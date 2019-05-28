<?php

namespace LaraSPF;

use DingoQueryMapper\Parser\DingoQueryMapper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait Filterable{

    /**
     *
     * Get a two value array containing the sorting column and the direction
     *
     * @param Collection $params
     * @return array
     *
     */
    private static function _getSorting(Collection $params)
    {
        $keyword = config("filter.keywords.sorting");
        return $params->has($keyword) ? explode("/", $params->get($keyword)) : null;
    }

    /**
     *
     * Get an array containing only the relationships
     *
     * @param Collection $params
     * @return array
     *
     */
    private static function _getRelationships(Collection $params)
    {
        $keyword = config("filter.keywords.relationships");
        return $params->has($keyword) ? explode(",", $params->get($keyword)) : [];
    }

    /**
     *
     * Get an array containing only the column filters
     *
     * @param Collection $params
     * @return array
     *
     */
    private static function _getFilters(Collection $params)
    {
        $keywords = config("filter.keywords") + config("filter.additional_keywords");
        return $params->filter(function ($value, $key) use ($keywords){
            return !in_array($key, $keywords);
        })->all();
    }

    /**
     *
     * Process a filter and append it to the query
     *
     * @param Builder $query
     * @return Builder
     *
     */
    private static function _processFilter($query, $column_unparsed, $value, $relationship=null)
    {
        $parsed = preg_split("/".config("filter.column_query_modificator")."/", $column_unparsed);

        $column = $parsed[0];

        if($column === config("filter.keywords.model_count")){

            $fk = $query->getRelation("posts");
            $fk = $fk->getForeignKeyName();

            return $query->whereHas($relationship, function($q) use ($value, $fk){
                $q->groupBy($fk)->havingRaw('COUNT(*) = ?', [$value]);
            });

        }

        if(count($parsed) === 2){
            $modificator = $parsed[1];
            switch($modificator){
                case "start":
                    $args = [$column, ">=", $value];
                    break;
                case "end":
                    $args = [$column, "<=", $value];
                    break;
                case "like":
                    $args = [$column, "ILIKE", "%$value%"];
                    break;
                case "not":
                    $args = [$column, "!=", "$value"];
                    break;
                default:
                    $args = [$column, $value];
                    break;
            }
        } else{
            $args = [$column, $value];
        }
        

        if($relationship){
            return $query->whereHas($relationship, function($q) use($args, $value){
                if(is_array($value)){
                    $q->whereIn(...$args);
                }
                else{
                    $q->where(...$args);
                }
            });
        } else {
            if(is_array($value)){
                return $query->whereIn(...$args);

            } else {
                return $query->where(...$args);
            }
        }
        
    }

    private static function _addFilters($query, $filters)
    {
        $relationships = [];
        foreach($filters as $key => $value){

            $filter_attr = preg_split("/".config("filter.relationship_separator")."/", $key);

            if(count($filter_attr) === 2){
                $relationship = $filter_attr[0];
                $column_unparsed = $filter_attr[1];
                if(!in_array($relationship, $relationships)){
                    $relationships[] = $relationship;
                }
                $query = self::_processFilter($query, $column_unparsed, $value, $relationship);
            } else{
                $column_unparsed = $filter_attr[0];
                $query = self::_processFilter($query, $column_unparsed, $value);
            }
            

        }
        if(count($relationships) > 0){
            $query = $query->with($relationships);
        }

        return $query;

    }

    private static function _addRelationships($query, $relationships)
    {
        $query = $query->with($relationships);
        return $query;
    }

    private static function _sortResult($query, $sorting)
    {

        if(!$sorting){
            return $query;
        } else{
            return $query->orderBy($sorting[0], $sorting[1]);
        }
    }

    /**
     *
     * Turn the input into a collection. Also throws and InvalidArgumentException if the argument is not a
     * Request or an array.
     *
     * @param Request|array|Collection|null $input
     * @return Collection
     *
     *
     * @throws \InvalidArgumentException
     */

    private static function _normalizeArguments($input)
    {
        if (!$input){
            return collect();
        }
        if (is_object($input)){
            if(get_class($input) === Request::class) {
                return collect($input->all());
            } elseif (get_class($input) === Collection::class){
                return $input;
            } else {
                throw new \InvalidArgumentException('Argument must be a Request, Collection or array');
            }
        } elseif (is_array($input)){
            return collect($input);
        } else{
            throw new \InvalidArgumentException('Argument must be a Request, Collection or array');
        }
    }

    /**
     *
     * Get the query for selecting that match the request filters
     *
     * @param Request|array|Collection|null $input
     * @param Builder|null $builder
     * @return
     *
     *
     * @throws \InvalidArgumentException
     */

    public static function filter($input = null, $builder)
    {

        $input = self::_normalizeArguments($input);

        //$fields = self::getRequestFields($request);
        $filters = self::_getFilters($input);
        $relationships = self::_getRelationships($input);
        $sorting = self::_getSorting($input);


        $query = $builder;
        $query = self::_addRelationships($query, $relationships);
        $query = self::_addFilters($query, $filters);
        $query = self::_sortResult($query, $sorting);

        return $query;


    }

    private static function filterCollection($input = null, $collection)
    {

        $input = self::_normalizeArguments($input);
        $availableKeywords = array_keys(config("filter.keywords") + config("filter.additional_keywords"));
        $filteredCollection = [];
        
        //Loop through each filter
        foreach ($input as $key => $value) {
            // Ignore sort and pagination keywords
            if (!in_array($key, $availableKeywords)) {
                // Are we applying another filter to the collection?
                if (!empty($filteredCollection)) {
                    // Set collection to filtered results
                    $collection = $filteredCollection;
                    // Prepare the filtered collection for the next filter
                    $filteredCollection = [];
                }
                // If value is empty, do a search on values with a space
                if($value === "") {
                    $value = ' ';
                }
                // Does the filter have a where like modifier?
                if ( strpos( $key, '/like' ) !== false) {
                    $keyUnmodified = substr($key, 0, strrpos( $key, '/'));
                    // Apply filter to collection
                    foreach($collection as $item) {
                        // Where ilike
                        if (strpos(strtolower($item->$keyUnmodified), strtolower($value)) !== false ) {
                            $filteredCollection[] = $item;
                        }
                    }
                } else {
                    foreach($collection as $item) {
                        // Strict where
                        if (strtolower($item->$key) === strtolower($value)) {
                            $filteredCollection[] = $item;
                        }
                    }
                }
            }
        }
        return $filteredCollection;
    }

    public static function paginateCollection($input = null, $collection)
    {
        $input = self::_normalizeArguments($input);
        $qm = new DingoQueryMapper($input);
        $filteredCollection = $qm->createFromCollection($collection)->paginate();

        return $filteredCollection;
    }

    /**
     *
     * Get the collection containing the models matching the request filters
     *
     * @param Request|array|Collection|null $input
     * @param Builder|null $builder
     * @return Collection
     *
     *
     * @throws \InvalidArgumentException
     */

    public static function filterAndGet($input = null, $builder)
    {

        // Query parameters should only be done for the following keys/keywords
        $availableKeywords = array_keys(config("filter.keywords") + config("filter.additional_keywords"));
        $tableKeys = array_keys($builder->get()->first()->toArray());
        $availableKeywords = array_merge($availableKeywords , $tableKeys);

        foreach ($input as $key => $value) {
            // We will delete any WHERE modifiers that were added to the key used by Laravel-filter
            if( strpos( $key, '/' ) !== false) {
                $keyUnmodified = substr($key, 0, strrpos( $key, '/'));
            } else {      
                $keyUnmodified = $key;
            }
            // Does the filtered column or keyword not exist in the availableKeyword array?
            if(!in_array($keyUnmodified, $availableKeywords)) {
                throw new \InvalidArgumentException("The filtered column, '" . $keyUnmodified . "' does not exist!");
            }
        }

        $request = new Request($input);

        $input = self::_normalizeArguments($input);

        $query_string = http_build_query(\request()->except("page"));

        $query = self::filter($input, $builder);

        if($input->has("sum")){
            $aggregates = [];
            $columns = explode(",", $input->get("sum"));

            foreach($columns as $column){
                $aggregates[] = DB::raw("SUM($column) as $column");
            }

            $result = $query->get($aggregates)[0];

            foreach ($columns as $column){
                $result->$column = intval($result->$column);
            }

            return $result;
        }

        $query = $query->get();
        return $query;

    }


    public static function filterAndGetCollection($input = null, $collection)
    {
        // If collection is empty
        if (!count($collection)) {
            return $collection;
        }
        // Query parameters should only be done for the following keys/keywords
        $availableKeywords = array_keys(config("filter.keywords") + config("filter.additional_keywords"));
        $tableKeys = array_keys($collection->first()->toArray());
        $availableKeywords = array_merge($availableKeywords , $tableKeys);
        $hasFilter = false;

        foreach ($input as $key => $value) {
            // We will delete any WHERE modifiers that were added to the key used by Laravel-filter
            if( strpos( $key, '/' ) !== false) {
                $keyUnmodified = substr($key, 0, strrpos( $key, '/'));
            } else {      
                $keyUnmodified = $key;
            }
            // Does the filtered column or keyword not exist in the availableKeyword array?
            if(!in_array($keyUnmodified, $availableKeywords)) {
                throw new \InvalidArgumentException("The filtered column, '" . $keyUnmodified . "' does not exist!");
            } else {
                if (in_array($keyUnmodified, $tableKeys)) {
                    $hasFilter = true;
                }
            }
        }

        $request = new Request($input);
        $input = self::_normalizeArguments($input);
        if($hasFilter === true) {
            $query = self::filterCollection($input, $collection);
        } else {
            $query = $collection;
        }
        
        return collect($query);
    }



}
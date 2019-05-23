<?php

namespace App\API\v1\Library;

use Illuminate\Http\Request;
use JohannesSchobel\DingoQueryMapper\Parser\UriParser;

class CaseInsensitiveUriParser extends UriParser
{
	public function __construct(Request $request)
	{
		parent::__construct($request);
        $this->query = rawurldecode(strtolower($request->getQueryString()));
        $this->queryParameters = [];
        if ($this->hasQueryUri()) {
            $this->setQueryParameters($this->query);
        }
    }

    /**
     * Sets the query parameters
     *
     * @param $query
     */
    private function setQueryParameters($query) {
        $queryParameters = array_filter(explode('&', $query));

        array_map([$this, 'appendQueryParameter'], $queryParameters);
    }

    /**
     * Appends one parameter to the builder
     *
     * @param $parameter
     */
    private function appendQueryParameter($parameter) {
        preg_match($this->pattern, $parameter, $matches);

        if(empty($matches)) {
            return;
        }

        $operator = $matches[0];

        list($key, $value) = explode($operator, $parameter);

        if(strlen($value) == 0) {
            return;
        }

        if (( ! $this->isPredefinedParameter($key)) && $this->isLikeQuery($value)) {
            if ($operator == '=')    $operator = 'like';
            if ($operator == '!=')   $operator = 'not like';

            $value = str_replace('*', '%', $value);
        }

        $this->queryParameters[] = [
            'key' => $key,
            'operator' => $operator,
            'value' => $value
        ];
    }

    /**
     * Checks, if the query parameter contains an asteriks (*) symbol and must be treated as like parameter
     *
     * @param $query
     * @return int
     */
    private function isLikeQuery($query) {
        $pattern = "/^\*|\*$/";

        return (preg_match($pattern, $query, $matches));
    }

    /**
     * Checks if the key is a predefined parameter
     *
     * @param $key
     * @return bool
     */
    private function isPredefinedParameter($key) {
        return (in_array($key, $this->predefinedParams));
    }
}
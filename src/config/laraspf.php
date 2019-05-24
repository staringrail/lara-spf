<?php

return [

    /* Macro Names*/
    "macros" => [
        "filter" => "filter",
        "filterAndGet" => "filterAndGet"
    ],

    /* Keywords */
    "keywords" => [
        "sorting" => "sort",
        "page_size" => "page_size",
        "relationships" => "relationships",
        "fields" => "fields",
        "sum" => "sum",
        "model_count" => "model_count"
    ],

    "relationship_separator" => "@",
    "column_query_modificator" => "\/",
    "paginate_by_default" => true,
    "page_size" => 100,

    "additional_keywords" => [
        "page"
    ],

    /*
     * Are filter queries allowed? If set to true, queries like age>18 are allowed
     */
    'allowFilters' => false,

    /*
     * The default values
     */
    'defaults' => [
        'limit' => 15,
        'sort' => [
            [
                'column'    => 'id',
                'direction' => 'asc'
            ]
        ],
    ],

    /*
     * The parameters to be excluded
     */
    'excludedParameters' => [
        'include',          // because of Fractal Transformers
        'token',            // because of JWT Auth
    ],
];
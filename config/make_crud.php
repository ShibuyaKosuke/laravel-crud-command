<?php

return [

    /**
     * exporting table data when default
     */
    'export' => false,

    /**
     * filtering table data when default
     */
    'filter' => false,

    /**
     * sorting table data when default
     */
    'sortable' => false,

    /**
     * make controller for api only
     */
    'api' => false,

    /**
     * default model directory
     */
    'models_dir' => 'Models', // app/Models

    /**
     * use cache
     */
    'use_cache' => false,

    /**
     * CSV Export use BOM
     */
    'use_bom' => false,

    /**
     * cache time :seconds
     */
    'cache_time' => 60 * 60 * 24,

    /**
     * output blade files using default parent blade
     */
    'blade_parent_file' => 'layouts.app',

    /**
     * list view with changeable limit count
     */
    'perPages' => [15, 30, 50],

    /**
     * default count per page
     */
    'defaultPerPage' => 15,

    /**
     * form label addon when it required
     */
    'required_html' => '<span class="required text-danger">*</span>',

    /**
     * AuthorObserver default value for user_id
     */
    'author_observer_default_user_id' => null,

    /**
     * view setting
     */
    'view' => [
        'show' => [
            'horizontal' => false,
            'dt' => 'col-3 text-right',
            'dd' => 'col-9'
        ]
    ],

    /**
     * reserved column names
     */
    'columns' => [
        'primary_key' => [
            'id'
        ],
        'timestamps' => [
            'created_at' => 'created_at',
            'updated_at' => 'updated_at'
        ],
        'soft_delete' => [
            'deleted_at' => 'deleted_at'
        ],
        'author' => [
            'created_by' => 'created_by',
            'updated_by' => 'updated_by',
            'deleted_by' => 'deleted_by',
            'restored_by' => 'restored_by'
        ]
    ]
];

<?php

// config for Zynfly/LaravelMeta
return [

    /*
    |--------------------------------------------------------------------------
    | Meta Value Column Type
    |--------------------------------------------------------------------------
    |
    | The column type used for the 'value' column in meta tables when using
    | the LaravelMeta::createMetaTableFor() helper. Supported: "text", "longText".
    |
    */
    'value_column_type' => 'text',

    /*
    |--------------------------------------------------------------------------
    | Use Transactions
    |--------------------------------------------------------------------------
    |
    | Whether to wrap meta insert/update operations inside a database
    | transaction for data integrity.
    |
    */
    'use_transactions' => true,

];

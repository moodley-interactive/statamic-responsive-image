<?php

return [


    /*
    |--------------------------------------------------------------------------
    | Breakpoints
    |--------------------------------------------------------------------------
    |
    */
    'breakpoints' => [
        'sm' => 640,
        'md' => 768,
        'lg' => 1024,
        'xl' => 1280,
        '2xl' => 1536,
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider
    |--------------------------------------------------------------------------
    | Can currently be
    | imgix, glide
    |
    */

    'provider' => 'glide',

    'imgix_url' => env('AWS_URL'),

    /*
    |--------------------------------------------------------------------------
    | Container & Grid
    |--------------------------------------------------------------------------
    |
    */
    'grid' => [
        'container_max_width' => 1200,
        'container_padding' => 40,
        'columns' => 12,
    ],

    /*
    |--------------------------------------------------------------------------
    | Image
    |--------------------------------------------------------------------------
    |
    */

    /**
     * A value in percent that allows to mute the most dominant color.
     * Applying a change of this value to all existing images requires
     * regenerating the placeholders using the `php please resp:generate` command.
     */
    'background_color_mute_percent' => 66,

    /**
     * Defines how lazy-loading should be handled.
     * Can be "browser" (browser-level lazy-loading) or "lazysizes".
     */
    'lazy_loading' => 'lazysizes',
];

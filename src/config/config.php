<?php

return [

    /* 
     * Absolute path to filesystem gallery
     * 
     * Do not add a trailing slash!
     */
    'gallery_file_path' => "/Users/cgibbs/www/self/packages.dev/public/gallery",
    
    /*
     * URL pointing to filesystem gallery base
     *
     * Do not add a trailing slash!
     */
    'gallery_root_url' => "http://packages.dev/gallery",

    /*
     * Name of album header files
     */
    'header_image_name' => "header.jpg",

    /*
     * The filesystem delimiter ('/' for *nix, '\' for MSDOS/Windows)
     */
    'file_tree_delimiter' => '/',
    
    'gallery_data_dir' => storage_path('gallery'),

];

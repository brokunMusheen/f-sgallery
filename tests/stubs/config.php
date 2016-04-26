<?php

class Config {
    
    static $vars = [
        'f-sgallery::config' => [
            'public' => true,
            'gallery_dir' => '/foo/bar',
            
            'gallery_root_url' => 'http://test.com/gallery',
            
            'file_tree_delimiter' => '/',
            'gallery_index' => '',
            'header_image_name' => 'header.jpg',
        ],
    ];
    
    public static function get($var, $default = null)
    {
        return array_get(self::$vars, $var, $default);
    }
    
    public static function set($var, $value)
    {
        return array_set(self::$vars, $var, $value);
    }
    
}
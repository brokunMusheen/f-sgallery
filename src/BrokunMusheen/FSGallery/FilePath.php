<?php namespace BrokunMusheen\FSGallery;

use \Config;

class FilePath {

    /**
     * @param array $path
     * @param null string $base_dir
     * @return string
     */
    static function arrayToString(array $path, $base_dir = null)
    {
        if( null !== $base_dir )
        {
            $path = array_merge([$base_dir], $path);
        }

        return implode(Config::get('f-sgallery::config.file_tree_delimiter'), $path);
    }

    static function stringToArray($path)
    {
        return explode(Config::get('f-sgallery::config.file_tree_delimiter'), $path);
    }

    /**
     * @param string $parent
     * @param string $relative_path
     *
     * @return string
     */
    static function join($parent, $relative_path)
    {
        return $parent . Config::get('f-sgallery::config.file_tree_delimiter') . $relative_path;
    }

    static function windowsToUnix($path)
    {
        return str_replace('\\', '/', $path);
    }

    static function unixToWindows($path)
    {
        return str_replace('/', '\\', $path);
    }
}
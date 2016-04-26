<?php namespace BrokunMusheen\FSGallery;

use \Config;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;


/**
 * Class Album
 *
 * The Album class represents a folder on the filesystem, and can contain several
 * sub-albums, images, videos, a header image and a meta information file.
 *
 * @package BrokunMusheen\FSGallery
 */
class Album
{
    /**
     * Album's unique ID
     *
     * @var string
     */
    protected $_album_id;
    protected $_album_created_at;
    protected $_album_description;

    /**
     * File path from the gallery's root
     *
     * @var string
     */
    protected $_directory;

    /**
     * Album's name
     *
     * @var string
     */
    protected $_name;
    protected $_title;

    /**
     * Album's gallery
     *
     * @var Gallery
     */
    protected $_gallery;

    /**
     * Album's parent album
     *
     * @var Album|null
     */
    protected $_parent;

    /**
     * Album's sub-Albums
     *
     * @var Album[]|null
     */
    protected $_sub_albums = null;

    protected $_files;

    protected $_is_archived;

    protected $_is_sticky;

    protected $_description_file;
    
    protected $_fs;
    protected $_finder;

    /**
     * Creates new Album instance
     *
     * @param string $directory
     * @param Gallery $gallery
     */
    public function __construct($directory, Gallery $gallery)
    {
        $this->_fs = new Filesystem;
        
        $this->_finder = new Finder;
        $this->_finder = $this->_finder->in($directory);
        
        $this->_gallery = $gallery;
        $this->_directory = $directory;
        $this->_name = self::getDirectoryName($directory);

        $file_data = $this->_parseFolderName();

        if(null !== $file_data)
        {
            $this->_title     = $file_data['title'];
            $this->_is_sticky = $file_data['is_sticky'];
            $this->_is_archived = $file_data['is_archived'];
        }
    }

    
    /**
     * Returns local directory path
     *
     * @return string
     */
    public function getDirectory()
    {
        return $this->_directory;
    }

    /**
     * Returns Album's gallery
     *
     * @return Gallery
     */
    public function getGallery()
    {
        return $this->_gallery;
    }

    /**
     * Returns Album's unique ID. This ID is stored in the Album's meta information
     * file, which is generated if it doesn't already exist.
     *
     * @return string
     */
    public function getId()
    {
        if(null === $this->_album_id)
        {
            $this->_loadMetaInfo();
        }

        return $this->_album_id;
    }

    public function getCreatedAt()
    {
        if(null === $this->_album_created_at)
        {
            $this->_loadMetaInfo();
        }

        return $this->_album_created_at;
    }

    public function getDescription()
    {
        if(null === $this->_description_file)
        {
            $this->_loadDescriptionFile();
        }

        if (false === $this->_description_file)
        {
            return "";
        }
        else
        {
            return $this->_description_file->getDescription();
        }
    }

    
    /**
     * Returns Album's name
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }


    /**
     * Returns array of sub-Albums.
     *
     * @param bool $include_archived
     *
     * @return Album[]
     */
    public function albums($include_archived = false)
    {
        if(null === $this->_sub_albums)
        {
            $this->_load_sub_albums();
        }

        if($include_archived)
        {
            return $this->_sub_albums;
        }
        else
        {
            return array_filter($this->_sub_albums, function(Album $album)
            {
                return ! $album->isArchived();
            });
        }
    }

    /**
     * Returns array of Album contents. Special items such as the meta information
     * file, header image and thumbnail images are filtered out.
     *
     * @return Image[]|Video[]
     */
    public function getFiles()
    {
        $content = $this->getContent();

        $content = array_pluck($content, 'orig');

        return array_where($content, function ($key, $value) {
            return null !== $value;
        });
    }

    /**
     * Returns album content
     * 
     * @return array {
     *      array  Array of content keyed by content filename without the extension  {
     *          'thumb'  Image|null   The content thumbnail. Null if thumbnail
     *                                file does not exist
     * 
     *          'orig'   Image|Video  The content
     *      }
     * }
     */
    public function getContent()
    {
        if($this->_files === null)
        {
            $this->loadContent();
        }

        return $this->_files;
    }

    /**
     * Returns Album's header image as an instance of Image class if it exists.
     * Otherwise, null is returned.
     *
     * @return Image|null
     */
    public function getHeaderImage()
    {
        $header_path = FilePath::join($this->getRealPath(), Config::get('f-sgallery::config.header_image_name'));

        if($this->_fs->exists($header_path))
        {
            return new Image($header_path, $this);
        }
        else
        {
            return null;
        }
    }

    /**
     * Returns true if the album is archived. Otherwise, false.
     * 
     * Archived albums are those prefixed with an underscore (ex: _hidden-album)
     * 
     * @return  bool
     */
    public function isArchived()
    {
        return $this->_is_archived;
    }

    /**
     * Returns true if the album is sticky. Otherwise, false.
     *
     * Sticky albums are those suffixed with an underscore 's' (ex: sticky-album_s)
     *
     * @return  bool
     */
    public function isSticky()
    {
        return $this->_is_sticky;
    }

    /**
     * Returns the Album's meta information file. If it doesn't already exist, a new one
     * is created.
     *
     * @return MetaFile
     */
    public function getMetaInfo()
    {
        $meta_path = $this->getMetaFilePath();

        $meta_file = new MetaFile($meta_path, $this);

        if($this->_fs->exists($meta_path))
        {
            $meta_file->loadFile();
        }
        else
        {
            $meta_file->save();
        }

        return $meta_file;
    }

    protected function _loadMetaInfo()
    {
        $meta_info = $this->getMetaInfo();

        $this->_album_id = $meta_info->getId();
        $this->_album_description = $meta_info->getDescription();
        $this->_album_created_at = $meta_info->getCreatedAt();
    }

    /**
     * Returns the Albums that are at the bottom of the file tree starting
     * with the current Album, or an array containing the current Album if
     * it has no sub-Albums.
     * 
     * Note that albums containing sub-albums are not returned
     *
     * @param bool $include_archived
     *
     * @return Album[]
     */
    public function getLeaves($include_archived = false)
    {
        if($this->hasSubAlbums(true))
        {
            $leaves = [];

            foreach($this->albums($include_archived) as $sub_album)
            {
                $leaves = array_merge($leaves, $sub_album->getLeaves($include_archived));
            }

            return $leaves;
        }
        else
        {
            return [ $this ];
        }
    }

    /**
     * Returns Album's full file path on the filesystem.
     *
     * @return string
     */
    public function getRealPath()
    {
        return FilePath::join($this->_gallery->baseDir(), $this->getPath());
    }

    /**
     * Returns Album's file path from the gallery root.
     *
     * @return string
     */
    public function getPath()
    {
        $parent_path = [];

        $current_album = $this;
        while(null !== $current_album)
        {
            $parent_path[] = $current_album->getName();

            $current_album = $current_album->getParent();
        }

        $parent_path = array_reverse($parent_path);
        $parent_path = array_slice($parent_path, 1);

        return FilePath::arrayToString($parent_path);
    }

    /**
     * Returns parent Album. If this Album is the gallery's base dir, null is returned.
     *
     * @return Album|null
     */
    public function getParent()
    {
        if($this->getDirectory() == $this->_gallery->getBaseAlbum()->getDirectory())
        {
            return null;
        }

        if(null !== $this->_parent)
        {
            return $this->_parent;
        }

        $arr_path = FilePath::stringToArray($this->getDirectory());

        $head = array_slice($arr_path, 0, sizeof($arr_path) - 1);

        $parent_dir = FilePath::arrayToString($head);

        $this->_parent = new Album($parent_dir, $this->_gallery);

        return $this->_parent;
    }

    public function getTitle()
    {
        return $this->_title;
    }


    /**
     * Calls the $callback function, passing in this Album and every sub-Album
     * as the argument.
     *
     * @param callable|\Closure $callback
     * @param bool $include_archived
     */
    public function albumWalk(\Closure $callback, $include_archived = false)
    {
        $callback($this);

        if($this->hasSubAlbums($include_archived))
        {
            foreach($this->albums($include_archived) as $sub_album)
            {
                $sub_album->albumWalk($callback, $include_archived);
            }
        }
    }

    // TODO: Duplicate code. Revise the "has" methods for version 2.

    /**
     * Returns true if this Album has sub-Albums. Otherwise, returns false.
     *
     * @param bool $include_archived
     *
     * @return bool
     */
    public function hasSubAlbums($include_archived = false)
    {
        return $this->albums($include_archived) !== [];
    }

    /**
     * Returns true if this Album has content (ie: non-special images and video).
     * Otherwise, returns false.
     *
     * @return bool
     */
    public function hasContent()
    {
        foreach($this->getFiles() as $file)
        {
            return true;
        }

        return false;
    }

    /**
     * Returns true if this Album has non-special images. Otherwise, returns false.
     *
     * @return bool
     */
    public function hasImagery()
    {
        foreach($this->getFiles() as $file)
        {
            if(get_class($file->make()) == 'BrokunMusheen\FSGallery\Image')
            {

                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if this Album has video. Otherwise, returns false.
     *
     * @return bool
     */
    public function hasVideo()
    {
        foreach($this->getFiles() as $file)
        {
            if(get_class($file->make()) == 'BrokunMusheen\FSGallery\Video')
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Loads album content, such as images, videos and the meta info file.
     * 
     * @return void
     */
    protected function loadContent()
    {
        $this->_files = [];

        $reserved_names = $this->getGallery()->reservedFileNames();

        $files = $this->_finder->depth('== 0')->files();
        
        foreach($files as $spl_file)
        {
            $file_path = $spl_file->getPathName();
            
            if(Config::get('f-sgallery::config.file_tree_delimiter') == '\\')
            {
                $file_path = FilePath::unixToWindows($file_path);
            }

            $file = new File($file_path, $this);

            if(null !== $file->make()
                && ! $file->isDescription()
                && ! in_array($file->getName(), $reserved_names))
            {
                $file_key = $file->getTitle();

                if( ! array_key_exists($file_key, $this->_files ))
                {
                    $this->_files[$file_key] = [
                        'thumb' => null,
                        'orig'  => null,
                    ];
                }

                if($file->isThumbnail())
                {
                    $this->_files[$file_key]['thumb'] = $file->make();
                }
                else
                {
                    $this->_files[$file_key]['orig'] = $file->make();
                }
            }
        }
    }

    protected function _loadDescriptionFile()
    {
        $description_file_name = $this->getRealPath() . '_caption.txt';

        if($this->_fs->exists($description_file_name))
        {
            $this->_description_file = new Description($description_file_name, $this->_parent);
        }
        else
        {
            $this->_description_file = false;
        }
    }

    /**
     * Sets local sub-Album array.
     *
     * @return void
     */
    protected function _load_sub_albums()
    {
        $this->_sub_albums = [];

        $sub_directories = $this->_finder->depth('== 0')->directories();

        foreach($sub_directories as $directory)
        {
            // $directory is an instance of Symfony\Component\Finder\SplFileInfo
            // see: http://api.symfony.com/3.0/Symfony/Component/Finder/SplFileInfo.html
            
            $this->_sub_albums[] = new Album($directory->getPathname(), $this->_gallery);
        }
    }

    protected function _parseFolderName()
    {
        $results = [];

        $successfully_parsed = preg_match("/(_?)([^_\r\n]+)_?(s?)/",
            $this->getName(),
            $results);

        if($successfully_parsed)
        {
            return [
                'is_archived' => ($results[1] == '_' ? true : false),
                'title' => $results[2],
                'is_sticky' => ($results[3] == 's' ? true : false),
            ];
        }
        else
        {
            return null;
        }
    }

    /**
     * @param  string  $file_path
     * @return  string
     */
    public static function getDirectoryName($file_path)
    {
        return last(FilePath::stringToArray($file_path));
    }

    /**
     * Return file path for meta info file
     * 
     * @return string
     */
    public function getMetaFilePath()
    {
        return FilePath::join($this->getRealPath(), MetaFile::$file_name);
    }

    /**
     * Removes meta file and clears meta information
     * 
     * @return void
     */
    public function unsetId()
    {
        unlink($this->getMetaFilePath());
        
        $this->_album_id = null;
        $this->_album_created_at = null;
    }
}

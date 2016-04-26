<?php namespace BrokunMusheen\FSGallery;

use \Config;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Class File
 *
 * The File represents a file contained within an Album.
 *
 * @package BrokunMusheen\FSGallery
 */
class File {

    /**
     * File's parent Album
     *
     * @var Album
     */
    protected $_album;

    /**
     * Path to the File from Gallery root.
     *
     * @var string
     */
    protected $_path;

    protected $_extension;
    protected $_is_archived;
    protected $_is_thumbnail;
    protected $_name;
    protected $_order;
    protected $_title;
    protected $_url;

    protected $_fs;
    protected $_finder;

    /**
     * Creates new File instance
     *
     * @param string $file_path
     * @param Album $album
     */
    public function __construct($file_path, Album $album)
    {
        $this->_fs = new Filesystem;

        $this->_finder = new Finder;
        $this->_finder = $this->_finder->in(self::getContainingDirectory($file_path));

        $this->_path = $file_path;
        $this->_album = $album;

        $file_data = $this->_parseFilename();

        if(null !== $file_data)
        {
            $this->_is_archived = $file_data['is_archived'];
            $this->_order = $file_data['order'];
            $this->_title = $file_data['title'];
            $this->_is_thumbnail = $file_data['is_thumbnail'];
            $this->_extension = $file_data['extension'];
        }
    }

    /**
     * Returns file extension
     * 
     * @return string
     */
    public function getExtension()
    {
        return $this->_extension;
    }

    /**
     * Returns file weight
     *
     * @return int
     */
    public function getOrder()
    {
        return $this->_order;
    }

    /**
     * Returns absolute path to file
     *
     * @return string
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * Returns relative URL pointing to File.
     *
     * @return string
     */
    public function getUrl()
    {
        // Use existing URL if it's been set - we can safely assume that it hasn't been changed
        if(null === $this->_url)
        {
            // Remove Gallery's containing folder from this file's path to make it relative
            $containing_folder = $this->_album->getGallery()->baseDir();
            
            $relative_path = str_replace($containing_folder, '', $this->getPath());
            
            $this->_url = Config::get('f-sgallery::config.gallery_root_url') . $relative_path;

            // Convert Window's \'s to URL-equivalent /'s, if applicable
            $this->_url = str_replace('\\', '/', $this->_url);
        }

        return $this->_url;
    }

    /**
     * Return file name
     *
     * @return string
     */
    public function getName()
    {
        if(null === $this->_name)
        {
            $arr_file_path = FilePath::stringToArray($this->getPath());

            $this->_name = end($arr_file_path);
        }

        return $this->_name;
    }

    /**
     * Returns thumbnail image for this file. Returns null if no thumbnail exists.
     * 
     * @return Image|null
     */
    public function getThumbnail()
    {
        $album_content = $this->_album->getContent();
        
        return array_get($album_content, $this->getTitle() . '.thumb');
    }

    /**
     * Return File's title by removing the number-underscore prefix and file extension.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->_title;
    }

    /**
     * Returns whether or not file is archived
     * 
     * @return bool
     */
    public function isArchived()
    {
        if($this->_is_archived)
        {
            return true;
        }

        $parent_album = $this->_album;
        while( $parent_album->getDirectory() !== $this->_album->getGallery()->getBaseAlbum()->getDirectory())
        {
            if($parent_album->isArchived())
            {
                return true;
            }

            $parent_album = $parent_album->getParent();
        }

        return false;
    }

    /**
     * Returns whether or not file is a thumbnail
     * 
     * @return bool
     */
    public function isThumbnail()
    {
        return $this->_is_thumbnail && (null !== $this->_is_thumbnail);
    }

    /**
     * Returns File subclass instance based on the type of the current File.
     *
     * @return Image|MetaFile|Video|null
     */
    public function make()
    {
        $type = static::type($this->getPath());

        switch($type)
        {
            case 'desc':
                return new Description($this->getPath(), $this->_album);

            case 'jpg':
                return new Image($this->getPath(), $this->_album);

            case 'meta':
                return new MetaFile($this->getPath(), $this->_album);

            case 'mp4':
                return new Video($this->getPath(), $this->_album);

            default:
                return null;
        }
    }

    /**
     * Returns true if this File is an image. Otherwise, false.
     *
     * @return bool
     */
    public function isDescription()
    {
        $type = static::type($this->getPath());

        return $type == 'desc';
    }

    /**
     * Returns true if this File is an image. Otherwise, false.
     *
     * @return bool
     */
    public function isImage()
    {
        $type = static::type($this->getPath());

        return $type == 'jpg';
    }

    /**
     * Returns true if this File is a meta-data file. Otherwise, false.
     *
     * @return bool
     */
    public function isMetaInfo()
    {
        $type = static::type($this->getPath());

        return $type == 'meta';
    }

    /**
     * Returns true if this File is a video. Otherwise, false.
     *
     * @return bool
     */
    public function isVideo()
    {
        $type = static::type($this->getPath());

        return $type == 'mp4';
    }

    public function setThumbnail(Image $image)
    {
        $this->_thumbnail = $image;
    }

    protected function _parseFilename()
    {
        $results = [];

        $successfully_parsed = preg_match("/^(_?)(\d*)?_?([^_]*)_?(.*)\.(.*)$/",
                $this->getName(),
                $results);

        if($successfully_parsed)
        {
            return [
                'is_archived' => ($results[1] == '_' ? true : false),
                'order' => $results[2],
                'title' => $results[3],
                'is_thumbnail' => ($results[4] == 'thumb' ? true : false),
                'extension' => $results[5],
            ];
        }
        else
        {
            return null;
        }
    }

    /**
     * Returns the file type of the file at $file_path.
     * Returns null if file isn't found or if it's not recognized.
     *
     * @param string $file_path
     * 
     * @return null|string { desc | jpg | meta | mp4 }
     */
    static function type($file_path)
    {
        $extension = '';

        $file_name = last(FilePath::stringToArray($file_path));

        // check file type based on file name
        if($file_name == MetaFile::$file_name) return 'meta';

        if(last(explode('_', $file_name)) == 'caption.txt') return 'desc';

        // check file type based on extension
        $extension_pos = strrpos($file_path, '.');
        if($extension_pos !== FALSE)
        {
            $extension = substr($file_path, $extension_pos + 1);
        }

        switch($extension)
        {
            case 'jpg':
            case 'jpeg':
                return 'jpg';

            case 'mp4':
                return 'mp4';

            default:
                return null;
        }
    }

    /**
     * Returns the absolute path of the directory containing the file at $file_path
     * 
     * @param string $file_path
     * 
     * @return string
     */
    public static function getContainingDirectory($file_path)
    {
        $nLastSlash = strrpos($file_path, Config::get('f-sgallery::config.file_tree_delimiter'));

        return substr($file_path, 0, $nLastSlash);
    }
}

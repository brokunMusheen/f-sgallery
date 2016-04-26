<?php namespace BrokunMusheen\FSGallery;

use \Config;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class Gallery
 *
 * The main class for the FSGallery library. The gallery has one base Album.
 *
 * @package BrokunMusheen\FSGallery
 */
class Gallery {

    /**
     * Gallery's root Album
     *
     * @var Album
     */
    protected $_base_album;

    /**
     * Gallery directory's parent
     *
     * @var Album
     */
    protected $_parent_directory;

    /**
     * Index for looking up Album file paths by ID
     *
     * @var array {
     *
     *   @type string key   ID of the Albums indexed
     *   @type string value File path of the Albums indexed
     *
     * }
     */
    protected $_album_index = [];

    /**
     * File path for the Gallery's Albums index
     *
     * @var string
     */
    protected $_album_index_location;

    /**
     * Filesystem helper library
     * 
     * @see http://symfony.com/doc/3.0/components/filesystem/introduction.html
     * 
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected $fs;

    /**
     * Creates new Gallery instance
     */
    public function __construct()
    {
        $this->fs = new Filesystem;
        
        $this->_initBaseDir();
        
        $this->_setBaseAlbum($this->baseDir());
        $this->_setAlbumIndexLocation();
        $this->loadIndex();
    }

    /**
     * Crawl the gallery and build a new index file.
     *
     * @return void
     */
    public function buildIndex()
    {
        $index = [];
        $album_ids = [];

        $callback =
            function ( Album $album ) use (&$index, &$album_ids)
            {
                usleep(100000);
                if($album->getPath() != '')
                {
                    $album_id = $album->getId();
                    
                    // Check for duplicated album id
                    while(in_array($album_id, $album_ids))
                    {
                        $album->unsetId();
                        
                        $album_id = $album->getId();
                    }
                    
                    $album_ids[] = $album_id;
                    
                    $index[$album_id] = $album->getPath();
                }
            };

        $root_album = $this->getBaseAlbum();
        $root_album->albumWalk($callback, true);
        
        $this->fs->dumpFile($this->_album_index_location, json_encode($index));
        $this->_album_index = $index;
    }

    /**
     * Retrieve an album by its path on the filesystem.
     *
     * @param array $album_path An array representing the folder path to the desired album.
     *
     * @return bool|Album Returns an instance of Album if it is found, FALSE otherwise.
     */
    public function getAlbum(array $album_path)
    {
        $directory_path = FilePath::arrayToString($album_path, $this->baseDir());

        if($this->fs->exists($directory_path))
        {
            return new Album($directory_path, $this);
        }
        else
        {
            return false;
        }
    }

    /**
     * Retrieve an Album by its unique ID. Return false if Album not found.
     *
     * @param string $album_id
     * @return bool|Album
     */
    public function getAlbumById($album_id)
    {
        if($this->_album_index === [] || ! array_key_exists($album_id, $this->_album_index))
        {
            return false;
        }
        else
        {
            $album_path = $this->_album_index[$album_id];

            if($album = $this->getAlbum(FilePath::stringToArray($album_path)))
            {
                // Album found on filesystem and it's ready to be served
                return $album;
            }
            else
            {
                // ID is in the index, but album not found on filesystem
                // Rebuild index and try again
                $this->buildIndex();

                return $this->getAlbumById($album_id);
            }
        }
    }

    /**
     * Returns the folder path of the gallery folder's parent.
     *
     * @return string
     */
    public function parentFolder()
    {
        if( isset($this->_parent_directory) )
        {
            return $this->_parent_directory;
        }
        
        $fs_delimiter = Config::get('f-sgallery::config.file_tree_delimiter');
        
        $path_steps = explode($fs_delimiter, $this->baseDir());

        $this->_parent_directory = implode($fs_delimiter, array_slice($path_steps, 0, count($path_steps) - 1));
        
        return $this->_parent_directory;
    }

    /**
     * Returns the folder path of the gallery folder.
     *
     * @return string
     */
    public function baseDir()
    {
        return Config::get('f-sgallery::config.gallery_file_path');
    }

    /**
     * Loads the album ID gallery index. Returns true on successful index load, false otherwise.
     *
     * @return bool
     */
    public function loadIndex()
    {
        if( $this->fs->exists($this->_album_index_location) )
        {
            $album_index = file_get_contents($this->_album_index_location);
            $this->_album_index = json_decode($album_index, true);

            return true;
        }

        return false;
    }

    /**
     * Returns a list of gallery reserved file names.
     *
     * @return string[]
     */
    public function reservedFileNames()
    {
        return [
            MetaFile::$file_name,
            Config::get('f-sgallery::config.header_image_name'),
        ];
    }

    /**
     * Create gallery base directory if it doesn't already exist.
     *
     * @return void
     */
    protected function _initBaseDir()
    {
        if( ! $this->fs->exists($this->baseDir()) )
        {
            $this->fs->mkdir($this->baseDir(), 0755);
        }
    }

    /**
     * Set album index property.
     *
     * @return void
     */
    protected function _setAlbumIndexLocation()
    {
        $gallery_storage_path = Config::get('f-sgallery::config.gallery_data_dir');

        if( ! $this->fs->exists($gallery_storage_path) )
        {
            $this->fs->mkdir($gallery_storage_path, 0755);
        }

        $this->_album_index_location = FilePath::arrayToString(['gallery.idx'], $gallery_storage_path);
    }

    /**
     * Set base album property
     *
     * @param string $directory File path for the new album
     */
    protected function _setBaseAlbum($directory)
    {
        $this->_base_album = new Album($directory, $this);
    }

    /**
     * Returns Gallery's root Album.
     *
     * @return Album
     */
    public function getBaseAlbum()
    {
        return $this->_base_album;
    }

}

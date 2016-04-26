<?php namespace BrokunMusheen\FSGallery;

/**
 * Class MetaFile
 *
 * Represents the Album metadata file.
 *
 * @package BrokunMusheen\FSGallery
 */
class MetaFile extends File {

    /**
     * The file name used for the metadata file
     *
     * @var string
     */
    static $file_name = '.meta.info';

    /**
     * The unique ID of the parent Album
     *
     * @var string
     */
    protected $_album_id;

    protected $_album_description;

    protected $_album_created_at;

    /**
     * Returns the metadata file name.
     *
     * @override
     *
     * @return string
     */
    public function getName()
    {
        return static::$file_name;
    }

    /**
     * Returns the metadata file's title.
     *
     * @override
     *
     * @return string
     */
    public function getTitle()
    {
        return 'Album Meta Info';
    }

    /*
     * Meta info-specific functions
     */

    /**
     * Returns the parent Album's ID.
     *
     * @return string
     */
    public function getId()
    {
        return $this->_album_id;
    }

    public function getDescription()
    {
        return $this->_album_description;
    }

    public function getCreatedAt()
    {
        if(null === $this->_album_created_at)
        {
            $this->_album_created_at = \File::lastModified($this->_album->getRealPath());
            $this->save();
        }

        return $this->_album_created_at;
    }

    /**
     * Sets information needed for new metadata file.
     *
     * @return void
     */
    public function generate()
    {
        $this->_album_id = $this->_create_id();

        $this->_album_created_at = filemtime($this->_album->getRealPath());
    }

    /**
     * Attempts to load existing metadata file information. Returns true on success, false otherwise.
     *
     * @return bool
     */
    public function loadFile()
    {
        if( ! $this->_fs->exists($this->getPath()) )
        {
            return false;
        }

        $meta_info = json_decode(file_get_contents($this->getPath()), true);

        $this->_album_id = array_get($meta_info, 'id', null);
        $this->_album_created_at = array_get($meta_info, 'created_at', null);

        return true;
    }

    /**
     * Saves metadata information to a file within the parent Album. Generates required information if needed.
     *
     * @return void
     */
    public function save()
    {
        if( ! isset($this->_album_id) )
        {
            $this->generate();
        }

        $meta_info =[
            'id' => $this->_album_id,
            'created_at' => $this->_album_created_at,
        ];
        
        $this->_fs->dumpFile($this->getPath(), json_encode($meta_info), 0755);
    }

    /**
     * Returns a unique ID string.
     *
     * @return string
     */
    protected function _create_id()
    {
        return uniqid();
    }

}
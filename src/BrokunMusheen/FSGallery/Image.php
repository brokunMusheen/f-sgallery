<?php namespace BrokunMusheen\FSGallery;

/**
 * Class Image
 *
 * Represents a recognized image file.
 *
 * @package BrokunMusheen\FSGallery
 */
class Image extends File {

    protected $_description_file;

    /**
     * @return string
     */
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

    public function getOriginal()
    {
        $album_content = $this->_album->getContent();

        array_get($album_content, $this->getName() . '.orig');
    }

    protected function _loadDescriptionFile()
    {
        $description_file_name = FilePath::join($this->_album->getRealPath(), $this->getTitle() . '_caption.txt');

        if(\File::exists($description_file_name))
        {
            $this->_description_file = new Description($description_file_name, $this->_album);
        }
        else
        {
            $this->_description_file = false;
        }
    }

}
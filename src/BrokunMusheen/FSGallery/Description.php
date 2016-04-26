<?php namespace BrokunMusheen\FSGallery;

class Description extends File {

    /**
     * Returns description file contents
     * 
     * @return string
     */
    public function getDescription()
    {
        if( $this->_fs->exists($this->_path) )
        {
            return file_get_contents($this->_path);
        }
        else
        {
            return "";
        }
    }

}
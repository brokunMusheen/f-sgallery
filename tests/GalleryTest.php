<?php

use BrokunMusheen\FSGallery\Gallery;
use org\bovigo\vfs\vfsStream;

class GalleryTest extends TestCase
{
    /** @test */
    public function it_has_a_parent_directory()
    {
        $gallery = new Gallery;
        $this->assertEquals('vfs://public', $gallery->parentFolder());
    }

    /** @test */
    public function it_has_a_base_album()
    {
        $gallery = new Gallery;
        $this->assertInstanceOf('BrokunMusheen\FSGallery\Album', $gallery->getBaseAlbum());
        $this->assertEquals('vfs://public/gallery', $gallery->getBaseAlbum()->getDirectory());
    }
    
    /** @test */
    public function it_has_a_base_directory()
    {
        $gallery = new Gallery;
        $this->assertEquals('vfs://public/gallery', $gallery->baseDir());
    }
    
    /** @test */
    public function it_can_retrieve_albums_by_path()
    {
        $gallery = new Gallery;

        $this->assertInstanceOf('BrokunMusheen\FSGallery\Album', $gallery->getAlbum(['foo', 'bar']));
        
        $this->assertFalse($gallery->getAlbum(['not', 'here']));
    }
    
    /** @test */
    public function it_can_build_an_album_index()
    {
        $gallery = new Gallery;
        
        $gallery->buildIndex();
        
        $this->assertFileExists(\Config::get('f-sgallery::config.gallery_data_dir') . '/gallery.idx');
    }
    
    /** @test */
    public function it_can_get_an_album_by_id()
    {
        $gallery = new Gallery;

        $this->createMetaFile(vfsStream::url('public/gallery/foo'), 'test', '100000');

        $gallery->buildIndex();
        $gallery->loadIndex();

        $album = $gallery->getAlbumById('test');
        
        $this->assertInstanceOf('BrokunMusheen\FSGallery\Album', $album);
        $this->assertEquals('vfs://public/gallery/foo', $album->getDirectory());
    }
    
    /** @test */
    public function it_has_a_set_of_reserved_file_names()
    {
        $gallery = new Gallery;
        $this->assertInternalType('array', $gallery->reservedFileNames());
    }
    
    /** @test */
    public function it_ensures_that_no_two_albums_have_the_same_id()
    {
        $this->createMetaFile(vfsStream::url('public/gallery/foo'), 'nope', '10000');
        $this->createMetaFile(vfsStream::url('public/gallery/foo/bar'), 'nope', '10000');
        
        $gallery = new Gallery;
        
        $this->assertEquals('nope', $gallery->getAlbum(['foo'])->getId());
        $this->assertEquals('nope', $gallery->getAlbum(['foo', 'bar'])->getId());
        
        $gallery->buildIndex();
        
        $this->assertEquals('nope', $gallery->getAlbum(['foo'])->getId());
        $this->assertNotEquals('nope', $gallery->getAlbum(['foo', 'bar'])->getId());
    }
}

<?php

use BrokunMusheen\FSGallery\File;
use org\bovigo\vfs\vfsStream;

class FileTest extends TestCase
{
    /**
     * @test
     * 
     * @covers File::getExtension
     */
    public function it_has_a_file_extension()
    {
        $image = $this->buildFile('public/gallery/foo/bar/img-1.jpg');
        $this->assertEquals('jpg', $image->getExtension());
        
        $video = $this->buildFile('public/gallery/foo/_boo/sample-vid-1.mp4');
        $this->assertEquals('mp4', $video->getExtension());
    }
    
    /**
     * @test
     *
     * @covers File::getOrder
     */
    public function it_can_have_a_weight()
    {
        $image = $this->buildFile('public/gallery/foo/baz/bazzybazz/2_photo-2.jpg');
        $this->assertEquals(2, $image->getOrder());
        
        
        // Some files do not have a set weight

        $image = $this->buildFile('public/gallery/foo/baz/bazzybazz/photo-1.jpg');
        $this->assertEquals('', $image->getOrder());
    }
    
    /**
     * @test
     *
     * @covers File::getPath
     */
    public function it_has_an_absolute_file_path()
    {
        $image = $this->buildFile('public/gallery/foo/baz/bazzybazz/2_photo-2.jpg');
        $this->assertEquals('vfs://public/gallery/foo/baz/bazzybazz/2_photo-2.jpg', $image->getPath());
    }
    
    /**
     * @test
     *
     * @covers File::getUrl
     */
    public function it_has_an_absolute_url()
    {
        $image = $this->buildFile('public/gallery/foo/baz/bazzybazz/2_photo-2.jpg');
        $this->assertEquals('http://test.com/gallery/foo/baz/bazzybazz/2_photo-2.jpg', $image->getUrl());
    }

    /**
     * @test
     *
     * @covers File::getName
     */
    public function it_has_a_file_name()
    {
        $image = $this->buildFile('public/gallery/foo/baz/bazzybazz/2_photo-2.jpg');
        $this->assertEquals('2_photo-2.jpg', $image->getName());
    }

    /**
     * @test
     *
     * @covers File::getThumbnail
     */
    public function it_has_a_thumbnail()
    {
        $image = $this->buildFile('public/gallery/foo/baz/bazzybazz/1_photo-3.jpg');
        
        $this->assertInstanceOf('BrokunMusheen\FSGallery\Image', $image->getThumbnail());
        $this->assertEquals('photo-3_thumb.jpg', $image->getThumbnail()->getName());

        
        // Some content does not have a thumbnail
        
        $image = $this->buildFile('public/gallery/foo/baz/bazzybazz/2_photo-2.jpg');
        
        $this->assertNull($image->getThumbnail());
    }
    
    /**
     * @test
     *
     * @covers File::getTitle
     */
    public function it_has_a_title()
    {
        $image = $this->buildFile('public/gallery/foo/baz/bazzybazz/1_photo-3.jpg');
        $this->assertEquals('photo-3', $image->getTitle());
    }

    /**
     * @test
     *
     * @covers File::isArchived
     */
    public function it_has_archived()
    {
        $image = $this->buildFile('public/gallery/foo/baz/bazzy/photo-2.jpg');
        $this->assertFalse($image->isArchived());
        
        
        // Individual files can be archived
        
        $image = $this->buildFile('public/gallery/foo/baz/bazzy/_photo-3.jpg');
        $this->assertTrue($image->isArchived());


        // files within an archived album are also archived

        $image = $this->buildFile('public/gallery/foo/_boo/photo-c.jpg');
        $this->assertTrue($image->isArchived());
    }
    
    /**
     * @test
     *
     * @covers File::isThumbnail
     */
    public function it_can_be_a_thumbnail()
    {
        $image = $this->buildFile('public/gallery/foo/baz/bazzy/photo-3_thumb.jpg');
        $this->assertTrue($image->isThumbnail());
        
        $image = $this->buildFile('public/gallery/foo/baz/bazzy/photo-2.jpg');
        $this->assertFalse($image->isThumbnail());
    }
    
    
    /*
     * Remaining functions to test:
     * make()
     * isDescription()
     * isImage()
     * isMetaInfo()
     * isVideo()
     * setThumbnail( Image )
     * type( string )
     */

    /**
     * @test
     * 
     * @covers File::getContainingDirectory
     */
    public function it_can_calculate_the_directory_given_a_file_path()
    {
        $this->assertEquals('/foo/bar', File::getContainingDirectory('/foo/bar/baz.jpg'));
    }
    
    /**
     * @param  string  $file_path
     * @return  File
     */
    protected function buildFile($file_path)
    {
        $file_album = $this->buildAlbum(File::getContainingDirectory($file_path));
        return new File(vfsStream::url($file_path), $file_album);
    }
}

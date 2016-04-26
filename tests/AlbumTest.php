<?php

use BrokunMusheen\FSGallery\Album;
use BrokunMusheen\FSGallery\Gallery;
use org\bovigo\vfs\vfsStream;

class AlbumTest extends TestCase {
    
    
    /**
     * @test
     * 
     * @covers Album::getGallery
     */
    public function it_has_a_gallery()
    {
        $album = $this->buildAlbum('public/gallery/foo');
        $this->assertInstanceOf('BrokunMusheen\FSGallery\Gallery', $album->getGallery());
    }
    
    /**
     * @test
     * 
     * @covers Album::getDirectory
     */
    public function it_has_a_directory()
    {
        $album_path = 'public/gallery/foo';
        $album = $this->buildAlbum($album_path);
        
        $this->assertEquals(vfsStream::url($album_path), $album->getDirectory());
    }
    
    /**
     * @test
     * 
     * @covers Album::getId
     */
    public function it_has_an_id()
    {
        $album_path = 'public/gallery/foo';
        $album = $this->buildAlbum($album_path);
        
        $this->createMetaFile(vfsStream::url($album_path), 'foo', '100000');
        
        $this->assertEquals('foo', $album->getId());
    }
    
    /**
     * @test
     * 
     * @covers Album::getCreatedAt
     */
    public function it_has_a_created_at_timestamp()
    {
        $album_path = 'public/gallery/foo';
        $album = $this->buildAlbum($album_path);

        $this->createMetaFile(vfsStream::url($album_path), 'foo', '100000');

        $this->assertEquals('100000', $album->getCreatedAt());
    }
    
    /**
     * @test
     * 
     * @covers Album::getDescription
     */
    public function it_can_have_a_description()
    {
        $album_path = 'public/gallery/foo';
        $description = 'lorem ipsum';
        
        $album = $this->buildAlbum($album_path);

        $this->createDescription($album->getDirectory(), $description);
        
        $this->assertFileExists('vfs://public/gallery/foo_caption.txt');
        $this->assertEquals($description, $album->getDescription());
        
        
        // Albums without descriptions return an empty string (ie: '')
        
        $nondescript_album = $this->buildAlbum('public/gallery/foo/bar');
        $this->assertEquals('', $nondescript_album->getDescription());
    }
    
    /**
     * @test
     * 
     * @covers Album::getName
     */
    public function it_has_a_name()
    {
        $album_path = 'public/gallery/foo';
        $album = $this->buildAlbum($album_path);

        $this->assertEquals('foo', $album->getName());
    }

    /**
     * @test
     * 
     * @covers Album::albums
     */
    public function it_can_have_sub_albums()
    {
        $album_path = 'public/gallery/foo';
        $album = $this->buildAlbum($album_path);
        
        $this->assertEquals(4, count($album->albums()));
        
        foreach($album->albums() as $sub_album)
        {
            $this->assertInstanceOf('BrokunMusheen\FSGallery\Album', $sub_album);
        }
        
        
        // And archived sub-albums can be retrieved
        
        $this->assertEquals(5, count($album->albums(true)));

        foreach($album->albums(true) as $sub_album)
        {
            $this->assertInstanceOf('BrokunMusheen\FSGallery\Album', $sub_album);
        }
    }
    
    /**
     * @test
     * 
     * @covers Album::getDirectoryName
     */
    public function it_can_calculate_the_album_name_from_the_folder_path()
    {
        $album_path = 'public/gallery/foo';
        $album = $this->buildAlbum($album_path);
        
        $this->assertEquals('foo', Album::getDirectoryName($album->getDirectory()));
    }

    /**
     * @test
     *
     * @covers Album::getFiles
     */
    public function it_can_have_files()
    {
        $album_path = 'public/gallery/foo/bar';
        $album = $this->buildAlbum($album_path);

        $this->assertEquals(3, count($album->getFiles()));
        foreach($album->getFiles() as $file)
        {
            $this->assertInstanceOf('BrokunMusheen\FSGallery\File', $file);
        }
    }
    
    /**
     * @test
     * 
     * @covers Album::getContent
     */
    public function it_can_have_content()
    {
        $album_path = 'public/gallery/foo/bar';
        $album = $this->buildAlbum($album_path);

        $album_content = $album->getContent();
        
        $this->assertEquals(3, count($album_content));
        
        $this->assertInstanceOf('BrokunMusheen\FSGallery\Image', $album_content['img-1']['thumb']);
        $this->assertInstanceOf('BrokunMusheen\FSGallery\Image', $album_content['img-1']['orig']);
        
        $this->assertNull($album_content['img-2']['thumb']);
        $this->assertInstanceOf('BrokunMusheen\FSGallery\Image', $album_content['img-2']['orig']);
        
        $this->assertInstanceOf('BrokunMusheen\FSGallery\Image', $album_content['img-3']['thumb']);
        $this->assertInstanceOf('BrokunMusheen\FSGallery\Image', $album_content['img-3']['orig']);
    }

    /**
     * @test
     *
     * @covers Album::getHeaderImage
     */
    public function it_can_have_a_header_image()
    {
        $album_path = 'public/gallery/foo/bar';
        $album = $this->buildAlbum($album_path);

        $this->assertInstanceOf('BrokunMusheen\FSGallery\Image', $album->getHeaderImage());
    }
    
    /**
     * @test
     *
     * @covers Album::isArchived
     */
    public function it_can_be_archived()
    {
        $unarchived_album = $this->buildAlbum('public/gallery/foo/bar');
        $this->assertFalse($unarchived_album->isArchived());
        
        $archived_album = $this->buildAlbum('public/gallery/foo/_boo');
        $this->assertTrue($archived_album->isArchived());
    }
    
    /**
     * @test
     *
     * @covers Album::isSticky
     */
    public function it_can_be_sticky()
    {
        $sticky_album = $this->buildAlbum('public/gallery/foo/featured_s');
        $this->assertTrue($sticky_album->isSticky());

        $non_sticky_album = $this->buildAlbum('public/gallery/foo/_boo');
        $this->assertFalse($non_sticky_album->isSticky());
    }
    
    /**
     * @test
     *
     * @covers Album::getMetaInfo
     */
    public function it_can_have_meta_info()
    {
        $this->createMetaFile(vfsStream::url('public/gallery/foo/bar'), 'test', '100000');

        $album = $this->buildAlbum('public/gallery/foo/bar');
        $this->assertInstanceOf('BrokunMusheen\FSGallery\MetaFile', $album->getMetaInfo());


        // A new meta info file is created if it doesn't already exist

        $album = $this->buildAlbum('public/gallery/foo/baz');
        $this->assertFileNotExists('vfs://public/gallery/foo/baz/.meta.info');
        
        $this->assertInstanceOf('BrokunMusheen\FSGallery\MetaFile', $album->getMetaInfo());
        $this->assertFileExists('vfs://public/gallery/foo/baz/.meta.info');
    }
    
    /**
     * @test
     *
     * @covers Album::getLeaves
     */
    public function it_can_return_the_leaf_albums()
    {
        $album = $this->buildAlbum('public/gallery/foo');

        $this->assertEquals(4, count($album->getLeaves()));
        
        
        // Archived leaf albums can also be collected
        
        $this->assertEquals(7, count($album->getLeaves(true)));
    }

    /**
     * @test
     *
     * @covers Album::getRealPath
     */
    public function it_has_an_absolute_file_path()
    {
        $album = $this->buildAlbum('public/gallery/foo');
        $this->assertEquals('vfs://public/gallery/foo', $album->getRealPath());
    }

    /**
     * @test
     *
     * @covers Album::getParent
     */
    public function it_can_have_a_parent_album()
    {
        $album = $this->buildAlbum('public/gallery/foo');
        $this->assertEquals('foo', $album->getPath());
    }
    
    /**
     * @test
     *
     * @covers Album::getParent
     */
    public function it_has_a_relative_file_path()
    {
        $album = $this->buildAlbum('public/gallery/foo');
        $this->assertInstanceOf('BrokunMusheen\FSGallery\Album', $album->getParent());
        $this->assertEquals('gallery', $album->getParent()->getName());
        
        
        // Some Albums have no parent
        
        $gallery = new Gallery;
        $this->assertNull($gallery->getBaseAlbum()->getParent());
    }
    
    /**
     * @test
     *
     * @covers Album::getTitle
     */
    public function it_has_a_title()
    {
        $album = $this->buildAlbum('public/gallery/foo/_boo');
        $this->assertEquals('boo', $album->getTitle());
    }
    
    /**
     * @test
     *
     * @covers Album::hasSubAlbums
     */
    public function it_reports_if_it_has_sub_albums()
    {
        $album = $this->buildAlbum('public/gallery/foo');
        $this->assertTrue($album->hasSubAlbums());

        $album = $this->buildAlbum('public/gallery/foo/all-archived');
        $this->assertFalse($album->hasSubAlbums());
        
        
        // There is an option to include archived albums
        
        $album = $this->buildAlbum('public/gallery/foo/all-archived');
        $this->assertTrue($album->hasSubAlbums(true));
    }
    
    /**
     * @test
     *
     * @covers Album::hasContent
     */
    public function it_reports_if_it_has_content()
    {
        $album = $this->buildAlbum('public/gallery/foo');
        $this->assertFalse($album->hasContent());
        
        $album = $this->buildAlbum('public/gallery/foo/bar');
        $this->assertTrue($album->hasContent());
    }

    /**
     * @test
     *
     * @covers Album::hasImagery
     */
    public function it_reports_if_it_has_imagery()
    {
        $album = $this->buildAlbum('public/gallery/foo/_boo');
        $this->assertTrue($album->hasImagery());

        $album = $this->buildAlbum('public/gallery/foo/featured_s');
        $this->assertFalse($album->hasImagery());
    }
    
    /**
     * @test
     *
     * @covers Album::hasVideo
     */
    public function it_reports_if_it_has_video()
    {
        $album = $this->buildAlbum('public/gallery/foo/_boo');
        $this->assertTrue($album->hasVideo());
        
        $album = $this->buildAlbum('public/gallery/foo/baz/bazzy');
        $this->assertFalse($album->hasVideo());
    }

    /**
     * @test
     *
     * @covers Album::albumWalk
     */
    public function it_can_execute_a_function_within_all_albums_in_a_tree()
    {
        $count = 0;
        $count_albums = function(Album $current_album) use (&$count) { $count++; };
        
        $album = $this->buildAlbum('public/gallery');
        $album->albumWalk($count_albums);
        
        $this->assertEquals(8, $count);
        
        
        // There is also an option to include archived folders
        
        $count = 0;
        $album->albumWalk($count_albums, true);
        
        $this->assertEquals(11, $count);
    }

    /**
     * @test
     *
     * @covers Album::albumWalk
     */
    public function it_can_clear_an_existing_album_id()
    {
        $album = $this->buildAlbum('public/gallery/foo');
        
        $this->createMetaFile(vfsStream::url('public/gallery/foo'), 'nope', '10000');
        
        $this->assertFileExists($album->getMetaFilePath());
        
        $album->unsetId();
        
        $this->assertFileNotExists($album->getMetaFilePath());
    }
        

    /**
     * @param  string  $album_path
     * @param  string  $description
     */
    protected function createDescription($album_path, $description)
    {
        file_put_contents($album_path . '_caption.txt', $description);
    }
}
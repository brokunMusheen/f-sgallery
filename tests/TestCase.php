<?php

require_once __DIR__ . '/stubs/config.php';

use BrokunMusheen\FSGallery\Album;
use BrokunMusheen\FSGallery\Gallery;

use org\bovigo\vfs\vfsStream;

class TestCase extends PHPUnit_Framework_TestCase
{
    protected $gallery_root;

    public function setup()
    {
        ini_set('error_reporting', E_ALL);
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');

        $this->setupDirectory();
    }

    /**
     * @param  string  $directory
     * @param  string  $album_id
     * @param  string  $created_at
     */
    protected function createMetaFile($directory, $album_id, $created_at)
    {
        $album_meta_data = json_encode([
            'id' => $album_id,
            'created_at' => $created_at,
        ]);

        file_put_contents($directory . '/.meta.info', $album_meta_data);
    }

    protected function setupDirectory()
    {
        $this->gallery_root = vfsStream::setup('public', null, [
            'index_store' => [],
            'gallery' => [
                'foo' => [
                    'bar' => [
                        'header.jpg' => 'image_file',
                        'img-1.jpg' => 'image_file',
                        'img-1_thumb.jpg' => 'image_file',
                        'img-2.jpg' => 'image_file',
                        'img-3.jpg' => 'image_file',
                        'img-3_thumb.jpg' => 'image_file',
                    ],
                    'baz' => [
                        'bazzy' => [
                            'photo-1.jpg' => 'image_file',
                            'photo-2.jpg' => 'image_file',
                            '_photo-3.jpg' => 'image_file',
                            'photo-3_thumb.jpg' => 'image_file',
                        ],

                        'bazzybazz' => [
                            'photo-1.jpg' => 'image_file',
                            '2_photo-2.jpg' => 'image_file',
                            '1_photo-3.jpg' => 'image_file',
                            'photo-3_thumb.jpg' => 'image_file',
                        ],
                    ],
                    '_boo' => [
                        'photo-a.jpg' => 'image_file',
                        'photo-b.jpg' => 'image_file',
                        'photo-c.jpg' => 'image_file',
                        'sample-vid-1.mp4' => 'video_file',
                    ],
                    'featured_s' => [
                        'preview-a.mp4' => 'video_file',
                        'preview-b.mp4' => 'video_file',
                        'preview-c.mp4' => 'video_file',
                    ],
                    'all-archived' => [
                        '_bazzy' => [
                            'photo-1.jpg' => 'image_file',
                            'photo-2.jpg' => 'image_file',
                            'photo-3.jpg' => 'image_file',
                            'photo-3_thumb.jpg' => 'image_file',
                        ],

                        '_bazzybazz' => [
                            'photo-1.jpg' => 'image_file',
                            'photo-2.jpg' => 'image_file',
                            'photo-3.jpg' => 'image_file',
                            'photo-3_thumb.jpg' => 'image_file',
                        ],
                    ],
                ],
            ],
        ]);

        \Config::set('f-sgallery::config.gallery_file_path', vfsStream::url('public/gallery'));
        \Config::set('f-sgallery::config.gallery_data_dir', vfsStream::url('public/index_store'));
    }

    /**
     * @param  string  $album_path
     * @return  Album
     */
    protected function buildAlbum($album_path)
    {
        $gallery = new Gallery;
        return new Album(vfsStream::url($album_path), $gallery);
    }
}

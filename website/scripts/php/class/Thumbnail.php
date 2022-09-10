<?php

namespace PhotoDatabase;

use Imagick;
use ImagickException;


/**
 * Class Thumbnail
 * @package PhotoDatabase
 */
class Thumbnail
{
    public int $width = 270;   // 1.5 * 180

    /**
     * Creates a thumbnail from provided image.
     * Creates a new image with given width and keeping the aspect ratio and applying an unsharp mask.
     * @param string $srcPath path to source image
     * @param string $filename path of new image to create
     * @param integer $newWidth with of thumbnail to create
     * @throws ImagickException
     */
    public function create(string $srcPath, string $filename, int $newWidth): void
    {
        $img = new Imagick($srcPath);
        $img->thumbnailImage($newWidth, $newWidth, true);
        //$img->resizeImage($newWidth, $newWidth, Imagick::FILTER_LANCZOS, 0.5, true);
        //$img->writeImage($filename);    // --> write permission error!?
        file_put_contents ($filename, $img); // works, or:
    }
}
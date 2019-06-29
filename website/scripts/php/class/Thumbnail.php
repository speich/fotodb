<?php

namespace PhotoDatabase;

use RuntimeException;


/**
 * Class Thumbnail
 * @package PhotoDatabase
 */
class Thumbnail
{

    /**
     * Creates a thumbnail from provided image.
     * Creates a new image with given width and keeping the aspect ratio and applying an unsharp mask.
     * @param string $srcPath path to source image
     * @param string $filename path of new image to create
     * @param integer $newWidth with of thumbnail to create
     */
    public function create($srcPath, $filename, $newWidth)
    {
        $img = imagecreatefromjpeg($srcPath);

        // calculate thumbnail height
        $width = imagesx($img);
        $height = imagesy($img);
        if ($width > $height) {
            $newHeight = floor($height * $newWidth / $width);
        } else {
            $newHeight = $newWidth;
            $newWidth = floor($width * $newHeight / $height);
        }
        // create new image and save it
        $tmpImg = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($tmpImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        $tmpImg = $this->unsharpMask($tmpImg, 60, 0.8, 0);
        $succ = imagejpeg($tmpImg, $filename, 85);
        imagedestroy($img);
        imagedestroy($tmpImg);

        if (!$succ) {
            throw new RuntimeException('could not create thumbnail');
        }
    }

    /**
     * Image hast to be already created with imgcreatetruecolor.
     * @see http://vikjavev.no/computing/ump.php
     * @param resource $img
     * @param int $amount
     * @param float $radius
     * @param int $threshold
     * @return
     */
    public function unsharpMask($img, $amount, $radius, $threshold)
    {
        /* Unsharp Mask for PHP - version 2.1.1
            Unsharp mask algorithm by Torstein Hønsi 2003-07.
            thoensi_at_netcom_dot_no. */

        // Attempt to calibrate the parameters to Photoshop:
        if ($amount > 500) {
            $amount = 500;
        }
        $amount = $amount * 0.016;
        if ($radius > 50) {
            $radius = 50;
        }
        $radius = $radius * 2;
        if ($threshold > 255) {
            $threshold = 255;
        }
        $radius = abs(round($radius)); // Only integers make sense.
        if ($radius == 0) {
            return false;
        }
        $w = imagesx($img);
        $h = imagesy($img);
        $imgCanvas = imagecreatetruecolor($w, $h);
        $imgBlur = imagecreatetruecolor($w, $h);

        /* Gaussian blur matrix:
                1  2  1
                2  4  2
                1  2  1
        */
        $matrix = [[1, 2, 1], [2, 4, 2], [1, 2, 1]];
        imagecopy($imgBlur, $img, 0, 0, 0, 0, $w, $h);
        imageconvolution($imgBlur, $matrix, 16, 0);
        if ($threshold > 0) {
            // Calculate the difference between the blurred pixels and the original
            // and set the pixels
            for ($x = 0; $x < $w - 1; $x++) { // each row
                for ($y = 0; $y < $h; $y++) { // each pixel
                    $rgbOrig = ImageColorAt($img, $x, $y);
                    $rOrig = (($rgbOrig >> 16) & 0xFF);
                    $gOrig = (($rgbOrig >> 8) & 0xFF);
                    $bOrig = ($rgbOrig & 0xFF);

                    $rgbBlur = ImageColorAt($imgBlur, $x, $y);
                    $rBlur = (($rgbBlur >> 16) & 0xFF);
                    $gBlur = (($rgbBlur >> 8) & 0xFF);
                    $bBlur = ($rgbBlur & 0xFF);

                    // When the masked pixels differ less from the original
                    // than the threshold specifies, they are set to their original value.
                    $rNew = (abs($rOrig - $rBlur) >= $threshold) ? max(0, min(255, ($amount * ($rOrig - $rBlur)) + $rOrig)) : $rOrig;
                    $gNew = (abs($gOrig - $gBlur) >= $threshold) ? max(0, min(255, ($amount * ($gOrig - $gBlur)) + $gOrig)) : $gOrig;
                    $bNew = (abs($bOrig - $bBlur) >= $threshold) ? max(0, min(255, ($amount * ($bOrig - $bBlur)) + $bOrig)) : $bOrig;

                    if (($rOrig != $rNew) || ($gOrig != $gNew) || ($bOrig != $bNew)) {
                        $pixCol = ImageColorAllocate($img, $rNew, $gNew, $bNew);
                        ImageSetPixel($img, $x, $y, $pixCol);
                    }
                }
            }
        } else {
            for ($x = 0; $x < $w; $x++) { // each row
                for ($y = 0; $y < $h; $y++) { // each pixel
                    $rgbOrig = ImageColorAt($img, $x, $y);
                    $rOrig = (($rgbOrig >> 16) & 0xFF);
                    $gOrig = (($rgbOrig >> 8) & 0xFF);
                    $bOrig = ($rgbOrig & 0xFF);

                    $rgbBlur = ImageColorAt($imgBlur, $x, $y);
                    $rBlur = (($rgbBlur >> 16) & 0xFF);
                    $gBlur = (($rgbBlur >> 8) & 0xFF);
                    $bBlur = ($rgbBlur & 0xFF);

                    $rNew = ($amount * ($rOrig - $rBlur)) + $rOrig;
                    if ($rNew > 255) {
                        $rNew = 255;
                    } elseif ($rNew < 0) {
                        $rNew = 0;
                    }
                    $gNew = ($amount * ($gOrig - $gBlur)) + $gOrig;
                    if ($gNew > 255) {
                        $gNew = 255;
                    } elseif ($gNew < 0) {
                        $gNew = 0;
                    }
                    $bNew = ($amount * ($bOrig - $bBlur)) + $bOrig;
                    if ($bNew > 255) {
                        $bNew = 255;
                    } elseif ($bNew < 0) {
                        $bNew = 0;
                    }
                    $rgbNew = ($rNew << 16) + ($gNew << 8) + $bNew;
                    ImageSetPixel($img, $x, $y, $rgbNew);
                }
            }
        }
        imagedestroy($imgCanvas);
        imagedestroy($imgBlur);

        return $img;
    }
}
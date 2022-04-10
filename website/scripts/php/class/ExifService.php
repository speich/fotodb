<?php

namespace PhotoDatabase;

use Exiftool\Exceptions\ExifToolBatchException;
use Exiftool\ExifToolBatch;
use PhotoDatabase\Iterator\FileInfoImage;
use function count;


/**
 * Class ExifService
 * @package PhotoDatabase
 */
class ExifService
{
    /** return only xmp data */
    public const FETCH_XMP = 1;

    /** return only exif data */
    public const FETCH_EXIF = 2;

    /** return both xmp and exif data */
    public const FETCH_BOTH = 3;

    private string $exiftool;

    private string $exiftoolParams = '-c "%+.8f" -g -s2 -j';

    /**
     * ExifService constructor.
     * @param string $path
     * @param string $lang
     */
    public function __construct(string $path, string $lang = 'en')
    {
        $this->exiftool = $path;
        $this->exiftoolParams .= ' -lang '.$lang;    // TODO: check input which is passed as an argument to exiftool
    }

    public function checkForUpdate(): void
    {
        // TODO: exiftool provides an rss feed I could use to automatically update
        // http://owl.phy.queensu.ca/~phil/exiftool/rss.xml
        // check element: <lastBuildDate>Mon, 13 Jun 2016 09:30:11 -0400</lastBuildDate>
    }

    /**
     * Returns exif data from raw and xmp as a multidimensional array.
     * Calls the exif tool for NEF and xmp
     * @param string $img full path of image
     * @return false|array mixed
     */
    public function getData(string $img): false|array
    {
        $imgInfo = new FileInfoImage($img);
        $extRaw = $imgInfo->getRealPathRaw();
        $extXmp = $imgInfo->getRealPathXmp();
        $files = [];

        $exifService = ExifToolBatch::getInstance($this->exiftool.'/exiftool', $this->exiftoolParams);
        if ($extRaw !== null) {
            $files[] = $extRaw;
        }
        if ($extXmp !== null) {
            $files[] = $extXmp;
        }
        $exifService?->add($files);
        try {
            $data = $exifService?->fetchAllDecoded(true);
        } catch (ExifToolBatchException $exception) {
            $data = false;
        }

        // merge xmp and exif arrays into one without overwriting $data[0]['File'] of NEF and of $data[1]['File'] XMP
        if ($data && count($data) > 0) {
            $files[0] = $data[0]['File'];
            $files[1] = $data[1]['File'];
            unset($data[0]['File'], $data[1]['File']);
            $data = array_merge($data[0], $data[1]);
            $data['Files'] = $files;
        }

        return $data;
    }

    /**
     * Print exif data as a HTML table.
     * @param array $data array returned from exiftool
     * @return string HTML table
     */
    public function render(array $data): string
    {
        $str = '<table class="exifData">';
        foreach ($data as $heading => $group) {
            $str .= '<tbody>';
            if (is_array($group)) {
                ksort($group);
                if ($heading === 'Files') {
                    $str .= $this->renderFiles($group);
                } else {
                    $str .= '<tr><th scope="rowgroup" colspan="2">'.$heading.'</th></tr>';
                    foreach ($group as $key => $item) {
                        $str .= '<tr><th scope="row">'.$key.'</th><td>'.(is_array($item) ? implode(',',
                                $item) : $item).'</td></tr>';
                    }
                }
            } else {
                $str .= '<tr><th scope="row">'.$heading.'</th><td>'.$group.'</td></tr>';
            }
            $str .= '</tbody>';
        }
        $str .= '</table>';

        return $str;
    }

    /**
     * @param array $files
     * @return string HTML
     */
    public function renderFiles(array $files): string
    {
        $str = '';
        foreach ($files as $file) {
            $str .= '<tr><th scope="rowgroup" colspan="2">File '.$file['FileType'].'</th></tr>';
            foreach ($file as $key => $item) {
                $str .= '<tr><th scope="row">'.$key.'</th><td>'.$item.'</td></tr>';
            }
        }

        return $str;
    }
}
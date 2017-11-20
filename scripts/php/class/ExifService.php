<?php
namespace PhotoDatabase;

class ExifService
{
    /** return only xmp data */
    const FETCH_XMP = 1;

    /** return only exif data */
    const FETCH_EXIF = 2;

    /** return both xmp and exif data */
    const FETCH_BOTH = 3;

    private $exiftool = '/cgi-bin/Image-ExifTool-10.24/exiftool';
    private $exiftoolParams = '-c "%+.8f" -g -s2 -j';
    //private $exiftoolParams = '-config /cgi-bin/.ExifTool_config -c "%+.8f" -g -s2 -j';  // config does not work, wrong path?
    //private $exiftoolParams = '-G';

    /**
     * ExifService constructor.
     * @param string $lang
     */
    public function __construct($lang = 'en')
    {
        $this->exiftoolParams .= ' -lang '.$lang;    // TODO: check input which is passed as an argument to exiftool
    }

    public function checkForUpdate()
    {
        // TODO: exiftool provides an rss feed I could use to automatically update
        // http://owl.phy.queensu.ca/~phil/exiftool/rss.xml
        // check element: <lastBuildDate>Mon, 13 Jun 2016 09:30:11 -0400</lastBuildDate>
    }

    /**
     * Checks if a XMP sidecar file exists.
     * @param $img
     * @return bool
     */
    public function hasSeparateXmpFile($img)
    {
        $imageNoExt = substr($img, 0, strrpos($img, '.'));   // remove file extension

        return file_exists($imageNoExt.'.'.'xmp');
    }

    /**
     * Returns exif data as a multidimensional array.
     * Calls the exif tool for NEF and xmp
     * @param string $img full path of image
     * @return array mixed
     */
    public function getData($img)
    {
        // TODO: make this work with other than NEF by using ExifService::originalImageExists
        // TODO: split into getExif and getXmp. Also use spl FileSystemInfo?
        $imageNoExt = substr($img, 0, strrpos($img, '.'));   // remove file extension
        $tool = __DIR__.'/../../..'.$this->exiftool.' '.$this->exiftoolParams;
        exec($tool.' '.$imageNoExt.'.NEF', $data);
        $files = [];
        if (count($data) > 0) {
            $data = implode('', $data);
            $data1 = json_decode($data, true);
            $data1 = array_pop($data1);
            $files[0] = $data1['File']; // $data1['File'] NEF would be overwritten by $data2['File'] XMP
            unset($data1['File']);
            $data2 = [];
            if ($this->hasSeparateXmpFile($img)) {
                exec($tool.' '.$imageNoExt.'.xmp', $data);   // note: exec appends the data
                $data = implode('', $data);
                $data2 = json_decode($data, true);
                $data2 = array_pop($data2);
                $files[1] = $data2['File'];
                unset($data2['File']);
            }

            $data = array_merge($data1, $data2);
            $data['Files'] = $files;

            return $data;
        } else {
            return $data;
        }
    }

    public function getExif($img)
    {
        // TODO
        $imageNoExt = substr($img, 0, strrpos($img, '.'));   // remove file extension
        $tool = __DIR__.'/../../..'.$this->exiftool.' '.$this->exiftoolParams;
        exec($tool.' '.$imageNoExt.'.NEF', $data);
        $files = [];
        if (count($data) > 0) {
            $data = implode('', $data);
            $data1 = json_decode($data, true);
            $data1 = array_pop($data1);
            $files[0] = $data1['File']; // $data1['File'] NEF would be overwritten by $data2['File'] XMP
            unset($data1['File']);
        }
    }

    public function getXmp() {
        // TODO
    }

    /**
     * Print exif data as a HTML table.
     * @param array $data array returned from exiftool
     * @return string HTML table
     */
    public function render($data)
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
    public function renderFiles($files)
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
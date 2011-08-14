<?php
/**
 * Klutz Image Class
 *
 * @author Marcus I. Ryan <marcus@riboflavin.net>
 * @package Klutz
 */
class Klutz_Image
{
    /**
     * The name of the file the image is stored in (if it's stored locally)
     *
     * @var string
     */
    var $file = null;

    /**
     * The image data itself (binary)
     *
     * @var string
     */
    var $data = null;

    /**
     * The height of the image in pixels
     *
     * @var integer
     */
    var $height = null;

    /**
     * The width of the image in pixels
     *
     * @var integer
     */
    var $width = null;

    /**
     * The mime type of the image
     *
     * @var string
     */
    var $type = null;

    /**
     * The attributes to use in an <img> tag to define the size
     * (e.g. height="120" width="400")
     *
     * @var string
     */
    var $size = null;

    /**
     * The last modified time of the file (only used if file is local).
     *
     * @var integer
     */
    var $lastmodified = 0;

    /**
     * Constructor - Based on the information passed, loads an image,
     *  determines the size and type, etc., and stores the information in
     *  the various public properties.  Any optional parameters not passed
     *  in are calculated to the best of our ability.
     *
     * @param string $image    Either raw image data or a filename
     * @param string $type     Image MIME type (e.g. image/jpeg)
     * @param integer $height  Height of the image in pixels
     * @param integer $width   Width of the image in pixels
     */
    function Klutz_Image($image, $type = null, $height = null, $width = null)
    {
        $argc = 1;
        if (!is_null($height)) {
            $argc++;
            $this->height = $height;
        }
        if (!is_null($width)) {
            $argc++;
            $this->width = $width;
        }
        if (!is_null($type)) {
            $argc++;
            $this->type = $type;
        }

        $image_info = @getimagesize($image);

        // if $image_info is false, then $image doesn't point to a file name
        if ($image_info === false) {
            $this->data = $image;

            // If we need to use getimagesize and we were passed data
            // write it to a tempfile so getimagesize will work.
            if ($argc < 4) {
                $tmpfile = Horde::getTempFile('klutz');
                $fp = fopen($tmpfile, 'wb+');
                fwrite($fp, $image);
                fclose($fp);
                $image_info = @getimagesize($tmpfile);

                // if $image_info is false, it's an invalid image...
                if ($image_info === false) {
                    return null;
                }
            }
        } else {
            $this->file = $image;
            $this->lastmodified = filemtime($this->file);
            $this->data = file_get_contents($image);
        }

        if (is_null($this->height)) {
            $this->height = $image_info[KLUTZ_FLD_HEIGHT];
        }
        if (is_null($this->width)) {
            $this->width = $image_info[KLUTZ_FLD_WIDTH];
        }
        if (is_null($this->type)) {
            global $klutz;
            $this->type = $klutz->image_types[$image_info[KLUTZ_FLD_TYPE]];
        }

        $this->size = '';
        if (!empty($this->height)) {
            $this->size = ' height="' . $this->height . '"';
        }
        if (!empty($this->width)) {
            $this->size .= ' width="' . $this->width . '"';
        }
    }

}

<?php
/**
 * Exif driver for Horde_Image utilizing PHP's compiled-in exif functions
 *
 */
class Horde_Image_Exif_Php extends Horde_Image_Exif_Base
{
    public function getData($image)
    {
        $exif = @exif_read_data($image, 0, false);

        return $this->_processData($exif);
    }

}

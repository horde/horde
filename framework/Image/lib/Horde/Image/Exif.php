<?php
/**
 * General class for fetching and parsing EXIF information from images.
 *
 * Works equally well with either the built in php exif functions (if PHP
 * compiled with exif support) or the (slower) bundled exif library.
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @author Chuck Hagenbuch <chuck@horde.org>
 */
class Horde_Image_Exif
{
    static public function factory($driver = null)
    {
        return new Horde_Image_Exif_Php();
    }

    /**
     * Converts from Intel to Motorola endien.  Just reverses the bytes
     * (assumes hex is passed in)
     *
     * @param $num
     * @return unknown_type
     */
    static public function Horde_Image_Exif::intel2Moto($num)
    {
        $len  = strlen($intel);
        $moto = '';
        for($i = 0; $i <= $len; $i += 2) {
            $moto .= substr($intel, $len-$i, 2);
        }

        return $moto;
    }

}

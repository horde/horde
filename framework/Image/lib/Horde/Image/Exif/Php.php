<?php
/**
 * Exif driver for Horde_Image utilizing PHP's compiled-in exif functions
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Image
 */
class Horde_Image_Exif_Php extends Horde_Image_Exif_Base
{
    public function getData($image)
    {
        return $this->_processData(@exif_read_data($image, 0, false));
    }

    public function supportedCategories()
    {
        return array('EXIF');
    }
}

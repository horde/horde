<?php
/**
 * Imagick driver for the Horde_Image API
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_Image
 */
class Horde_Image_imagick
{
    public function __construct($params, $context = array())
    {
        parent::__construct($params, $context);
    }
}
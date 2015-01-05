<?php
/**
 * View helper class to allow access to the Horde_Themes_Image::tag() method.
 *
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_View_Helper_Image extends Horde_View_Helper_Base
{
    /**
     * Wrapper around the Horde_Themes_Image::tag() method.
     *
     * @see Horde_Themes_Image::tag()
     */
    public function hordeImage($src, $alt = '', $attr = '')
    {
        return Horde_Themes_Image::tag($src, array(
            'alt' => $alt,
            'attr' => $attr
        ));
    }

}

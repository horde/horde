<?php
/**
 * Copyright 2003-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2003-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * Provides methods for generating a country flag image.
 *
 * Copyright 2003-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2003-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */
class Horde_Core_Ui_FlagImage
{
    /**
     * Generate a flag image tag.
     *
     * @param string $host  The hostname.
     *
     * @return string  An HTML IMG tag (or empty if host is not found).
     */
    static public function generateFlagImageByHost($host)
    {
        $data = Horde_Nls::getCountryByHost($host, empty($GLOBALS['conf']['geoip']['datafile']) ? null : $GLOBALS['conf']['geoip']['datafile']);
        if ($data === false) {
            return '';
        }

        $img = Horde_Themes_Image::tag('flags/' . $data['code'] . '.png', array(
            'alt' => $data['name'],
            'attr' => array('title' => $data['name'])
        ));

        return $img
            ? $img
            : '[' . $data['name'] . ']';
    }

}

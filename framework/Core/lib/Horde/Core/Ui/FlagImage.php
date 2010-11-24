<?php
/**
 * The Horde_Core_Ui_FlagImage:: class provides a widget for linking to a flag
 * image.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Core
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

        $img = strval(Horde::img('flags/' . $data['code'] . '.png', $data['name'], array('title' => $data['name'])));

        return $img
            ? $img
            : '[' . $data['name'] . ']';
    }

}

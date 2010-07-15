<?php
/**
 * The Horde_Ui_FlagImage:: class provides a widget for linking to a flag
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
 * @package  Ui
 */
class Horde_Ui_FlagImage
{
    /**
     * Render the language selection.
     *
     * @param boolean $form  Return the selection box as a complete standalone
     *                       form.
     *
     * @return string  The HTML selection box.
     */
    static public function generateFlagImageByHost($host)
    {
        $data = Horde_Nls::getCountryByHost($host, empty($GLOBALS['conf']['geoip']['datafile']) ? null : $GLOBALS['conf']['geoip']['datafile']);
        if ($data === false) {
            return '';
        }

        $img = $data['code'] . '.png';
        return file_exists($GLOBALS['registry']->get('themesfs', 'horde') . '/graphics/flags/' . $img)
            ? Horde::img('flags/' . $img, $data['name'], array('title' => $data['name']))
            : '[' . $data['name'] . ']';
    }

}

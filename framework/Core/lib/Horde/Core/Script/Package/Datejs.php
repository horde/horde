<?php
/**
 * This class identifies the javascript necessary to output the date.js
 * javascript code to the browser.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Script_Package_Datejs extends Horde_Script_Package
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $datejs = str_replace('_', '-', $GLOBALS['language']) . '.js';
        if (!file_exists($GLOBALS['registry']->get('jsfs', 'horde') . '/date/' . $datejs)) {
            $datejs = 'en-US.js';
        }
        $this->_files[] = new Horde_Script_File_JsDir('date/' . $datejs, 'horde');
        $this->_files[] = new Horde_Script_File_JsDir('date/date.js', 'horde');
    }

    /**
     * Translates date format strings from strftime to datejs.
     *
     * @param string $format  A date format string in strftime syntax.
     *
     * @return string  The date format string in datejs format.
     */
    public function translateFormat($format)
    {
        $from = array('%e', '%d', '%a', '%A', '%m', '%h', '%b', '%B', '%y', '%Y', '%x');
        $to = array('d', 'dd', 'ddd', 'dddd', 'MM', 'MMM', 'MMM', 'MMMM', 'yy', 'yyyy');
        $to[] = str_replace($from, $to, Horde_Nls::getLangInfo(D_FMT));
        return str_replace($from, $to, $format);
    }
}

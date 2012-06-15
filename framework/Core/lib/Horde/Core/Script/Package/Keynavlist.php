<?php
/**
 * This class identifies the javascript necessary to output the keyboard
 * navigation list widget javascript code to the browser.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Script_Package_Keynavlist extends Horde_Script_Package
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_files[] = new Horde_Script_File_JsDir('keynavlist.js', 'horde');
        $this->_files[] = new Horde_Script_File_JsDir('scriptaculous/effects.js', 'horde');
    }

}

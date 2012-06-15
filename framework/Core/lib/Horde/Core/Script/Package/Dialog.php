<?php
/**
 * This class identifies the javascript necessary to output the dialog
 * javascript code to the browser.
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
class Horde_Core_Script_Package_Dialog extends Horde_Script_Package
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $ob = new Horde_Script_File_JsDir('dialog.js', 'horde');
        $ob->jsvars = array(
            'HordeDialog.cancel_text' => Horde_Core_Translation::t("Cancel"),
            'HordeDialog.ok_text' => Horde_Core_Translation::t("OK")
        );
        $this->_files[] = $ob;

        $this->_files[] = new Horde_Script_File_JsDir('redbox.js', 'horde');
        $this->_files[] = new Horde_Script_File_JsDir('scriptaculous/effects.js', 'horde');
    }

}

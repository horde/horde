<?php
/**
 * This class identifies the javascript necessary to output the popup
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
class Horde_Core_Script_Package_Popup extends Horde_Script_Package
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $ob = new Horde_Script_File_JsDir('popup.js', 'horde');
        $ob->jsvars = array(
            'HordePopup.popup_block_text' => Horde_Core_Translation::t("A popup window could not be opened. Your browser may be blocking popups.")
        );

        $this->_files[] = $ob;
    }

}

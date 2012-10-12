<?php
/**
 * This class identifies the javascript necessary to output the IMP_JS
 * javascript script to the browser.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Script_Package_Imp extends Horde_Script_Package
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $ob = new Horde_Script_File_JsDir('imp.js', 'imp');
        $ob->jsvars = array(
            'IMP_JS.unblock_image_text' => _("Click to always show images from this sender.")
        );
        $this->_files[] = $ob;
    }

}

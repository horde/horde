<?php
/**
 * This class identifies the javascript necessary to output the ImpComposeBase
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
class IMP_Script_Package_ComposeBase extends Horde_Script_Package
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $ob = new Horde_Script_File_JsDir('compose-base.js', 'imp');
        $ob->jsvars = array(
            'ImpComposeBase.pastehtml' => _("Pasting non-text elements is not supported.")
        );
        $this->_files[] = $ob;
    }

}

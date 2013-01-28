<?php
/**
 * This class identifies the javascript necessary to output the ImpComposeBase
 * javascript script to the browser.
 *
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
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
        global $page_output, $registry, $session;

        $this->_files[] = new Horde_Script_File_JsDir('compose-base.js', 'imp');

        if ($session->get('imp', 'rteavail')) {
            switch ($registry->getView()) {
            case $registry::VIEW_DYNAMIC:
                $plugin = 'pasteattachment';
                break;

            default:
                $plugin = 'pasteignore';
                break;
            }

            $js = new Horde_Script_File_JsDir('ckeditor/' . $plugin . '.js', 'imp');
            $page_output->addInlineScript(array(
                'CKEDITOR.on("loaded", function(e) {' .
                  'CKEDITOR.plugins.addExternal("' . $plugin . '", "' . $js->url->url . '", "");' .
                  'CKEDITOR.config.extraPlugins = CKEDITOR.config.extraPlugins.split(",").concat("' . $plugin . '").join(",");' .
                '});'
            ), true);
        }
    }

}

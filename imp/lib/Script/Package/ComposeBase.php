<?php
/**
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * This class identifies the javascript necessary to output the ImpComposeBase
 * javascript script to the browser.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
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
                $this->_files[] = new Horde_Script_File_JsDir('ckeditor/imageupload.js', 'imp');
                $this->_files[] = new Horde_Script_File_JsDir('ckeditor/imagepoll.js', 'imp');
                $page_output->addInlineJsVars(array(
                    'IMP_Ckeditor_Imagepoll.related_attr' => IMP_Compose::RELATED_ATTR
                ));

                $plugin = 'pasteattachment';
                $upload_url = $registry->getServiceLink('ajax', 'imp')->url . 'addAttachmentCkeditor';
                break;

            default:
                $plugin = 'pasteignore';
                $upload_url = '';
                break;
            }

            $js = new Horde_Script_File_JsDir('ckeditor/' . $plugin . '.js', 'imp');
            $page_output->addInlineScript(array(
                'if (window.CKEDITOR) { CKEDITOR.on("loaded", function(e) {' .
                  'CKEDITOR.plugins.addExternal("' . $plugin . '", "' . $js->url->url . '", "");' .
                  'CKEDITOR.config.filebrowserImageUploadUrl = "' . $upload_url . '";' .
                '}); };'
            ), true);
        }
    }

}

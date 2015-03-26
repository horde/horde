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
 * This class identifies the javascript necessary to output the compose
 * javascript script to the browser.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Script_Package_Compose extends Horde_Script_Package
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        global $page_output, $prefs, $registry;

        $this->_files[] = new Horde_Script_File_JsDir('compose.js', 'imp');
        $this->_files[] = new Horde_Script_File_JsDir('draghandler.js', 'imp');
        $this->_files[] = new Horde_Script_File_JsDir('editor.js', 'imp');
        $this->_files[] = new Horde_Script_File_JsDir('imp.js', 'imp');

        if (!$prefs->isLocked('default_encrypt') &&
            (IMP_Pgp::enabled() || IMP_Crypt_Smime::enabled())) {
            $page_output->addScriptPackage('Horde_Core_Script_Package_Dialog');
            $this->_files[] = new Horde_Script_File_JsDir('passphrase.js', 'imp');
        }

        if (!IMP_Compose::canHtmlCompose()) {
            return;
        }

        switch ($registry->getView()) {
        case $registry::VIEW_BASIC:
        case $registry::VIEW_DYNAMIC:
            $this->_files[] = new Horde_Script_File_JsDir('ckeditor/imageupload.js', 'imp');
            $this->_files[] = new Horde_Script_File_JsDir('ckeditor/images.js', 'imp');
            $page_output->addInlineJsVars(array(
                'ImpCkeditorImgs.related_attr' => IMP_Compose::RELATED_ATTR
            ));

            $js = new Horde_Script_File_JsDir('ckeditor/pasteattachment.js', 'imp');
            $upload_url = $registry->getServiceLink('ajax', 'imp')->url . 'addAttachmentCkeditor';

            $page_output->addInlineScript(array(
                'if (window.CKEDITOR) { CKEDITOR.on("loaded", function(e) {' .
                  'CKEDITOR.plugins.addExternal("pasteattachment", "' . $js->url->url . '", "");' .
                  'CKEDITOR.config.filebrowserImageUploadUrl = "' . $upload_url . '";' .
                '}); };'
            ), true);
            break;
        }
    }

}

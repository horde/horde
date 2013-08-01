<?php
/**
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Common code relating to IMP's setup and configuration of the browser HTML
 * editor.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Editor
{
    /**
     * Initialize the Rich Text Editor (RTE).
     *
     * @param string $id  The DOM ID to load. If null, won't start editor on
     *                    page load.
     */
    public function init($id = null)
    {
        global $injector, $page_output, $language, $prefs;

        $injector->getInstance('Horde_Editor')->initialize(array(
            'basic' => true,
            'config' => 'IMP.ckeditor_config',
            'id' => $id
        ));

        $font_family = $prefs->getValue('compose_html_font_family');
        if (!$font_family) {
            $font_family = 'Arial';
        }

        $font_size = intval($prefs->getValue('compose_html_font_size'));
        $font_size = $font_size
            /* Font size should be between 8 and 24 pixels. Or else recipients
             * will hate us. Default to 14px. */
            ? min(24, max(8, $font_size)) . 'px'
            : '14px';

        $config = array(
            /* To more closely match "normal" textarea behavior, send <BR> on
             * enter instead of <P>. */
            // CKEDITOR.ENTER_BR
            'enterMode: 2',
            // CKEDITOR.ENTER_P
            'shiftEnterMode: 1',

            /* Don't load the config.js file. */
            'customConfig: ""',

            /* Disable resize of the textarea. */
            'resize_enabled: false',

            /* Disable spell check as you type. */
            'scayt_autoStartup: false',

            /* Convert HTML entities. */
            'entities: false',

            /* Set language to Horde language. */
            'language: "' . Horde_String::lower($language) . '"',

            /* Default display font. This is NOT the font used to send
             * the message, however. */
            'contentsCss: "body { font-family: ' . $font_family . '; font-size: ' . $font_size . '; }"',
            'font_defaultLabel: "' . $font_family . '"',
            'fontSize_defaultLabel: "' . $font_size . '"'
        );

        $buttons = $prefs->getValue('ckeditor_buttons');
        if (!empty($buttons)) {
            $config[] = 'toolbar: ' . $prefs->getValue('ckeditor_buttons');
        }

        $page_output->addInlineScript(array(
            'window.IMP = window.IMP || {}',
            'IMP.ckeditor_config = {' . implode(',', $config) . '}'
        ));
    }

}

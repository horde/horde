<?php
/**
 * THis class provices a place to share common code relating to IMP's
 * setup and configuration of the browser HTML editor.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Ui_Editor
{
    /**
     * Initialize the Rich Text Editor (RTE).
     *
     * @param boolean $basic  Load the basic ckeditor stub?
     * @param string $id      The DOM ID to load. If null, won't start editor
     *                        on page load.
     */
    static public function init($basic = false, $id = null)
    {
        global $injector, $language, $prefs;

        $injector->getInstance('Horde_Editor')->initialize(array(
            'basic' => $basic,
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

        Horde::addInlineScript(array(
            'window.IMP = window.IMP || {}',
            'IMP.ckeditor_config = {' . implode(',', $config) . '}'
        ));
    }

}

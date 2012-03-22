<?php
/**
 * The Horde_Mime_Viewer_Syntaxhighlighter class renders source code appropriate
 * for highlighting with http://alexgorbatchev.com/SyntaxHighlighter/.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Mime_Viewer_Syntaxhighlighter extends Horde_Mime_Viewer_Syntaxhighlighter
{
    protected static $_shLoaded = false;
    protected static $_shBrushes = array();

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _renderInline()
    {
        /* Determine the language and brush from the mime type. */
        $mimeType = $this->_mimepart->getType();
        $language = $this->_mimeTypeToLanguage($mimeType);
        $brush = $this->_languageToBrush($language);

        $page_output = $GLOBALS['injector']->getInstance('Horde_PageOutput');

        if (!self::$_shLoaded) {
            $page_output->addScriptFile('syntaxhighlighter/scripts/shCore.js', 'horde');
            $page_output->addInlineScript(array(
                'SyntaxHighlighter.defaults[\'toolbar\'] = false',
                'SyntaxHighlighter.highlight()',
            ), true);
            self::$_shLoaded = true;

            $sh_js_fs = $this->getConfigParam('registry')->get('jsfs', 'horde') . '/syntaxhighlighter/styles/';
            $sh_js_uri = Horde::url($this->getConfigParam('registry')->get('jsuri', 'horde'), false, -1) . '/syntaxhighlighter/styles/';

            $page_output->addStylesheet($sh_js_fs . 'shCoreEclipse.css', $sh_js_uri . 'shCoreEclipse.css');
            $page_output->addStylesheet($sh_js_fs . 'shThemeEclipse.css', $sh_js_uri . 'shThemeEclipse.css');
        }

        if (empty(self::$_shBrushes[$brush])) {
            $page_output->addScriptFile('syntaxhighlighter/scripts/shBrush' . $brush . '.js', 'horde');
            self::$_shBrushes[$brush] = true;
        }

        $results = '<pre class="brush: ' . $language . '; toolbar: false;">' . htmlspecialchars(Horde_String::convertCharset($this->_mimepart->getContents(), $this->_mimepart->getCharset(), $this->getConfigParam('charset')), ENT_QUOTES, $this->getConfigParam('charset')) . '</pre>';
        return $this->_renderReturn(
            $results,
            'text/html; charset=' . $this->getConfigParam('charset')
        );
    }
}

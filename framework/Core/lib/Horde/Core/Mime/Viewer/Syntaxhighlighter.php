<?php
/**
 * The Horde_Mime_Viewer_Syntaxhighlighter class renders source code appropriate
 * for highlighting with http://alexgorbatchev.com/SyntaxHighlighter/.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Mime_Viewer
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

        if (!self::$_shLoaded) {
            Horde::addScriptFile('syntaxhighlighter/scripts/shCore.js', 'horde', true);
            Horde::addInlineScript(array(
                'SyntaxHighlighter.defaults[\'toolbar\'] = false',
                'SyntaxHighlighter.all()',
            ), 'dom');
            self::$_shLoaded = true;

            $sh_js_fs = $this->getConfigParam('registry')->get('jsfs', 'horde') . '/syntaxhighlighter/styles/';
            $sh_js_uri = Horde::url($this->getConfigParam('registry')->get('jsuri', 'horde'), false, -1) . '/syntaxhighlighter/styles/';

            Horde_Themes::addStylesheetFile($sh_js_fs . 'shCoreEclipse.css', $sh_js_uri . 'shCoreEclipse.css');
            Horde_Themes::addStylesheetFile($sh_js_fs . 'shThemeEclipse.css', $sh_js_uri . 'shThemeEclipse.css');
        }

        if (empty(self::$_shBrushes[$brush])) {
            Horde::addScriptFile('syntaxhighlighter/scripts/shBrush' . $brush . '.js', 'horde', true);
            self::$_shBrushes[$brush] = true;
        }

        $results = '<pre class="brush: ' . $language . '; toolbar: false;">' . htmlspecialchars($this->_mimepart->getContents(), ENT_QUOTES, $this->getConfigParam('charset')) . '</pre>';
        return $this->_renderReturn(
            $results,
            'text/html; charset=' . $this->getConfigParam('charset')
        );
    }
}

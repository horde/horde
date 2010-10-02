<?php

require_once 'Text/Wiki/Render/Xhtml/Code.php';

/**
 * @package Wicked
 */
class Text_Wiki_Render_Xhtml_Code2 extends Text_Wiki_Render_Xhtml_Code
{
    protected $_shLoaded = false;
    protected $_shBrushes = array();

    /**
     * Renders a token into text matching the requested format.
     *
     * @param array $options The "options" portion of the token (second
     * element).
     *
     * @return string The text rendered from the token options.
     */
    function token($options)
    {
        $type = $options['attr']['type'];
        switch ($type) {
        case 'php':
        case 'htmlphp':
            $type = 'php';
            $brush = 'Php';
            break;

        case 'xml':
        case 'html':
        case 'xhtml':
        case 'xslt':
            $brush = 'Xml';
            break;

        case 'bash':
        case 'shell':
            $brush = 'Bash';
            break;

        case 'diff':
        case 'patch':
        case 'pas':
            $brush = 'Diff';
            break;

        case 'css':
            $brush = 'Css';
            break;

        case 'c':
        case 'cpp':
            $brush = 'Cpp';
            break;

        case 'java':
            $brush = 'Java';
            break;

        case 'js':
        case 'jscript':
        case 'javascript':
            $brush = 'JScript';
            break;

        default:
            return parent::token($options);
        }

        if (!$this->_shLoaded) {
            Horde::addScriptFile('syntaxhighlighter/scripts/shCore.js', 'horde', true);
            Horde::addInlineScript(array(
                'SyntaxHighlighter.defaults[\'toolbar\'] = false;',
                'SyntaxHighlighter.all();',
            ), 'dom');
            $this->_shLoaded = true;

            $sh_js_fs = $GLOBALS['injector']->getInstance('Horde_Registry')->get('jsfs', 'horde') . '/syntaxhighlighter/styles/';
            $sh_js_uri = Horde::url($GLOBALS['injector']->getInstance('Horde_Registry')->get('jsuri', 'horde'), false, -1) . '/syntaxhighlighter/styles/';
            Horde_Themes::includeStylesheetFiles(array('additional' => array(
                array('f' => $sh_js_fs . 'shCoreEclipse.css', 'u' => $sh_js_uri . 'shCoreEclipse.css'),
                array('f' => $sh_js_fs . 'shThemeEclipse.css', 'u' => $sh_js_uri . 'shThemeEclipse.css'),
            )));
        }
        if (empty($this->_shBrushes[$brush])) {
            Horde::addScriptFile('syntaxhighlighter/scripts/shBrush' . $brush . '.js', 'horde', true);
            $this->_shBrushes[$brush] = true;
        }

        return '<pre class="brush: ' . $type . '">' . htmlspecialchars($options['text']) . '</pre>';
    }
}

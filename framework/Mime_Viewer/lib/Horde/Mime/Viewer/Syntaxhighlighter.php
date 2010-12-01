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
class Horde_Mime_Viewer_Syntaxhighlighter extends Horde_Mime_Viewer_Base
{
    /**
     * This driver's display capabilities.
     *
     * @var array
     */
    protected $_capability = array(
        'full' => true,
        'info' => false,
        'inline' => true,
        'raw' => false,
    );

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _render()
    {
        return $this->_renderFullReturn($this->_renderInline());
    }

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

        $results = '<pre class="brush: ' . $language . '">' . htmlspecialchars($this->_mimepart->getContents()) . '</pre>';
        return $this->_renderReturn(
            $results,
            'text/html; charset=UTF-8'
        );
    }

    protected function _getLanguageOptions($language)
    {
        if ($language == 'php') {
            return 'html-script: true';
        }
    }

    /**
     * Attempts to determine what mode to use for the source-highlight
     * program from a MIME type.
     *
     * @param string $type  The MIME type.
     *
     * @return string  The mode to use.
     */
    protected function _mimeTypeToLanguage($type)
    {
        $type = str_replace('x-unknown', 'x-extension', $type);

        switch ($type) {
        case 'application/javascript':
        case 'application/x-javascript':
        case 'x-extension/javascript':
        case 'x-extension/js':
            return 'js';

        case 'application/x-perl':
        case 'x-extension/pl':
            return 'perl';

        case 'application/x-php':
        case 'x-extension/php':
        case 'x-extension/phps':
        case 'x-extension/php3s':
        case 'application/x-httpd-php':
        case 'application/x-httpd-php3':
        case 'application/x-httpd-phps':
            return 'php';

        case 'application/x-python':
            return 'python';

        case 'application/x-ruby':
            return 'ruby';

        case 'application/x-sh':
        case 'application/x-shellscript':
        case 'x-extension/bash':
        case 'x-extension/sh':
            return 'bash';

        case 'application/xml':
        case 'text/xml':
        case 'text/xslt':
        case 'text/html':
        case 'text/xhtml':
        case 'application/xhtml':
            return 'xml';

        case 'text/css':
        case 'x-extension/css':
            return 'css';

        case 'text/diff':
        case 'text/x-diff':
        case 'text/x-patch':
            return 'diff';

        case 'text/cpp':
        case 'text/x-c++':
        case 'text/x-c++src':
        case 'text/x-c++hdr':
        case 'text/x-c':
        case 'text/x-chdr':
        case 'text/x-csrc':
            return 'cpp';

        case 'text/x-java':
            return 'java';

        case 'text/x-pascal':
            return 'pascal';

        case 'text/x-sql':
            return 'sql';

        case 'x-extension/bat':
            return 'batch';

        case 'x-extension/cs':
            return 'csharp';

        case 'x-extension/vb':
        case 'x-extension/vba':
            return 'vb';

        default:
            return 'plain';
        }
    }

    protected function _languageToBrush($language)
    {
        switch ($language) {
        case 'php':
            return 'Php';

        case 'xml':
        case 'html':
        case 'xhtml':
        case 'xslt':
            return 'Xml';

        case 'bash':
        case 'shell':
            return 'Bash';

        case 'diff':
        case 'patch':
        case 'pas':
            return 'Diff';

        case 'css':
            return 'Css';

        case 'c':
        case 'cpp':
            return 'Cpp';

        case 'java':
            return 'Java';

        case 'js':
        case 'jscript':
        case 'javascript':
            return 'JScript';

        default:
            return 'Plain';
        }
    }
}

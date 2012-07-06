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
        case 'application/x-extension-javascript':
        case 'application/x-extension-js':
            return 'js';

        case 'application/x-perl':
        case 'application/x-extension-pl':
            return 'perl';

        case 'application/x-php':
        case 'application/x-extension-php':
        case 'application/x-extension-php3':
        case 'application/x-extension-phps':
        case 'application/x-extension-php3s':
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
        case 'application/x-extension-bash':
        case 'application/x-extension-sh':
            return 'bash';

        case 'application/xml':
        case 'text/xml':
        case 'text/xslt':
        case 'text/html':
        case 'text/xhtml':
        case 'application/xhtml':
        case 'application/x-vnd.kolab.contact':
        case 'application/x-vnd.kolab.event':
        case 'application/x-vnd.kolab.h-ledger':
        case 'application/x-vnd.kolab.h-prefs':
        case 'application/x-vnd.kolab.note':
        case 'application/x-vnd.kolab.task':
            return 'xml';

        case 'text/css':
        case 'application/x-extension-css':
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

        case 'text/x-sql':
            return 'sql';

        case 'application/x-extension-cs':
            return 'csharp';

        case 'application/x-extension-vb':
        case 'application/x-extension-vba':
            return 'vb';

        default:
            return 'plain';
        }
    }

    protected function _languageToBrush($language)
    {
        switch ($language) {
        case 'bash':
        case 'sh':
        case 'shell':
            return 'Bash';

        case 'csharp':
            return 'Csharp';

        case 'c':
        case 'cpp':
            return 'Cpp';

        case 'css':
            return 'Css';

        case 'diff':
        case 'patch':
        case 'pas':
            return 'Diff';

        case 'java':
            return 'Java';

        case 'js':
        case 'jscript':
        case 'javascript':
            return 'JScript';

        case 'perl':
            return 'Perl';

        case 'php':
            return 'Php';

        case 'python':
            return 'Python';

        case 'ruby':
            return 'Ruby';

        case 'sql':
            return 'Sql';

        case 'vb':
            return 'Vb';

        case 'xml':
        case 'html':
        case 'xhtml':
        case 'xslt':
            return 'Xml';

        default:
            return 'Plain';
        }
    }
}

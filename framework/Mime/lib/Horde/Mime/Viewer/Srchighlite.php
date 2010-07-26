<?php
/**
 * The Horde_Mime_Viewer_Srchighlite class renders out various content in HTML
 * format by using the GNU source-highlight package.
 *
 * Source-highlight: http://www.gnu.org/software/src-highlite/
 * Tested with v3.1.3
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Mime
 */
class Horde_Mime_Viewer_Srchighlite extends Horde_Mime_Viewer_Driver
{
    /**
     * This driver's display capabilities.
     *
     * @var array
     */
    protected $_capability = array(
        'full' => true,
        'info' => false,
        'inline' => false,
        'raw' => false
    );

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _render()
    {
        $ret = $this->_renderInline();

        reset($ret);
        $ret[key($ret)]['data'] = '<html><body>' .
            $ret[key($ret)]['data'] .
            '</body></html>';

        return $ret;
    }

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInline()
    {
        /* Check to make sure the viewer program exists. */
        if (!isset($this->_conf['location']) ||
            !file_exists($this->_conf['location'])) {
            return array();
        }

        /* Create temporary files for Webcpp. */
        $tmpin  = Horde::getTempFile('SrcIn');
        $tmpout = Horde::getTempFile('SrcOut', false);

        /* Write the contents of our buffer to the temporary input file. */
        file_put_contents($tmpin, $this->_mimepart->getContents());

        /* Determine the language from the mime type. */
        $lang = $this->_typeToLang($this->_mimepart->getType());

        /* Execute Source-Highlite. */
        exec($this->_conf['location'] . " --src-lang $lang --out-format html --input $tmpin --output $tmpout");
        $results = file_get_contents($tmpout);
        unlink($tmpout);

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => $results,
                'status' => array(),
                'type' => 'text/html; charset=' . $GLOBALS['registry']->getCharset()
            )
        );
    }

    /**
     * Attempts to determine what mode to use for the source-highlight
     * program from a MIME type.
     *
     * @param string $type  The MIME type.
     *
     * @return string  The mode to use.
     */
    protected function _typeToLang($type)
    {
        switch ($type) {
        case 'application/x-javascript':
            return 'js';

        case 'application/x-perl':
            return 'perl';

        case 'application/x-php':
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
            return 'sh';

        case 'application/x-tcl':
            return 'tcl';

        case 'application/xml':
        case 'text/xml':
            return 'xml';

        case 'text/cpp':
        case 'text/x-c++src':
        case 'text/x-c++hdr':
            return 'cpp';

        case 'text/css':
        case 'x-extension/css':
            return 'css';

        case 'text/diff':
        case 'text/x-diff':
        case 'text/x-patch':
            return 'diff';

        case 'text/x-chdr':
        case 'text/x-csrc':
            return 'c';

        case 'text/x-emacs-lisp':
            return 'lisp';

        case 'text/x-fortran':
        case 'x-extension/f77':
        case 'x-extension/f90':
        case 'x-extension/for':
        case 'x-extension/ftn':
            return 'fortran';

        case 'text/x-java':
            return 'java';

        case 'text/x-pascal':
            return 'pascal';

        case 'text/x-sql':
            return 'sql';

        case 'text/x-tex':
            return 'tex';

        case 'x-extension/asm':
            return 'asm';

        case 'x-extension/bat':
            return 'batch';

        case 'x-extension/cs':
            return 'csharp';

        case 'x-extension/vb':
        case 'x-extension/vba':
            return 'vbs';
        }
    }

}

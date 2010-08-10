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
 * @package  Mime_Viewer
 */
class Horde_Mime_Viewer_Srchighlite extends Horde_Mime_Viewer_Base
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
     * Constructor.
     *
     * @param Horde_Mime_Part $mime_part  The object with the data to be
     *                                    rendered.
     * @param array $conf                 Configuration:
     * <pre>
     * 'location' - (string) Location of the source-highlight binary.
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct(Horde_Mime_Part $part, array $conf = array())
    {
        $this->_required = array_merge($this->_required, array(
            'location'
        ));

        parent::__construct($part, $conf);
    }

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
        /* Check to make sure the viewer program exists. */
        if (!($location = $this->getConfigParam('location')) ||
            !file_exists($location)) {
            return array();
        }

        $tmpin = $this->_getTempFile();
        $tmpout = $this->_getTempFile();

        /* Write the contents of our buffer to the temporary input file. */
        file_put_contents($tmpin, $this->_mimepart->getContents());

        /* Determine the language from the mime type. */
        $lang = $this->_typeToLang($this->_mimepart->getType());

        /* Execute Source-Highlite. */
        exec($location . ' --src-lang ' . escapeshellarg($lang) . ' --out-format html --input ' . $tmpin . ' --output ' . $tmpout);
        $results = file_get_contents($tmpout);
        unlink($tmpout);

        return $this->_renderReturn(
            $results,
            'text/html; charset=' . $this->getConfigParam('charset')
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

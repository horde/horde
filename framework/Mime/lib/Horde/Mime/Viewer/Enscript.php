<?php
/**
 * The Horde_Mime_Viewer_Enscript class renders out various content in HTML
 * format by using GNU Enscript.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @package Horde_Mime_Viewer
 */
class Horde_Mime_Viewer_Enscript extends Horde_Mime_Viewer_Source
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
        'raw' => false
    );

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _render()
    {
        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => $this->_toHTML(),
                'status' => array(),
                'type' => 'text/html; charset=' . $GLOBALS['registry']->getCharset()
            )
        );
    }

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInline()
    {
        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => $this->_toHTML(),
                'status' => array(),
                'type' => 'text/html; charset=' . $GLOBALS['registry']->getCharset()
            )
        );
    }

    /**
     * Converts the code to HTML.
     *
     * @return string  The HTML-ified version of the MIME part contents.
     */
    protected function _toHTML()
    {
        /* Check to make sure the viewer program exists. */
        if (!isset($this->_conf['location']) ||
            !file_exists($this->_conf['location'])) {
            return array();
        }

        /* Create temporary files for input to Enscript. Note that we can't
         * use a pipe, since enscript must have access to the whole file to
         * determine its type for coloured syntax highlighting. */
        $tmpin = Horde::getTempFile('enscriptin');

        /* Write the contents of our buffer to the temporary input file. */
        file_put_contents($tmpin, $this->_mimepart->getContents());

        /* Execute the enscript command. */
        $lang = escapeshellarg($this->_typeToLang($this->_mimepart->getType()));
        $results = shell_exec($this->_conf['location'] . " -E$lang --language=html --color --output=- < $tmpin");

        /* Strip out the extraneous HTML from enscript. */
        $res_arr = preg_split('/\<\/?pre\>/i', $results);
        if (count($res_arr) == 3) {
            $results = trim($res_arr[1]);
        }

        return $this->_lineNumber($results);
    }

    /**
     * Attempts to determine what language to use for the enscript program
     * from a MIME type.
     *
     * @param string $type  The MIME type.
     *
     * @return string  The enscript 'language' parameter string.
     */
    protected function _typeToLang($type)
    {
        $ext = Horde_Mime_Magic::MIMEToExt($type);

        switch ($ext) {
        case 'cs':
            return 'java';

        case 'el':
            return 'elisp';

        case 'h':
            return 'c';

        case 'C':
        case 'H':
        case 'cc':
        case 'hh':
        case 'c++':
        case 'cxx':
        case 'cpp':
            return 'cpp';

        case 'htm':
        case 'shtml':
        case 'xml':
            return 'html';

        case 'js':
            return 'javascript';

        case 'pas':
            return 'pascal';

        case 'al':
        case 'cgi':
        case 'pl':
        case 'pm':
            return 'perl';

        case 'ps':
            return 'postscript';

        case 'vb':
            return 'vba';

        case 'vhd':
            return 'vhdl';

        case 'patch':
        case 'diff':
            return 'diffu';

        default:
            return $ext;
        }
    }
}

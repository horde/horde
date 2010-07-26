<?php
/**
 * The Horde_Mime_Viewer_Srchighlite class renders out various content in HTML
 * format by using Source-highlight.
 *
 * Source-highlight: http://www.gnu.org/software/src-highlite/
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Mime
 */
class Horde_Mime_Viewer_Srchighlite extends Horde_Mime_Viewer_Source
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

        // Need Horde headers for CSS tags.
        reset($ret);
        Horde::startBuffer();
        require $GLOBALS['registry']->get('templates', 'horde') . '/common-header.inc';
        echo $ret[key($ret)]['data'];
        require $GLOBALS['registry']->get('templates', 'horde') . '/common-footer.inc';
        $ret[key($ret)]['data'] = Horde::endBuffer();

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
        exec($this->_conf['location'] . " --src-lang $lang --out-format xhtml --input $tmpin --output $tmpout");
        $results = file_get_contents($tmpout);
        unlink($tmpout);

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => $this->_lineNumber($results),
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
        // TODO: 'prolog', 'flex', 'changelog', 'ruby'

        switch ($type) {
        case 'text/x-java':
            return 'java';

        case 'text/x-csrc':
        case 'text/x-c++src':
        case 'text/cpp':
            return 'cpp';

        case 'application/x-perl':
            return 'perl';

        case 'application/x-php':
        case 'x-extension/phps':
        case 'x-extension/php3s':
        case 'application/x-httpd-php':
        case 'application/x-httpd-php3':
        case 'application/x-httpd-phps':
            return 'php3';

        case 'application/x-python':
            return 'python';
        }
    }
}

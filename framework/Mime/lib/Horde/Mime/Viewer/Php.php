<?php
/**
 * The Horde_Mime_Viewer_Php class renders out syntax-highlighted PHP code in
 * HTML format.
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Mime_Viewer
 */
class Horde_Mime_Viewer_Php extends Horde_Mime_Viewer_Source
{
    /**
     * Can this driver render various views?
     *
     * @var boolean
     */
    protected $_capability = array(
        'embedded' => false,
        'forceinline' => false,
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
        $ret = $this->_renderInline();

        // Need Horde headers for CSS tags.
        reset($ret);
        $ret[key($ret)]['data'] =  Horde_Util::bufferOutput('require', $GLOBALS['registry']->get('templates', 'horde') . '/common-header.inc') .
            $ret[key($ret)]['data'] .
            Horde_Util::bufferOutput('require', $GLOBALS['registry']->get('templates', 'horde') . '/common-footer.inc');

        return $ret;
    }

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInline()
    {
        $code = $this->_mimepart->getContents();
        if (strpos($code, '<?php') === false) {
            $text = str_replace('&lt;?php&nbsp;', '', highlight_string('<?php ' . $code, true));
        } else {
            $text = highlight_string($code, true);
        }
        $text = trim(str_replace(array("\n", '<br />'), array('', "\n"), $text));
        $text = $this->_lineNumber($text);

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => $text,
                'status' => array(),
                'type' => 'text/html; charset=' . Horde_Nls::getCharset()
            )
        );
    }
}

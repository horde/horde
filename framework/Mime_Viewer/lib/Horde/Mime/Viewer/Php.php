<?php
/**
 * The Horde_Mime_Viewer_Php class renders out syntax-highlighted PHP code in
 * HTML format.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Mime_Viewer
 */
class Horde_Mime_Viewer_Php extends Horde_Mime_Viewer_Source
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
                'type' => 'text/html; charset=' . $GLOBALS['registry']->getCharset()
            )
        );
    }
}

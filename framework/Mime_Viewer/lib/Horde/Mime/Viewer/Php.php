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
        $code = $this->_mimepart->getContents();

        $text = (strpos($code, '<?php') === false)
            ? str_replace('&lt;?php&nbsp;', '', highlight_string('<?php ' . $code, true))
            : highlight_string($code, true);

        return $this->_renderReturn(
            $this->_lineNumber(trim(str_replace(array("\n", '<br />'), array('', "\n"), $text))),
            'text/html; charset=' . $this->getConfigParam('charset')
        );
    }

}

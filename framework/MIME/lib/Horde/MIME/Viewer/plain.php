<?php
/**
 * The Horde_MIME_Viewer_plain class renders out plain text with URLs made
 * into hyperlinks (if viewing inline).
 *
 * Copyright 1999-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_MIME_Viewer
 */
class Horde_MIME_Viewer_plain extends Horde_MIME_Viewer_Driver
{
    /**
     * Constructor.
     *
     * @param array $conf  Configuration specific to the driver.
     */
    function __construct($conf = array())
    {
        $this->_canrender['full'] = true;
        $this->_canrender['inline'] = true;
        $this->_type = 'text/html; charset=' . NLS::getCharset();
        parent::__construct($conf);
    }

    /**
     * Render out the contents.
     *
     * @return string  The rendered contents.
     */
    protected function _render()
    {
        $text = $this->_mimepart->getContents();

        /* Check for 'flowed' text data. */
        if ($this->_mimepart->getContentTypeParameter('format') == 'flowed') {
            $text = $this->_formatFlowed($text, $this->_mimepart->getContentTypeParameter('delsp'));
        }

        require_once 'Horde/Text/Filter.php';
        return '<html><body><tt>' . Text_Filter::filter($text, 'text2html', array('parselevel' => TEXT_HTML_MICRO, 'charset' => NLS::getCharset(), 'class' => null)) . '</tt></body></html>';
    }

    /**
     * Render out the contents.
     *
     * @return string  The rendered contents.
     */
    protected function _renderInline()
    {
        $text = $this->_mimepart->getContents();

        /* Check for 'flowed' text data. */
        return ($this->_mimepart->getContentTypeParameter('format') == 'flowed')
            ? $this->_formatFlowed($text, $this->_mimepart->getContentTypeParameter('delsp'))
            : $text;
    }

    /**
     * Format flowed text for HTML output.
     *
     * @param string $text    The text to format.
     * @param boolean $delsp  Was text created with DelSp formatting?
     *
     * @return string  The formatted text.
     */
    protected function _formatFlowed($text, $delsp = null)
    {
        require_once 'Text/Flowed.php';
        $flowed = new Text_Flowed($this->_mimepart->replaceEOL($text, "\n"), $this->_mimepart->getCharset());
        $flowed->setMaxLength(0);
        if (!is_null($delsp)) {
            $flowed->setDelSp($delsp);
        }
        return $flowed->toFixed();
    }
}

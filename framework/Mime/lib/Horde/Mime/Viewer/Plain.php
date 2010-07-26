<?php
/**
 * The Horde_Mime_Viewer_Plain class renders out plain text with URLs made
 * into hyperlinks (if viewing inline).
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Anil Madhavapeddy <anil@recoil.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Mime
 */
class Horde_Mime_Viewer_Plain extends Horde_Mime_Viewer_Driver
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
        $text = $this->_mimepart->getContents();
        $charset = $this->_mimepart->getCharset();

        /* Check for 'flowed' text data. */
        if ($this->_mimepart->getContentTypeParameter('format') == 'flowed') {
            $text = $this->_formatFlowed($text, $this->_mimepart->getContentTypeParameter('delsp'));
        }

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => '<html><body><tt>' . Horde_Text_Filter::filter($text, 'text2html', array(
                    'charset' => $charset,
                    'parselevel' => Horde_Text_Filter_Text2html::MICRO_LINKURL
                )) . '</tt></body></html>',
                'status' => array(),
                'type' => 'text/html; charset=' . $charset
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
        $text = Horde_String::convertCharset($this->_mimepart->getContents(), $this->_mimepart->getCharset());

        /* Check for 'flowed' text data. */
        $data = ($this->_mimepart->getContentTypeParameter('format') == 'flowed')
            ? $this->_formatFlowed($text, $this->_mimepart->getContentTypeParameter('delsp'))
            : $text;

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => $data,
                'status' => array(),
                'type' => 'text/html; charset=' . $GLOBALS['registry']->getCharset()
            )
        );
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
        $flowed = new Horde_Text_Flowed($this->_mimepart->replaceEOL($text, "\n"), $this->_mimepart->getCharset());
        $flowed->setMaxLength(0);
        if (!is_null($delsp)) {
            $flowed->setDelSp($delsp);
        }
        return $flowed->toFixed();
    }
}

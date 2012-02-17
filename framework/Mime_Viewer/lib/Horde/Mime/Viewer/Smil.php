<?php
/**
 * The Horde_Mime_Viewer_Smil renders SMIL documents to very basic HTML.
 *
 * Copyright 2006-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Mime_Viewer
 */
class Horde_Mime_Viewer_Smil extends Horde_Mime_Viewer_Base
{
    /**
     * Handle for the XML parser object.
     *
     * @var resource
     */
    protected $_parser;

    /**
     * String buffer to hold the generated content
     *
     * @var string
     */
    protected $_content;

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
        $this->_content = '';

        /* Create a new parser and set its default properties. */
        $this->_parser = xml_parser_create();
        xml_set_object($this->_parser, $this);
        xml_set_element_handler($this->_parser, '_startElement', '_endElement');
        xml_set_character_data_handler($this->_parser, '_defaultHandler');
        xml_parse($this->_parser, $this->_mimepart->getContents(), true);
        xml_parser_free($this->_parser);

        return $this->_renderReturn(
            $this->_content,
            'text/html; charset=UTF-8'
        );
    }

    /**
     * User-defined function callback for start elements.
     *
     * @param object $parser  Handle to the parser instance.
     * @param string $name    The name of this XML element.
     * @param array $attrs    List of this element's attributes.
     */
    protected function _startElement($parser, $name, $attrs)
    {
        switch ($name) {
        case 'IMG':
            if (isset($attrs['SRC'])) {
                $this->_content .= '<img src="' . htmlspecialchars($attrs['SRC']) . '" />';
            }
            break;
        }
    }

    /**
     * User-defined function callback for end elements.
     *
     * @param object $parser  Handle to the parser instance.
     * @param string $name    The name of this XML element.
     */
    protected function _endElement($parser, $name)
    {
    }

    /**
     * User-defined function callback for character data.
     *
     * @param object $parser  Handle to the parser instance.
     * @param string $data    String of character data.
     */
    protected function _defaultHandler($parser, $data)
    {
        $data = trim($data);
        if (!empty($data)) {
            $this->_content .= ' ' . htmlspecialchars($data);
        }
    }

}

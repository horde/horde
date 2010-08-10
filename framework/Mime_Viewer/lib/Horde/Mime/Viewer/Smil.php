<?php
/**
 * The Horde_Mime_Viewer_Smil renders SMIL documents to very basic HTML.
 *
 * Copyright 2006-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
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
        'inline' => false,
        'raw' => false
    );

    /**
     * Metadata for the current viewer/data.
     *
     * @var array
     */
    protected $_metadata = array(
        'compressed' => false,
        'embedded' => false,
        'forceinline' => true
    );

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _render()
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
            'text/html; charset=' . $this->getConfigParam('charset')
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

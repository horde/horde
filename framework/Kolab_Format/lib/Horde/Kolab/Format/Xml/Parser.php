<?php
/**
 * Handles parsing the provided XML input.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */

/**
 * Handles parsing the provided XML input.
 *
 * Copyright 2007-2009 Klarälvdalens Datakonsult AB
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */
class Horde_Kolab_Format_Xml_Parser
{
    /**
     * The XML parser.
     *
     * @var DOMDocument
     */
    private $_document;

    /**
     * Constructor.
     *
     * @param DOMDocument $document The XML parser.
     */
    public function __construct(DOMDocument $document)
    {
        $this->_document = $document;
        $this->_document->preserveWhiteSpace = false;
        $this->_document->formatOutput       = true;
    }

    /**
     * Load an object based on the given XML string.
     *
     * @param string $input The XML of the message as string.
     *
     * @return array The data array representing the object.
     *
     * @throws Horde_Kolab_Format_Exception If parsing the XML data failed.
     *
     * @todo Check encoding of the returned array. It seems to be ISO-8859-1 at
     * the moment and UTF-8 would seem more appropriate.
     */
    public function parse($input)
    {
        try {
            return $this->_parseXml($input);
        } catch (Horde_Kolab_Format_Exception_ParseError $e) {
            /**
             * If the first call does not return successfully this might mean we
             * got an attachment with broken encoding. There are some Kolab
             * client versions in the wild that might have done that. So the
             * next section starts a second attempt by guessing the encoding and
             * trying again.
             */
            if (0 !== strcasecmp(
                    mb_detect_encoding($input, 'UTF-8, ISO-8859-1'), 'UTF-8'
                )) {
                $input = mb_convert_encoding($input, 'UTF-8', 'ISO-8859-1');
            }
            return $this->_parseXml($input);
        }
    }

     /**
     * Parse the XML string. The root node is returned on success.
     *
     * @param string $input The XML of the message as string.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Format_Exception If parsing the XML data failed.
     *
     * @todo Make protected (fix the XmlTest for that)
     */
    private function _parseXml($input)
    {
        @$this->_document->loadXML($input);
        if (empty($this->_document->documentElement) || !$this->_document->documentElement->hasChildNodes()) {
            throw new Horde_Kolab_Format_Exception_ParseError($input);
        }
        return $this->_document;
    }
}

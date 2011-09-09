<?php
/**
 * Handles appending XML to the document.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */

/**
 * Handles appending XML to the document.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */
class Horde_Kolab_Format_Xml_Type_XmlAppend
{
    /**
     * The XML document this object works with.
     *
     * @var DOMDocument
     */
    private $_xmldoc;

    /**
     * Constructor
     */
    public function __construct($xmldoc)
    {
        $this->_xmldoc = $xmldoc;
    }

    /**
     * Create a node with raw XML content.
     *
     * @param DOMNode $parent The parent of the new node.
     * @param string  $xml    The XML content.
     *
     * @return DOMNode The new node.
     */
    public function save($parent, $xml)
    {
        $node = $this->_xmldoc->createDocumentFragment();
        $node->appendXML($xml);
        $parent->appendChild($node);
        return $node;
    }

}

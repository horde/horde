<?php
/**
 * Handles package.xml files.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Pear
 */

/**
 * Handles package.xml files.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Pear
 */
class Horde_Pear_Package_Xml
{
    /**
     * The parsed XML.
     *
     * @var SimpleXMLElement
     */
    private $_xml;

    /**
     * Constructor.
     *
     * @param resource $xml The package.xml as stream.
     */
    public function __construct($xml)
    {
        rewind($xml);
        $this->_xml = new SimpleXMLElement(stream_get_contents($xml));
    }

    /**
     * Return the package name.
     *
     * @return string The name of the package.
     */
    public function getName()
    {
        return (string) $this->_xml->name;
    }

    /**
     * Mark the package as being release and set the timestamps to now.
     *
     * @return NULL
     */
    public function releaseNow()
    {
        $this->_xml->date = date('Y-m-d');
        $this->_xml->time = date('H:i:s');
        $version = (string) $this->_xml->version->release;
        foreach($this->_xml->changelog as $release) {
            $relver = (string) $release->release->version->release;
            if ($relver == $version) {
                $release->release->date = date('Y-m-d');
            }
        }
    }

    /**
     * Return the complete package.xml as string.
     *
     * @return string The package.xml content.
     */
    public function __toString()
    {
        return $this->_xml->asXML();
    }
}
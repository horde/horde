<?php
/**
 * Test the modification-date attribute handler.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Format
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the modification-date attribute handler.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Format
 */
class Horde_Kolab_Format_Unit_Xml_Type_ModificationDateTest
extends PHPUnit_Framework_TestCase
{
    public function testLoadModificationDate()
    {
        list($doc, $rootNode, $mdate) = $this->_getDefaultMdate(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><modification-date>2011-06-28T08:42:11Z</modification-date>c</kolab>'
        );
        $attributes = array();
        $mdate->load('modification-date', $attributes, $rootNode);
        $this->assertInstanceOf(
            'DateTime', 
            $attributes['modification-date']
        );
    }

    public function testLoadModificationDateValue()
    {
        list($doc, $rootNode, $mdate) = $this->_getDefaultMdate(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><modification-date>2011-06-28T08:42:11Z</modification-date>c</kolab>'
        );
        $attributes = array();
        $mdate->load('modification-date', $attributes, $rootNode);
        $this->assertEquals(
            1309250531, 
            $attributes['modification-date']->format('U')
        );
    }

    public function testLoadStrangeModificationDate()
    {
        list($doc, $rootNode, $mdate) = $this->_getDefaultMdate(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><modification-date type="strange"><b/>1970-01-01T00:00:00Z<a/></modification-date>c</kolab>'
        );
        $attributes = array();
        $mdate->load('modification-date', $attributes, $rootNode);
        $this->assertEquals(
            0,
            $attributes['modification-date']->format('U')
        );
    }

    public function testLoadMissingModificationDate()
    {
        list($doc, $rootNode, $mdate) = $this->_getDefaultMdate(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $attributes = array();
        $mdate->load('modification-date', $attributes, $rootNode);
        $this->assertInstanceOf(
            'DateTime', 
            $attributes['modification-date']
        );
    }

    public function testSave()
    {
        list($doc, $rootNode, $mdate) = $this->_getDefaultMdate();
        $this->assertInstanceOf(
            'DOMNode', 
            $mdate->save('modification-date', array(), $rootNode)
        );
    }

    public function testSaveXml()
    {
        list($doc, $rootNode, $mdate) = $this->_getDefaultMdate();
        $mdate->save('modification-date', array('modification-date' => new DateTime('1970-01-01T00:00:00Z')), $rootNode);
        $this->assertRegexp(
            '#<modification-date>\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z</modification-date>#', 
            $doc->saveXML()
        );
    }

    public function testSaveOverwritesOldValue()
    {
        list($doc, $rootNode, $mdate) = $this->_getDefaultMdate(
            array('relaxed' => true),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><modification-date type="strange"><b/>1970-01-01T00:00:00Z<a/></modification-date>c</kolab>'
        );
        $mdate->save('modification-date', array('modification-date' => new DateTime('1971-01-01T00:00:00Z')), $rootNode);
        $this->assertRegexp(
            '#<modification-date type="strange">\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z<b/><a/></modification-date>#', 
            $doc->saveXML()
        );
    }

    private function _getDefaultMdate($params = array(), $previous = null)
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        if ($previous !== null) {
            $doc->loadXML($previous);
        }
        $root = new Horde_Kolab_Format_Xml_Type_Root(
            $doc, array('type' => 'kolab', 'version' => '1.0')
        );
        $rootNode = $root->save();
        $mdate = new Horde_Kolab_Format_Xml_Type_ModificationDate($doc, $params);
        return array($doc, $rootNode, $mdate);
    }
}

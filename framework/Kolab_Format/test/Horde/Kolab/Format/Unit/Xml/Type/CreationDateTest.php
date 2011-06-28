<?php
/**
 * Test the creation-date attribute handler.
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
 * Test the creation-date attribute handler.
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
class Horde_Kolab_Format_Unit_Xml_Type_CreationDateTest
extends PHPUnit_Framework_TestCase
{
    public function testLoadCreationDate()
    {
        list($doc, $rootNode, $cdate) = $this->_getDefaultCDate(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><creation-date>2011-06-28T08:42:11Z</creation-date>c</kolab>'
        );
        $attributes = array();
        $cdate->load('creation-date', $attributes, $rootNode);
        $this->assertInstanceOf(
            'DateTime', 
            $attributes['creation-date']
        );
    }

    public function testLoadCreationDateValue()
    {
        list($doc, $rootNode, $cdate) = $this->_getDefaultCDate(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><creation-date>2011-06-28T08:42:11Z</creation-date>c</kolab>'
        );
        $attributes = array();
        $cdate->load('creation-date', $attributes, $rootNode);
        $this->assertEquals(
            1309250531, 
            $attributes['creation-date']->format('U')
        );
    }

    public function testLoadStrangeCreationDate()
    {
        list($doc, $rootNode, $cdate) = $this->_getDefaultCDate(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><creation-date type="strange"><b/>1970-01-01T00:00:00Z<a/></creation-date>c</kolab>'
        );
        $attributes = array();
        $cdate->load('creation-date', $attributes, $rootNode);
        $this->assertEquals(
            0,
            $attributes['creation-date']->format('U')
        );
    }

    public function testLoadMissingCreationDate()
    {
        list($doc, $rootNode, $cdate) = $this->_getDefaultCDate(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $attributes = array();
        $cdate->load('creation-date', $attributes, $rootNode);
        $this->assertInstanceOf(
            'DateTime', 
            $attributes['creation-date']
        );
    }

    private function _getDefaultCDate($params = array(), $previous = null)
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        if ($previous !== null) {
            $doc->loadXML($previous);
        }
        $root = new Horde_Kolab_Format_Xml_Type_Root(
            $doc, array('type' => 'kolab', 'version' => '1.0')
        );
        $rootNode = $root->save();
        $cdate = new Horde_Kolab_Format_Xml_Type_CreationDate($doc, $params);
        return array($doc, $rootNode, $cdate);
    }

    public function testSave()
    {
        list($doc, $rootNode, $cdate) = $this->_getDefaultCDate();
        $this->assertInstanceOf(
            'DOMNode', 
            $cdate->save('creation-date', array(), $rootNode)
        );
    }

    public function testSaveXml()
    {
        list($doc, $rootNode, $cdate) = $this->_getDefaultCDate();
        $cdate->save('creation-date', array('creation-date' => new DateTime('1970-01-01T00:00:00Z')), $rootNode);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><creation-date>1970-01-01T00:00:00Z</creation-date></kolab>
', 
            $doc->saveXML()
        );
    }

    public function testSaveDoesNotTouchOldValue()
    {
        list($doc, $rootNode, $cdate) = $this->_getDefaultCDate(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><creation-date type="strange"><b/>1970-01-01T00:00:00Z<a/></creation-date>c</kolab>'
        );
        $cdate->save('creation-date', array('creation-date' => new DateTime('1970-01-01T00:00:00Z')), $rootNode);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><creation-date type="strange"><b/>1970-01-01T00:00:00Z<a/></creation-date>c</kolab>
', 
            $doc->saveXML()
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testSaveFailsOverwritingOldValue()
    {
        list($doc, $rootNode, $cdate) = $this->_getDefaultCDate(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><creation-date type="strange"><b/>1970-01-01T00:00:00Z<a/></creation-date>c</kolab>'
        );
        $cdate->save('creation-date', array('creation-date' => new DateTime('1971-01-01T00:00:00Z')), $rootNode);
    }

    public function testSaveRelaxedOverwritesOldValue()
    {
        list($doc, $rootNode, $cdate) = $this->_getDefaultCDate(
            array('relaxed' => true),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><creation-date type="strange"><b/>1970-01-01T00:00:00Z<a/></creation-date>c</kolab>'
        );
        $cdate->save('creation-date', array('creation-date' => new DateTime('1971-01-01T00:00:00Z')), $rootNode);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><creation-date type="strange">1971-01-01T00:00:00Z<b/><a/></creation-date>c</kolab>
', 
            $doc->saveXML()
        );
    }
}

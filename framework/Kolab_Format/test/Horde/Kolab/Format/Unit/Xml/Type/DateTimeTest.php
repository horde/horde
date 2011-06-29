<?php
/**
 * Test the date-time attribute handler.
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
 * Test the date-time attribute handler.
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
class Horde_Kolab_Format_Unit_Xml_Type_DateTimeTest
extends PHPUnit_Framework_TestCase
{
    public function testLoadDate()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultDateTime(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><date-time>2011-06-29</date-time>c</kolab>'
        );
        $attributes = array();
        $result->load('date-time', $attributes, $rootNode);
        $this->assertTrue($attributes['date-time']['date-only']);
    }

    public function testLoadDateValue()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultDateTime(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><date-time>2011-06-29</date-time>c</kolab>'
        );
        $attributes = array();
        $result->load('date-time', $attributes, $rootNode);
        $this->assertEquals('2011-06-29T00:00:00+00:00', $attributes['date-time']['date']->format('c'));
    }

    public function testLoadTimezoneValue()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultDateTime(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><date-time tz="Europe/Berlin">2011-06-29</date-time>c</kolab>'
        );
        $attributes = array();
        $result->load('date-time', $attributes, $rootNode);
        $this->assertEquals('2011-06-29T00:00:00+02:00', $attributes['date-time']['date']->format('c'));
    }

    public function testLoadStrangeDateTime()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultDateTime(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><date-time type="strange"><b/>2011-06-29<a/></date-time>c</kolab>'
        );
        $attributes = array();
        $result->load('date-time', $attributes, $rootNode);
        $this->assertEquals('2011-06-29T00:00:00+00:00', $attributes['date-time']['date']->format('c'));
    }

    public function testLoadEmptyDateTime()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultDateTime(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><date-time></date-time></kolab>'
        );
        $attributes = array();
        $result->load('date-time', $attributes, $rootNode);
        $this->assertNull($attributes['date-time']);
    }

    public function testLoadMissingDateTime()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultDateTime(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $attributes = array();
        $result->load('date-time', $attributes, $rootNode);
        $this->assertFalse(isset($attributes['date-time']));
    }

    public function testLoadDefault()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultDateTime(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
                'default' => array('date' => new DateTime())
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $attributes = array();
        $result->load('date-time', $attributes, $rootNode);
        $this->assertInstanceOf('DateTime', $attributes['date-time']['date']);
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_MissingValue
     */
    public function testLoadNotEmpty()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultDateTime(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $attributes = array();
        $result->load('date-time', $attributes, $rootNode);
    }

    public function testLoadNotEmptyRelaxed()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultDateTime(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                'relaxed' => true,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $attributes = array();
        $result->load('date-time', $attributes, $rootNode);
        $this->assertFalse(isset($attributes['date-time']));
    }

    public function testSaveDateTime()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultDateTime();
        $result->save(
            'date-time',
            array(
                'date-time' => array(
                    'date' => new DateTime(
                        '2011-06-29T11:11:11',
                        new DateTimeZone('UTC')
                    )
                )
            ),
            $rootNode
        );
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><date-time tz="UTC">2011-06-29T11:11:11Z</date-time></kolab>
',
            $doc->saveXML()
        );
    }

    public function testSaveTimeZone()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultDateTime();
        $result->save(
            'date-time',
            array(
                'date-time' => array(
                    'date' => new DateTime(
                        '2011-06-29T11:11:11',
                        new DateTimeZone('Europe/Berlin')
                    )
                )
            ),
            $rootNode
        );
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><date-time tz="Europe/Berlin">2011-06-29T11:11:11</date-time></kolab>
',
            $doc->saveXML()
        );
    }

    public function testSaveOverwritesOldValue()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultDateTime(
            array(),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><date-time type="strange"><b/>STRANGE<a/></date-time>c</kolab>'
        );
        $result->save(
            'date-time',
            array(
                'date-time' => array(
                    'date' => new DateTime(
                        '2011-06-29T11:11:11',
                        new DateTimeZone('Europe/Berlin')
                    ),
                    'date-only' => true
                )
            ),
            $rootNode
        );
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><date-time type="strange" tz="Europe/Berlin">2011-06-29<b/><a/></date-time>c</kolab>
',
            $doc->saveXML()
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception_MissingValue
     */
    public function testSaveNotEmpty()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultDateTime(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $result->save('date-time', array(), $rootNode);
    }

    public function testSaveNotEmptyWithOldValue()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultDateTime(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><date-time type="strange"><b/>STRANGE<a/></date-time>c</kolab>'
        );
        $this->assertInstanceOf(
            'DOMNode', 
            $result->save('date-time', array(), $rootNode)
        );
    }

    public function testDeleteNode()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultDateTime(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><date-time type="strange"><b/>STRANGE<a/></date-time>c</kolab>'
        );
        $result->save('date-time', array(), $rootNode);
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b">c</kolab>
',
            $doc->saveXML()
        );
    }

    public function testSaveNotEmptyRelaxed()
    {
        list($doc, $rootNode, $result) = $this->_getDefaultDateTime(
            array(
                'value' => Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY,
                'relaxed' => true,
            ),
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $this->assertFalse($result->save('date-time', array(), $rootNode));
    }

    private function _getDefaultDateTime($params = array(), $previous = null)
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        if ($previous !== null) {
            $doc->loadXML($previous);
        }
        $root = new Horde_Kolab_Format_Xml_Type_Root(
            $doc, array('type' => 'kolab', 'version' => '1.0')
        );
        $rootNode = $root->save();
        $result = new Horde_Kolab_Format_Xml_Type_DateTime($doc, $params);
        return array($doc, $rootNode, $result);
    }
}

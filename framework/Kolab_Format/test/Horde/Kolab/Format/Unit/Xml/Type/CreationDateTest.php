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
extends Horde_Kolab_Format_TestCase
{
    public function testLoadCreationDate()
    {
        $attributes = $this->_load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><creation-date>2011-06-28T08:42:11Z</creation-date>c</kolab>'
        );
        $this->assertInstanceOf('DateTime', $attributes['creation-date']);
    }

    public function testLoadCreationDateValue()
    {
        $attributes = $this->_load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><creation-date>2011-06-28T08:42:11Z</creation-date>c</kolab>'
        );
        $this->assertEquals(
            1309250531, 
            $attributes['creation-date']->format('U')
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testLoadInvalidCreationDateValue()
    {
        $attributes = $this->_load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><creation-date>2011A-06-28T08:42:11Z</creation-date>c</kolab>'
        );
    }

    public function testLoadInvalidCreationDateValueRelaxed()
    {
        $attributes = $this->_load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><creation-date>2011A-06-28T08:42:11Z</creation-date>c</kolab>',
            array('relaxed' => true)
        );
        $this->assertFalse($attributes['creation-date']);
    }

    public function testLoadStrangeCreationDate()
    {
        $attributes = $this->_load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><creation-date type="strange"><b/>1970-01-01T00:00:00Z<a/></creation-date>c</kolab>'
        );
        $this->assertEquals(0, $attributes['creation-date']->format('U'));
    }

    public function testLoadMissingCreationDate()
    {
        $attributes = $this->_load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $this->assertInstanceOf('DateTime', $attributes['creation-date']);
    }

    public function testSave()
    {
        $this->assertInstanceOf(
            'DOMNode', 
            $this->_saveToReturn()
        );
    }

    public function testSaveXml()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"><creation-date>1970-01-01T00:00:00Z</creation-date></kolab>
', 
            $this->_saveToXml(
                null,
                array('creation-date' => new DateTime('1970-01-01T00:00:00Z'))
            )
        );
    }

    public function testSaveDoesNotTouchOldValue()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><creation-date type="strange"><b/>1970-01-01T00:00:00Z<a/></creation-date>c</kolab>
', 
            $this->_saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><creation-date type="strange"><b/>1970-01-01T00:00:00Z<a/></creation-date>c</kolab>',
                array('creation-date' => new DateTime('1970-01-01T00:00:00Z'))
            )
        );
    }

    /**
     * @expectedException Horde_Kolab_Format_Exception
     */
    public function testSaveFailsOverwritingOldValue()
    {
        $this->_saveToXml(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><creation-date type="strange"><b/>1970-01-01T00:00:00Z<a/></creation-date>c</kolab>',
            array('creation-date' => new DateTime('1971-01-01T00:00:00Z'))
        );
    }

    public function testSaveRelaxedOverwritesOldValue()
    {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><creation-date type="strange">1971-01-01T00:00:00Z<b/><a/></creation-date>c</kolab>
', 
            $this->_saveToXml(
                '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><creation-date type="strange"><b/>1970-01-01T00:00:00Z<a/></creation-date>c</kolab>',
                array('creation-date' => new DateTime('1971-01-01T00:00:00Z')),
                array('relaxed' => true)    
            )
        );
    }

    private function _load($previous, $params = array())
    {
        list($type_params, $root_node, $type) = $this->_getCreationDate(
            $previous
        );
        $params = array_merge($type_params, $params);
        $attributes = array();
        $type->load('creation-date', $attributes, $root_node, $params);
        return $attributes;
    }

    private function _saveToXml(
        $previous = null, $attributes = array(), $params = array()
    )
    {
        list($type_params, $root_node, $type) = $this->_getCreationDate($previous);
        $params = array_merge($type_params, $params);
        $type->save('creation-date', $attributes, $root_node, $params);
        return (string)$params['helper'];
    }

    private function _saveToReturn(
        $previous = null, $attributes = array(), $params = array()
    )
    {
        list($type_params, $root_node, $type) = $this->_getCreationDate($previous);
        $params = array_merge($type_params, $params);
        return $type->save('creation-date', $attributes, $root_node, $params);
    }

    private function _getCreationDate(
        $previous = null,
        $kolab_type = 'kolab',
        $version = '1.0'
    )
    {
        return $this->getXmlType(
            'Horde_Kolab_Format_Xml_Type_CreationDate',
            $previous,
            $kolab_type,
            $version
        );
    }
}

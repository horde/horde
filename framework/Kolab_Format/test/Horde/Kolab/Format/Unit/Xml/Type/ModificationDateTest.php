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
extends Horde_Kolab_Format_TestCase
{
    public function testLoadModificationDate()
    {
        $attributes = $this->_load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><modification-date>2011-06-28T08:42:11Z</modification-date>c</kolab>'
        );
        $this->assertInstanceOf('DateTime', $attributes['modification-date']);
    }

    public function testLoadModificationDateValue()
    {
        $attributes = $this->_load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><modification-date>2011-06-28T08:42:11Z</modification-date>c</kolab>'
        );
        $this->assertEquals(
            1309250531, 
            $attributes['modification-date']->format('U')
        );
    }

    public function testLoadStrangeModificationDate()
    {
        $attributes = $this->_load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><modification-date type="strange"><b/>1970-01-01T00:00:00Z<a/></modification-date>c</kolab>'
        );
        $this->assertEquals(0, $attributes['modification-date']->format('U'));
    }

    public function testLoadMissingModificationDate()
    {
        $attributes = $this->_load(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0"/>'
        );
        $this->assertInstanceOf('DateTime', $attributes['modification-date']);
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
        $this->assertRegexp(
            '#<modification-date>\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z</modification-date>#', 
            $this->_saveToXml()
        );
    }

    public function testSaveOverwritesOldValue()
    {
        $this->assertRegexp(
            '#<modification-date type="strange">\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z<b/><a/></modification-date>#', 
            $this->_saveToXml(
            '<?xml version="1.0" encoding="UTF-8"?>
<kolab version="1.0" a="b"><modification-date type="strange"><b/>1970-01-01T00:00:00Z<a/></modification-date>c</kolab>'
            )
        );
    }

    private function _load($previous)
    {
        list($params, $root_node, $type) = $this->_getModificationDate($previous);
        $attributes = array();
        $type->load('modification-date', $attributes, $root_node, $params);
        return $attributes;
    }

    private function _saveToXml($previous = null)
    {
        list($params, $root_node, $type) = $this->_getModificationDate($previous);
        $attributes = array();
        $type->save('modification-date', $attributes, $root_node, $params);
        return (string)$params['helper'];
    }

    private function _saveToReturn($previous = null)
    {
        list($params, $root_node, $type) = $this->_getModificationDate($previous);
        $attributes = array();
        return $type->save('modification-date', $attributes, $root_node, $params);
    }

    private function _getModificationDate(
        $previous = null,
        $kolab_type = 'kolab',
        $version = '1.0'
    )
    {
        return $this->getXmlType(
            'Horde_Kolab_Format_Xml_Type_ModificationDate',
            $previous,
            $kolab_type,
            $version
        );
    }
}

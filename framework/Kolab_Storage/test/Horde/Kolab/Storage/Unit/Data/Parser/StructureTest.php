<?php
/**
 * Test the structure based parser.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the structure based parser.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Unit_Data_Parser_StructureTest
extends Horde_Kolab_Storage_TestCase
{
    public function testFetchArray()
    {
        $this->assertType(
            'array',
            $this->_getParser()->fetch(
                'test', array(1), array('type' => 'event')
            )
        );
    }

    public function testFetchUidKeys()
    {
        $this->assertEquals(
            array(1, 2, 4),
            array_keys(
                $this->_getParser()->fetch(
                    'test', array(1,2,4), array('type' => 'event')
                )
            )
        );
    }

    public function testFetchArrayValues()
    {
        $objects = $this->_getParser()->fetch(
            'test', array(1,2,4), array('type' => 'event')
        );
        foreach ($objects as $object) {
            $this->assertType('array', $object);
        }
    }

    public function testCreateObjectEnvelope()
    {
        $this->assertEquals(
            'Kolab Groupware Data',
            Horde_Mime_Part::parseMessage($this->_getNewObject())->getName()
        );
    }

    public function testCreateObjectCore()
    {
        $this->assertEquals(
            array(
                0 => 'multipart/mixed',
                1 => 'text/plain',
                2 => 'application/x-vnd.kolab.note'
            ),
            Horde_Mime_Part::parseMessage($this->_getNewObject())->contentTypeMap(true)
        );
    }

    public function testCreateObjectHeaders()
    {
        $this->assertEquals(
            'A',
            Horde_Mime_Headers::parseHeaders($this->_getNewObject())->getValue('Subject')
        );
    }

    private function _getNewObject()
    {
        $res = $this->_getStructure()->createObject(
            array('uid' => 'A', 'desc' => 'SUMMARY'),
            array('type' => 'note', 'version' => '1')
        );
        rewind($res);
        return stream_get_contents($res);
    }

    private function _getStructure()
    {
        $parser = new Horde_Kolab_Storage_Data_Parser_Structure($this->getMock('Horde_Kolab_Storage_Driver'));
        $format = new Horde_Kolab_Storage_Data_Format_Mime(
            new Horde_Kolab_Storage_Factory(), $parser
        );
        $parser->setFormat($format);
        return $parser;
    }

    private function _getParser()
    {
        $fixture = dirname(__FILE__) . '/../../../fixtures/event.struct';
        $structure = unserialize(base64_decode(file_get_contents($fixture)));
        $structures = array(
            1 => array('structure' => $structure),
            2 => array('structure' => $structure),
            4 => array('structure' => $structure),
        );
        $this->driver = $this->getMock('Horde_Kolab_Storage_Driver_Imap', array(), array(), '', false, false);
        $this->driver->expects($this->once())
            ->method('fetchStructure')
            ->will($this->returnValue($structures));
        $this->format = $this->getMock('Horde_Kolab_Storage_Data_Format');
        $this->format->expects($this->exactly(3))
            ->method('parse')
            ->will($this->returnValue(array()));
        $structure = new Horde_Kolab_Storage_Data_Parser_Structure(
            $this->driver
        );
        $structure->setFormat($this->format);
        return $structure;
    }
}

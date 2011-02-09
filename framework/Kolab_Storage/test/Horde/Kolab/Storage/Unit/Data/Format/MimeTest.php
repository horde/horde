<?php
/**
 * Test the MIME based format parsing.
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
 * Test the MIME based format parsing.
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
class Horde_Kolab_Storage_Unit_Data_Format_MimeTest
extends Horde_Kolab_Storage_TestCase
{
    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testInvalidData()
    {
        $this->_getMime()->parse('a', '1', null, array());
    }

    public function testMatchId()
    {
        $this->assertEquals(
            '2',
            $this->_getMime()->matchMimeId(
                'event',
                $this->_getStructure()->contentTypeMap()
            )
        );
    }

    public function testEvent()
    {
        $mime = $this->_getMime();

        $event = fopen(
            dirname(__FILE__) . '/../../../fixtures/event.xml.qp',
            'r'
        );
        $this->parser->expects($this->once())
            ->method('fetchId')
            ->with('a', '1', '2')
            ->will($this->returnValue($event));
        $object = $mime->parse(
            'a',
            '1',
            $this->_getStructure(),
            array('type' => 'event', 'version' => '1')
        );
        $this->assertEquals('libkcal-543769073.139', $object['uid']);
    }

    private function _getMime()
    {
        $this->parser = $this->getMock('Horde_Kolab_Storage_Data_Parser_Structure', array('fetchId'), array(), '', false, false);
        return new Horde_Kolab_Storage_Data_Format_Mime(
            new Horde_Kolab_Storage_Factory(), $this->parser
        );
    }

    private function _getStructure()
    {
        $fixture = dirname(__FILE__) . '/../../../fixtures/bodystructure.ser';
        $structures = unserialize(file_get_contents($fixture));
        return Horde_Mime_Part::parseStructure($structures[4]['structure']);
    }
}

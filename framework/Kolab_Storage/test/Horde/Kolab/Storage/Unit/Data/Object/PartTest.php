<?php
/**
 * Tests the Kolab mime part generator.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../Autoload.php';

/**
 * Tests the Kolab mime part generator.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Unit_Data_Object_PartTest
extends PHPUnit_Framework_TestCase
{
    public function testSetContentsReturnsMimePart()
    {
        $contents = $this->getMock('Horde_Kolab_Storage_Data_Object_Content');
        $part = new Horde_Kolab_Storage_Data_Object_Part();
        $this->assertInstanceOf(
            'Horde_Mime_Part', $part->setContents($contents)
        );
    }

    public function testPartCharset()
    {
        $contents = $this->getMock('Horde_Kolab_Storage_Data_Object_Content');
        $part = new Horde_Kolab_Storage_Data_Object_Part();
        $this->assertEquals(
            'utf-8', $part->setContents($contents)->getCharset()
        );
    }

    public function testPartDisposition()
    {
        $contents = $this->getMock('Horde_Kolab_Storage_Data_Object_Content');
        $part = new Horde_Kolab_Storage_Data_Object_Part();
        $this->assertEquals(
            'inline', $part->setContents($contents)->getDisposition()
        );
    }

    public function testPartDispositionKolabType()
    {
        $contents = $this->getMock('Horde_Kolab_Storage_Data_Object_Content');
        $part = new Horde_Kolab_Storage_Data_Object_Part();
        $this->assertEquals(
            'xml', $part->setContents($contents)->getDispositionParameter('x-kolab-type')
        );
    }

    public function testPartName()
    {
        $contents = $this->getMock('Horde_Kolab_Storage_Data_Object_Content');
        $part = new Horde_Kolab_Storage_Data_Object_Part();
        $this->assertEquals(
            'kolab.xml', $part->setContents($contents)->getName()
        );
    }

    public function testPartType()
    {
        $contents = $this->getMock('Horde_Kolab_Storage_Data_Object_Content');
        $contents->expects($this->once())
            ->method('getMimeType')
            ->will($this->returnValue('application/x-vnd.kolab.event'));
        $part = new Horde_Kolab_Storage_Data_Object_Part();
        $this->assertEquals(
            'application/x-vnd.kolab.event',
            $part->setContents($contents)->getType()
        );
    }

    public function testPartContent()
    {
        $contents = $this->getMock('Horde_Kolab_Storage_Data_Object_Content');
        $contents->expects($this->once())
            ->method('toString')
            ->will($this->returnValue('<content/>'));
        $part = new Horde_Kolab_Storage_Data_Object_Part();
        $this->assertEquals(
            '<content/>',
            $part->setContents($contents)->getContents()
        );
    }
}
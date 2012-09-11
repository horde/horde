<?php
/**
 * Test the Kolab content handler.
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
 * Test the Kolab content handler.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Storage_Unit_Data_Object_ContentTest
extends PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $format = $this->getMock('Horde_Kolab_Format');
        $format->expects($this->once())
            ->method('save')
            ->with(array('foo' => 'foo'))
            ->will($this->returnValue('<event/>'));
        $content = new Horde_Kolab_Storage_Data_Object_Content_New(array('foo' => 'foo'), $format);
        $content->setType('event');
        $this->assertEquals('<event/>', $content->toString());
    }

    public function testNewUid()
    {
        $format = $this->getMock('Horde_Kolab_Format');
        $content = new Horde_Kolab_Storage_Data_Object_Content_New(array('uid' => 'UID'), $format);
        $content->setType('event');
        $this->assertEquals('UID', $content->getUid());
    }

    /**
     * @expectedException Horde_Kolab_Storage_Data_Exception
     */
    public function testUidExceptionOnMissingUid()
    {
        $format = $this->getMock('Horde_Kolab_Format');
        $content = new Horde_Kolab_Storage_Data_Object_Content_New(array('foo' => 'bar'), $format);
        $content->setType('event');
        $content->getUid();
    }

    /**
     * @expectedException Horde_Kolab_Storage_Data_Exception
     */
    public function testCreateWithException()
    {
        $format = $this->getMock('Horde_Kolab_Format');
        $format->expects($this->once())
            ->method('save')
            ->with(array('foo' => 'foo'))
            ->will($this->throwException(new Horde_Kolab_Format_Exception()));
        $content = new Horde_Kolab_Storage_Data_Object_Content_New(array('foo' => 'foo'), $format);
        $content->setType('event');
        $content->toString();
    }

    public function testModify()
    {
        $format = $this->getMock('Horde_Kolab_Format');
        $format->expects($this->once())
            ->method('save')
            ->with(array('foo' => 'foo'), array('previous' => '<event/>'))
            ->will($this->returnValue('<event><modified/></event>'));
        $content = new Horde_Kolab_Storage_Data_Object_Content_Modified(array('foo' => 'foo'), $format);
        $content->setType('event');
        $content->setPreviousBody('<event/>');
        $this->assertEquals(
            '<event><modified/></event>', $content->toString()
        );
    }

    public function testModifiedMimeType()
    {
        $format = $this->getMock('Horde_Kolab_Format');
        $content = new Horde_Kolab_Storage_Data_Object_Content_Modified(array('foo' => 'foo'), $format);
        $content->setType('event');
        $content->setPreviousBody('<event/>');
        $this->assertEquals('application/x-vnd.kolab.event', $content->getMimeType());
    }

    /**
     * @expectedException Horde_Kolab_Storage_Data_Exception
     */
    public function testModifyWithException()
    {
        $format = $this->getMock('Horde_Kolab_Format');
        $format->expects($this->once())
            ->method('save')
            ->with(array('foo' => 'foo'), array('previous' => '<event/>'))
            ->will($this->throwException(new Horde_Kolab_Format_Exception()));
        $content = new Horde_Kolab_Storage_Data_Object_Content_Modified(array('foo' => 'foo'), $format);
        $content->setType('event');
        $content->setPreviousBody('<event/>');
        $content->toString();
    }

    public function testRaw()
    {
        $content = new Horde_Kolab_Storage_Data_Object_Content_Raw('<foo/>', 'UID');
        $this->assertEquals('<foo/>', $content->toString());
    }

    public function testRawUid()
    {
        $content = new Horde_Kolab_Storage_Data_Object_Content_Raw('<foo/>', 'UID');
        $this->assertEquals('UID', $content->getUid());
    }

    public function testMimeType()
    {
        $content = new Horde_Kolab_Storage_Data_Object_Content_Raw('<foo/>', 'UID');
        $content->setType('event');
        $this->assertEquals('application/x-vnd.kolab.event', $content->getMimeType());
    }

    /**
     * @dataProvider getType
     */
    public function testGetMimeType($type)
    {
        $mimeType = new Horde_Kolab_Storage_Data_Object_Content_Raw('a', 'b');
        $mimeType->setType($type);
        $this->assertEquals(
            'application/x-vnd.kolab.' . $type,
            $mimeType->getMimeType()
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Data_Exception
     */
    public function testUndefinedMimeType()
    {
        $mimeType = new Horde_Kolab_Storage_Data_Object_Content_Raw('a', 'b');
        $mimeType->setType('UNDEFINED');
    }

    /**
     * @dataProvider getType
     */
    public function testMatchMimeType($type)
    {
        $mimeType = new Horde_Kolab_Storage_Data_Object_Content_Raw('a', 'b');
        $mimeType->setType($type);
        $this->assertEquals(
            2,
            $mimeType->matchMimeId(
                array(
                    'multipart/mixed',
                    'foo/bar',
                    'application/x-vnd.kolab.' . $type
                )
            )
        );
    }

    public function getType()
    {
        return array(
            array('contact'),
            array('event'),
            array('note'),
            array('task'),
            array('h-prefs'),
            array('h-ledger'),
        );
    }

}

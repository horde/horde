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
        $type = $this->getMock('Horde_Kolab_Storage_Data_Object_MimeType', array(), array(), '', false, false);
        $format = $this->getMock('Horde_Kolab_Format');
        $format->expects($this->once())
            ->method('save')
            ->with(array('foo' => 'foo'))
            ->will($this->returnValue('<event/>'));
        $content = new Horde_Kolab_Storage_Data_Object_Content_New($type, array('foo' => 'foo'), $format);
        $this->assertEquals('<event/>', $content->toString());
    }

    public function testNewUid()
    {
        $type = $this->getMock('Horde_Kolab_Storage_Data_Object_MimeType', array(), array(), '', false, false);
        $format = $this->getMock('Horde_Kolab_Format');
        $content = new Horde_Kolab_Storage_Data_Object_Content_New($type, array('uid' => 'UID'), $format);
        $this->assertEquals('UID', $content->getUid());
    }

    /**
     * @expectedException Horde_Kolab_Storage_Data_Exception
     */
    public function testUidExceptionOnMissingUid()
    {
        $type = $this->getMock('Horde_Kolab_Storage_Data_Object_MimeType', array(), array(), '', false, false);
        $format = $this->getMock('Horde_Kolab_Format');
        $content = new Horde_Kolab_Storage_Data_Object_Content_New($type, array('foo' => 'bar'), $format);
        $content->getUid();
    }

    /**
     * @expectedException Horde_Kolab_Storage_Data_Exception
     */
    public function testCreateWithException()
    {
        $type = $this->getMock('Horde_Kolab_Storage_Data_Object_MimeType', array(), array(), '', false, false);
        $format = $this->getMock('Horde_Kolab_Format');
        $format->expects($this->once())
            ->method('save')
            ->with(array('foo' => 'foo'))
            ->will($this->throwException(new Horde_Kolab_Format_Exception()));
        $content = new Horde_Kolab_Storage_Data_Object_Content_New($type, array('foo' => 'foo'), $format);
        $content->toString();
    }

    public function testModify()
    {
        $type = $this->getMock('Horde_Kolab_Storage_Data_Object_MimeType', array(), array(), '', false, false);
        $format = $this->getMock('Horde_Kolab_Format');
        $format->expects($this->once())
            ->method('save')
            ->with(array('foo' => 'foo'), array('previous' => '<event/>'))
            ->will($this->returnValue('<event><modified/></event>'));
        $content = new Horde_Kolab_Storage_Data_Object_Content_Modified(array('foo' => 'foo'), $format);
        $content->setMimeType($type);
        $content->setPreviousBody('<event/>');
        $this->assertEquals(
            '<event><modified/></event>', $content->toString()
        );
    }

    public function testModifiedMimeType()
    {
        $type = $this->getMock('Horde_Kolab_Storage_Data_Object_MimeType', array(), array(), '', false, false);
        $type->expects($this->once())
            ->method('getMimeType')
            ->will($this->returnValue('application/x-vnd.kolab.event'));
        $format = $this->getMock('Horde_Kolab_Format');
        $content = new Horde_Kolab_Storage_Data_Object_Content_Modified(array('foo' => 'foo'), $format);
        $content->setMimeType($type);
        $content->setPreviousBody('<event/>');
        $this->assertEquals('application/x-vnd.kolab.event', $content->getMimeType());
    }

    /**
     * @expectedException Horde_Kolab_Storage_Data_Exception
     */
    public function testModifyWithException()
    {
        $type = $this->getMock('Horde_Kolab_Storage_Data_Object_MimeType', array(), array(), '', false, false);
        $format = $this->getMock('Horde_Kolab_Format');
        $format->expects($this->once())
            ->method('save')
            ->with(array('foo' => 'foo'), array('previous' => '<event/>'))
            ->will($this->throwException(new Horde_Kolab_Format_Exception()));
        $content = new Horde_Kolab_Storage_Data_Object_Content_Modified(array('foo' => 'foo'), $format);
        $content->setMimeType($type);
        $content->setPreviousBody('<event/>');
        $content->toString();
    }

    public function testRaw()
    {
        $type = $this->getMock('Horde_Kolab_Storage_Data_Object_MimeType', array(), array(), '', false, false);
        $content = new Horde_Kolab_Storage_Data_Object_Content_Raw($type, '<foo/>', 'UID');
        $this->assertEquals('<foo/>', $content->toString());
    }

    public function testRawUid()
    {
        $type = $this->getMock('Horde_Kolab_Storage_Data_Object_MimeType', array(), array(), '', false, false);
        $content = new Horde_Kolab_Storage_Data_Object_Content_Raw($type, '<foo/>', 'UID');
        $this->assertEquals('UID', $content->getUid());
    }

    public function testMimeType()
    {
        $type = $this->getMock('Horde_Kolab_Storage_Data_Object_MimeType', array(), array(), '', false, false);
        $type->expects($this->once())
            ->method('getMimeType')
            ->will($this->returnValue('application/x-vnd.kolab.event'));
        $content = new Horde_Kolab_Storage_Data_Object_Content_Raw($type, '<foo/>', 'UID');
        $this->assertEquals('application/x-vnd.kolab.event', $content->getMimeType());
    }
}

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
        $content = new Horde_Kolab_Storage_Data_Object_Content($format);
        $this->assertEquals(
            '<event/>', $content->create(array('foo' => 'foo'))
        );
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
        $content = new Horde_Kolab_Storage_Data_Object_Content($format);
        $content->create(array('foo' => 'foo'));
    }

    public function testModify()
    {
        $format = $this->getMock('Horde_Kolab_Format');
        $format->expects($this->once())
            ->method('save')
            ->with(array('foo' => 'foo'), array('previous' => '<event/>'))
            ->will($this->returnValue('<event><modified/></event>'));
        $content = new Horde_Kolab_Storage_Data_Object_Content($format);
        $this->assertEquals(
            '<event><modified/></event>',
            $content->modify(array('foo' => 'foo'), '<event/>')
        );
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
        $content = new Horde_Kolab_Storage_Data_Object_Content($format);
        $content->modify(array('foo' => 'foo'), '<event/>');
    }

}

<?php
/**
 * Test the handling of share data.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../../Autoload.php';

/**
 * Test the handling of share data.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Unit_List_Query_Cache_BaseTest
extends Horde_Kolab_Storage_TestCase
{
    public function testGetDescription()
    {
        $share = $this->_getShare();
        $this->driver->expects($this->once())
            ->method('getAnnotation')
            ->with('INBOX', '/shared/comment')
            ->will($this->returnValue('description'));
        $this->assertEquals('description', $share->getDescription('INBOX'));
    }

    public function testGetParameters()
    {
        $share = $this->_getShare();
        $this->driver->expects($this->once())
            ->method('getAnnotation')
            ->with('INBOX', '/shared/vendor/horde/share-params')
            ->will($this->returnValue(serialize(array('params'))));
        $this->assertEquals(array('params'), $share->getParameters('INBOX'));
    }

    public function testSetDescription()
    {
        $share = $this->_getShare();
        $this->driver->expects($this->once())
            ->method('setAnnotation')
            ->with('INBOX', '/shared/comment', 'test');
        $share->setDescription('INBOX', 'test');
    }

    public function testSetParameters()
    {
        $share = $this->_getShare();
        $this->driver->expects($this->once())
            ->method('setAnnotation')
            ->with(
                'INBOX',
                '/shared/vendor/horde/share-params',
                serialize(array('params'))
            );
        $share->setParameters('INBOX', array('params'));
    }

    private function _getShare()
    {
        $this->driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $this->list = new Horde_Kolab_Storage_List_Base(
            $this->driver,
            new Horde_Kolab_Storage_Factory()
        );
        return new Horde_Kolab_Storage_List_Query_Share_Base(
            $this->list, array()
        );
    }
}
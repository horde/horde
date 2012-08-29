<?php
/**
 * Test the handling of ACL.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../../Autoload.php';

/**
 * Test the handling of ACL.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_ComponentTest_List_Query_Acl_BaseTest
extends Horde_Kolab_Storage_TestCase
{
    public function testAclWithNewFolder()
    {
        $acl = $this->_getMockAcl();
        $this->driver->create('INBOX/Test');
        $this->assertEquals('lrswipkxtecda', $acl->getMyAcl('INBOX/Test'));
    }

    public function testSetGetAcl()
    {
        $acl = $this->_getMockAcl();
        $this->driver->create('INBOX/Test');
        $acl->setAcl('INBOX/Test', 'other', 'lrid');
        $this->assertEquals(
            array('test@example.com' => 'lrswipkxtecda', 'other' => 'lrid'),
            $acl->getAcl('INBOX/Test')
        );
    }

    public function testSetDeleteAcl()
    {
        $acl = $this->_getMockAcl();
        $this->driver->create('INBOX/Test');
        $acl->setAcl('INBOX/Test', 'other', 'lrid');
        $acl->setAcl('INBOX/Test', 'two', 'lrid');
        $acl->deleteAcl('INBOX/Test', 'two');
        $this->assertEquals(
            array(
                'other' => 'lrid',
                'test@example.com' => 'lrswipkxtecda'
            ),
            $acl->getAcl('INBOX/Test')
        );
    }

    private function _getMockAcl()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $this->driver = $this->getNamespaceMock($factory);
        return new Horde_Kolab_Storage_List_Query_Acl_Base($this->driver);
    }
}
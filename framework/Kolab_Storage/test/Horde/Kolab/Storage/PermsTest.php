<?php
/**
 * Test the Kolab permission handler.
 *
 * $Horde: framework/Kolab_Storage/test/Horde/Kolab/Storage/PermsTest.php,v 1.4 2009/01/06 17:49:28 jan Exp $
 *
 * @package Kolab_Storage
 */

/**
 *  We need the unit test framework 
 */
require_once 'PHPUnit/Framework.php';

require_once 'Horde.php';
require_once 'Horde/Kolab/Storage/Perms.php';

/**
 * Test the Kolab permission handler.
 *
 * $Horde: framework/Kolab_Storage/test/Horde/Kolab/Storage/PermsTest.php,v 1.4 2009/01/06 17:49:28 jan Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Kolab_Storage
 */
class Horde_Kolab_Storage_PermsTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test class construction.
     */
    public function testConstruct()
    {
        $folder = &new DummyFolder(null);
        $perms = &new Horde_Permission_Kolab($folder);
        $this->assertEquals('DummyFolder', get_class($perms->_folder));
        $this->assertEquals(array(), $perms->data);
        $perms = &new Horde_Permission_Kolab($folder, array('users' => array(
                                               'wrobel' => PERMS_SHOW | PERMS_READ |
                                               PERMS_EDIT | PERMS_DELETE)
                                  ));
        $this->assertTrue(is_array($perms->data));
    }

    /**
     * Test retrieving permissions.
     */
    public function testGetPerm()
    {
        $GLOBALS['conf']['auth']['driver'] = 'auto';
        $GLOBALS['conf']['group']['driver'] = 'mock';

        $folder = &new DummyFolder(
            array(
                'wrobel' => 'lrid',
                'reader' => 'lr',
                'viewer' => 'l',
                'editor' => 'lre',
                'anyone' => 'l',
                'anonymous' => '',
                'group:editors' => 'lre'
            )
        );
        $perms = &new Horde_Permission_Kolab($folder);
        $this->assertContains('users', array_keys($perms->data));
        $this->assertContains('wrobel', array_keys($perms->data['users']));
        $this->assertContains('reader', array_keys($perms->data['users']));
        $this->assertContains('groups', array_keys($perms->data));
        $this->assertContains('default', array_keys($perms->data));
        $this->assertContains('guest', array_keys($perms->data));
    }

    /**
     * Test saving permissions
     */
    public function testSave()
    {
        $GLOBALS['conf']['auth']['driver'] = 'auto';
        $GLOBALS['conf']['group']['driver'] = 'mock';

        $folder = &new DummyFolder(
            array(
                'wrobel' => 'lrid',
                'reader' => 'lr',
                'viewer' => 'l',
                'editor' => 'lre',
                'anyone' => 'l',
                'anonymous' => '',
                'group:editors' => 'lre'
            ),
            'wrobel'
        );
        $perms = &new Horde_Permission_Kolab($folder);
        unset($perms->data['guest']);
        unset($perms->data['default']);
        unset($perms->data['users']['viewer']);
        $perms->data['users']['editor'] = PERMS_SHOW | PERMS_READ | PERMS_EDIT | PERMS_DELETE;
        $perms->data['users']['test'] = PERMS_SHOW | PERMS_READ;
        $perms->data['groups']['group'] = PERMS_SHOW | PERMS_READ;
        $perms->save();
        $this->assertNotContains('anyone', array_keys($folder->acl));
        $this->assertNotContains('anonymous', array_keys($folder->acl));
        $this->assertEquals('lr', $folder->acl['test']);
        $this->assertEquals('lriswcd', $folder->acl['editor']);
        $this->assertEquals('alriswcd', $folder->acl['wrobel']);
    }

    /**
     * Test using Horde permissions.
     */
    public function testHordePermissions()
    {
        $GLOBALS['conf']['auth']['driver'] = 'auto';
        $GLOBALS['conf']['group']['driver'] = 'mock';

        $folder = &new DummyFolder(array(), 'wrobel');
        $hperms = &new Horde_Permission('test');
        $hperms->addUserPermission('wrobel', PERMS_SHOW, false);
        $perms = &new Horde_Permission_Kolab($folder, $hperms->data);
        $perms->save();
        $this->assertEquals('al', $folder->acl['wrobel']);
    }
}

/**
 * A dummy folder representation to test the Kolab permission handler.
 *
 * $Horde: framework/Kolab_Storage/test/Horde/Kolab/Storage/PermsTest.php,v 1.4 2009/01/06 17:49:28 jan Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Kolab_Storage
 */
class DummyFolder
{
    var $acl;
    var $_owner;
    function DummyFolder($acl, $owner = null) 
    {
        $this->acl = $acl;
        $this->_owner = $owner;
    }
    function getACL()
    {
        return $this->acl;
    }
    function setACL($user, $acl)
    {
        return $this->acl[$user] = $acl;
    }
    function deleteACL($user)
    {
        unset($this->acl[$user]);
    }
    function getOwner()
    {
        return $this->_owner;
    }
}


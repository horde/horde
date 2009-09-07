<?php
/**
 * Test the Kolab permission handler.
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
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Autoloader.php';

/**
 * Test the Kolab permission handler.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Storage_PermsTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test class construction.
     */
    public function testConstruct()
    {
        $folder = &new DummyFolder(null);
        $perms = &new Horde_Kolab_Storage_Permission($folder);
        $this->assertEquals(array(), $perms->get('perm'));
        $permissions =  array('users' =>
                              array(
                                  'wrobel' =>
                                  PERMS_SHOW |
                                  PERMS_READ |
                                  PERMS_EDIT |
                                  PERMS_DELETE
                              ));
        $perms = &new Horde_Kolab_Storage_Permission($folder, $permissions);
        $this->assertTrue(is_array($perms->get('perm')));
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
        $perms = &new Horde_Kolab_Storage_Permission($folder);
	$data = $perms->getData();
        $this->assertContains('users', array_keys($data));
        $this->assertContains('wrobel', array_keys($data['users']));
        $this->assertContains('reader', array_keys($data['users']));
        $this->assertContains('groups', array_keys($data));
        $this->assertContains('default', array_keys($data));
        $this->assertContains('guest', array_keys($data));
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
        $perms = &new Horde_Kolab_Storage_Permission($folder);
	$data = $perms->getData();
        unset($data['guest']);
        unset($data['default']);
        unset($data['users']['viewer']);
        $data['users']['editor'] = PERMS_SHOW | PERMS_READ | PERMS_EDIT | PERMS_DELETE;
        $data['users']['test'] = PERMS_SHOW | PERMS_READ;
        $data['groups']['group'] = PERMS_SHOW | PERMS_READ;
        $perms->setData($data);
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
        $perms = &new Horde_Kolab_Storage_Permission($folder, $hperms->data);
        $perms->save();
        $this->assertEquals('al', $folder->acl['wrobel']);
    }
}

/**
 * A dummy folder representation to test the Kolab permission handler.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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
class DummyFolder extends Horde_Kolab_Storage_Folder
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


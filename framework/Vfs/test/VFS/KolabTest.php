<?php
/**
 * Test the Kolab based virtual file system.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    VFS
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Test the Kolab based virtual file system.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    VFS
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */
class VFS_KolabTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test setup.
     *
     * @return NULL
     */
    public function setUp()
    {
        $this->markTestIncomplete('Convert to Horde4');

        $world = $this->prepareBasicSetup();

        $this->assertTrue($world['auth']->authenticate('wrobel@example.org',
                                                        array('password' => 'none')));

        $this->_vfs = VFS::factory('kolab');
    }

    /**
     * Test folder handling.
     *
     * @return NULL
     */
    public function testFolders()
    {
        $this->assertEquals(array(), $this->_vfs->listFolders());
        $this->assertNoError($this->_vfs->createFolder('/', 'test'));
        $this->assertEquals(1, count($this->_vfs->listFolders()));
        $this->assertNoError($this->_vfs->autocreatePath('/a/b/c/d'));
        $this->assertEquals(1, count($this->_vfs->listFolders('/')));
        $this->assertEquals(3, count($this->_vfs->listFolders('/INBOX')));
        $this->assertTrue($this->_vfs->exists('/INBOX/a', 'b'));
        $a = $this->_vfs->listFolder('/INBOX/a/b', null, true, true);
        $this->assertTrue(isset($a['c']));
        $this->assertTrue($this->_vfs->isFolder('/INBOX/a/b', 'c'));
        $this->assertTrue($this->_vfs->deleteFolder('/INBOX/a/b/c', 'd'));
        $this->assertFalse($this->_vfs->exists('/INBOX/a/b/c', 'd'));
        $this->assertTrue($this->_vfs->deleteFolder('/INBOX', 'a', true));
    }

    /**
     * Test file handling.
     *
     * @return NULL
     */
    public function testFiles()
    {
    }
}

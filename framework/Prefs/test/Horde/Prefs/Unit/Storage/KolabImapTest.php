<?php
/**
 * Copyright 2011-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Prefs
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Prefs
 */

/**
 * Test the preferences storage backend for Kolab.
 *
 * @category Horde
 * @package  Prefs
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Prefs
 */
class Horde_Prefs_Unit_Storage_KolabImapTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!class_exists('Horde_Kolab_Storage_Factory')) {
            $this->markTestSkipped('Horde_Kolab_Storage package is missing');
        }
        $_SESSION = array();
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testMissingStorage()
    {
        $b = new Horde_Prefs_Storage_KolabImap('nobody');
    }

    public function testConstruction()
    {
        $b = new Horde_Prefs_Storage_KolabImap(
            'nobody', array('kolab' => $this->_createDefaultStorage())
        );
    }

    public function testStorage()
    {
        $o = $this->_createDefaultStorage()
            ->getData('INBOX/Preferences')
            ->getObjects();
        $this->assertEquals(1, count($o));
    }

    public function testPrefsAccess()
    {
        $a = new Horde_Prefs(
            'horde',
            new Horde_Prefs_Storage_KolabImap(
                'nobody', array('kolab' => $this->_createDefaultStorage())
            )
        );
        $this->assertEquals('silver', $a['theme']);
    }

    public function testCreateFolder()
    {
        $this->markTestIncomplete();
        $storage = $this->_createStorage();
        $p = new Horde_Prefs(
            'test@example.com',
            array(
                new Horde_Prefs_Storage_KolabImap(
                    'test@example.com', array('kolab' => $storage)
                ),
                new Horde_Prefs_Stub_Storage('test')
            )
        );
        $p['a'] = 'c';
        $p->store();
        $this->assertContains('INBOX/Preferences', $storage->getList()->listFolders());
    }

    public function testCreatePreferences()
    {
        $storage = $this->_createStorage();
        $p = new Horde_Prefs(
            'test',
            array(
                new Horde_Prefs_Storage_KolabImap(
                    'test@example.com', array('kolab' => $storage)
                ),
                new Horde_Prefs_Stub_Storage('test')
            )
        );
        $p['a'] = 'c';
        $p->store();
        $this->assertEquals(
            1, count($storage->getData('INBOX/Preferences')->getObjects())
        );
    }

    public function testModifyPreferences()
    {
        $storage = $this->_createDefaultStorage();
        $p = new Horde_Prefs(
            'horde',
            array(
                new Horde_Prefs_Storage_KolabImap(
                    'test@example.com', array('kolab' => $storage)
                )
            )
        );
        $p['theme'] = 'barbie';
        $p->store();
        $objects = $storage->getData('INBOX/Preferences')->getObjects();
        $object = array_pop($objects);
        $this->assertContains(
            'theme:YmFyYmll', $object['pref']
        );
    }

    private function _createDefaultStorage()
    {
        return $this->_createStorage(
            array(
                'user/test/Preferences' => array(
                    't' => 'h-prefs.default',
                    'm' => array(
                        1 => array('file' => __DIR__ . '/../../fixtures/preferences.1'),
                    ),
                )
            )
        );
    }

    private function _createStorage($data = array())
    {
        $factory = new Horde_Kolab_Storage_Factory(
            array(
                'driver' => 'mock',
                'params' => array(
                    'data'   => array_merge(
                        array(
                            'format' => 'brief',
                            'user/test' => null,
                        ),
                        $data
                    ),
                    'username' => 'test@example.com'
                ),
                'queryset' => array(
                    'list' => array('queryset' => 'horde'),
                    'data' => array('queryset' => 'horde'),
                ),
                'cache'  => new Horde_Cache(new Horde_Cache_Storage_Mock()),
                'logger' => $this->getMock('Horde_Log_Logger'),
            )
        );
        return $factory->create();
    }
}

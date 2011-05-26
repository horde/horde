<?php
/**
 * Test logging in the preferences storage backend for Kolab.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Prefs
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Prefs
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../Autoload.php';

/**
 * Test logging in the preferences storage backend for Kolab.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Prefs
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Prefs
 */
class Horde_Prefs_Unit_Storage_KolabImapLogTest extends Horde_Test_Log
{
    public function setUp()
    {
        $_SESSION = array();
    }

    public function testMissingScope()
    {
        $a = new Horde_Prefs(
            'test',
            new Horde_Prefs_Storage_KolabImap(
                'nobody',
                array(
                    'kolab' => $this->_createDefaultStorage(),
                    'logger' => $this->getLogger()
                )
            )
        );
        $this->assertLogContains('Horde_Prefs_Storage_KolabImap: No preference information available for scope test (No preferences for application test available).');
    }

    public function testMissingFolder()
    {
        $a = new Horde_Prefs(
            'test',
            new Horde_Prefs_Storage_KolabImap(
                'nobody',
                array(
                    'kolab' => $this->_createStorage(),
                    'logger' => $this->getLogger()
                )
            )
        );
        $this->assertLogContains('Horde_Prefs_Storage_KolabImap: Failed retrieving Kolab preferences data storage (No Kolab storage backend available.');
    }

    public function testCreateFolder()
    {
        $p = new Horde_Prefs(
            'test',
            array(
                new Horde_Prefs_Storage_KolabImap(
                    'test',
                    array(
                        'kolab' => $this->_createStorage(),
                        'logger' => $this->getLogger()
                    )
                ),
                new Horde_Prefs_Stub_Storage('test')
            )
        );
        $p['a'] = 'c';
        $p->store();
        $this->assertLogContains('Horde_Prefs_Storage_KolabImap: Created default Kolab preferences folder "Preferences".');
    }

    private function _createDefaultStorage()
    {
        return $this->_createStorage(
            array(
                'user/test/Preferences' => array(
                    't' => 'h-prefs.default',
                    'm' => array(
                        1 => array('file' => dirname(__FILE__) . '/../../fixtures/preferences.1'),
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
            )
        );
        return $factory->create();
    }

}
<?php
/**
 * Test the server class.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Tests for the main server class.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Integration_ObjectsTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->markTestIncomplete('Needs to be fixed');
    }

    /**
     * Provide a mock server.
     *
     * @return Horde_Kolab_Server The mock server.
     */
    protected function getMockServer()
    {
        $injector = new Horde_Injector(new Horde_Injector_TopLevel());
        $config = new stdClass;
        $config->driver = 'none';
        $injector->setInstance('Horde_Kolab_Server_Config', $config);
        $injector->bindFactory('Horde_Kolab_Server_Structure',
                               'Horde_Kolab_Server_Factory',
                               'getStructure');
        $injector->bindFactory('Horde_Kolab_Server',
                               'Horde_Kolab_Server_Factory',
                               'getServer');
        return $injector->getInstance('Horde_Kolab_Server');
    }

    /**
     * The generating a uid for an object.
     *
     * @return NULL
     */
    public function testGenerateUid()
    {
        $ks   = $this->getMockServer();
        $user = new Horde_Kolab_Server_Object($ks, null, null);
        $this->assertEquals(preg_replace('/[0-9a-f]*/', '', $user->get(Horde_Kolab_Server_Object::ATTRIBUTE_UID)), '');
    }

    /**
     * Test creating the server object.
     *
     * @return NULL
     */
    public function testCreation()
    {
        try {
            $injector = new Horde_Injector(new Horde_Injector_TopLevel());
            $config = new stdClass;
            $config->driver = 'dummy';
            $injector->setInstance('Horde_Kolab_Server_Config', $config);
            $injector->bindFactory('Horde_Kolab_Server_Structure',
                                   'Horde_Kolab_Server_Factory',
                                   'getStructure');
            $injector->bindFactory('Horde_Kolab_Server',
                                   'Horde_Kolab_Server_Factory',
                                   'getServer');
            Horde_Kolab_Server_Factory::getServer($injector);
            $this->assertFail('No error!');
        } catch (Horde_Kolab_Server_Exception $e) {
            $this->assertEquals('Server type definition "Horde_Kolab_Server_Dummy" missing.',
                                $e->getMessage());
        }
    }

    /**
     * The base class provides no abilities for reading data. So it
     * should mainly return error. But it should be capable of
     * returning a dummy Kolab user object.
     *
     * @return NULL
     */
    public function testFetch()
    {
        $ks   = $this->getMockServer();
        $user = $ks->fetch('test');
        $this->assertEquals('Horde_Kolab_Server_Object_Kolab_User', get_class($user));

        $ks   = $this->getMockServer();
        $user = $ks->fetch();
        $this->assertEquals('Horde_Kolab_Server_Object_Kolab_User', get_class($user));
    }

    /**
     * Test listing objects.
     *
     * @return NULL
     */
    public function testList()
    {
        $ks   = $this->getMockServer();
        $hash = $ks->listHash('Horde_Kolab_Server_Object');
        $this->assertEquals($hash, array());

        $ks   = $this->getMockServer();
        $hash = $ks->listHash('Horde_Kolab_Server_Object');
        $this->assertEquals($hash, array());
    }

}

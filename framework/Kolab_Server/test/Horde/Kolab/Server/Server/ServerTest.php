
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
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Server_ServerTest extends PHPUnit_Framework_TestCase
{
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

/**
 * A dummy class to test the original abstract class.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_None extends Horde_Kolab_Server
{
    /**
     * Stub for reading object data.
     *
     * @param string $uid   The object to retrieve.
     * @param string $attrs Restrict to these attributes.
     *
     * @return array|PEAR_Error An array of attributes.
     */
    public function read($uid, $attrs = null)
    {
        return false;
    }

    /**
     * Stub for saving object data.
     *
     * @param string $uid   The object to retrieve.
     * @param string $attrs Restrict to these attributes.
     *
     * @return array|PEAR_Error An array of attributes.
     */
    public function save($uid, $data, $exists = false)
    {
        throw new Horde_Kolab_Server_Exception('Not implemented!');
    }

    /**
     * Stub for deleting an object.
     *
     * @param string $uid The UID of the object to be deleted.
     *
     * @return boolean True if saving succeeded.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function delete($uid)
    {
        throw new Horde_Kolab_Server_Exception('Not implemented!');
    }

    /**
     * Stub for renaming an object.
     *
     * @param string $uid The UID of the object to be renamed.
     * @param string $new The new UID of the object.
     *
     * @return boolean True if renaming succeeded.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function rename($uid, $new)
    {
        throw new Horde_Kolab_Server_Exception('Not implemented!');
    }

    /**
     * Determine the type of a Kolab object.
     *
     * @param string $uid The UID of the object to examine.
     *
     * @return string The corresponding Kolab object type.
     */
    public function determineType($uid)
    {
        return 'Horde_Kolab_Server_Object_Kolab_User';
    }

    /**
     * List all objects of a specific type
     *
     * @param string $type   The type of the objects to be listed
     * @param array  $params Additional parameters.
     *
     * @return array|PEAR_Error An array of Kolab objects.
     */
    public function listObjects($type, $params = null)
    {
        return array();
    }

    /**
     * Generates a UID for the given information.
     *
     * @param string $type The type of the object to create.
     * @param string $id   The id of the object.
     * @param array  $info Any additional information about the object to create.
     *
     * @return string|PEAR_Error The UID.
     */
    public function generateServerUid($type, $id, $info)
    {
        return $id;
    }

    /**
     * Return the root of the UID values on this server.
     *
     * @return string The base UID on this server (base DN on ldap).
     */
    public function getBaseUid()
    {
        return '';
    }

    /**
     * Identify the UID for the first object found using the specified
     * search criteria.
     *
     * @param array $criteria The search parameters as array.
     * @param int   $restrict A Horde_Kolab_Server::RESULT_* result restriction.
     *
     * @return boolean|string|array The UID(s) or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function uidForSearch($criteria,
                                 $restrict = Horde_Kolab_Server::RESULT_SINGLE)
    {
        /* In the default class we just return false */
        return false;
    }

    /**
     * Identify the GID for the first group found using the specified
     * search criteria
     *
     * @param array $criteria The search parameters as array.
     * @param int   $restrict A Horde_Kolab_Server::RESULT_* result restriction.
     *
     * @return boolean|string|array The GID(s) or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function gidForSearch($criteria,
                                 $restrict = Horde_Kolab_Server::RESULT_SINGLE)
    {
        /* In the default class we just return false */
        return false;
    }

    /**
     * Identify attributes for the objects found using a filter.
     *
     * @param array $criteria The search parameters as array.
     * @param array $attrs    The attributes to retrieve.
     * @param int   $restrict A Horde_Kolab_Server::RESULT_* result restriction.
     *
     * @return array The results.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function attrsForSearch($criteria, $attrs,
                                   $restrict = Horde_Kolab_Server::RESULT_SINGLE)
    {
        /* In the default class we just return an empty array */
        return array();
    }

    /**
     * Find object data matching a given set of criteria.
     *
     * @param array  $criteria The criteria for the search.
     * @param string $params   Additional search parameters.
     *
     * @return array The result array.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function find($criteria, $params = array())
    {
        /* In the default class we just return an empty array */
        return array();
    }

    /**
     * Returns the set of objects supported by this server.
     *
     * @return array An array of supported objects.
     */
    public function getSupportedObjects()
    {
        return array('Horde_Kolab_Server_Object');
    }
}

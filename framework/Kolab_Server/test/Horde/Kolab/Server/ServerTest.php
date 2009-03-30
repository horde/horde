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
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Autoloader.php';

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
class Horde_Kolab_Server_ServerTest extends PHPUnit_Framework_TestCase
{
    /**
     * The generating a uid for an object.
     *
     * @return NULL
     */
/*     public function testGenerateUid() */
/*     { */
/*         $ks = &Horde_Kolab_Server::factory('none'); */
/*         $uid = $ks->generateUid('Horde_Kolab_Server_Object', array()); */
/*         $this->assertEquals($uid, ''); */
/*     } */

    /**
     * Test creating the server object.
     *
     * @return NULL
     */
    public function testCreation()
    {
        try {
            Horde_Kolab_Server::factory('dummy');
            $this->assertFail('No error!');
        } catch (Horde_Kolab_Server_Exception $e) {
            $this->assertEquals($e->getMessage(),
                                'Server type definition "Horde_Kolab_Server_Dummy" missing.');
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
        $ks   = &Horde_Kolab_Server::factory('none');
        $user = $ks->fetch('test');
        $this->assertEquals('Horde_Kolab_Server_Object_user', get_class($user));

        $ks   = &Horde_Kolab_Server::factory('none', array('uid' => 'test'));
        $user = $ks->fetch();
        $this->assertEquals('Horde_Kolab_Server_Object_user', get_class($user));
    }

    /**
     * Test listing objects.
     *
     * @return NULL
     */
    public function testList()
    {
        $ks   = &Horde_Kolab_Server::factory('none');
        $hash = $ks->listHash('Horde_Kolab_Server_Object');
        $this->assertEquals($hash, array());

        $ks   = &Horde_Kolab_Server::factory('none', array('whatever'));
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
        throw new Horde_Kolab_Server_Exception('Not implemented!');
    }

    /**
     * Determine the type of a Kolab object.
     *
     * @param string $uid The UID of the object to examine.
     *
     * @return string The corresponding Kolab object type.
     */
    protected function determineType($uid)
    {
        return 'Horde_Kolab_Server_Object_user';
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
    protected function generateServerUid($type, $id, $info)
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
}

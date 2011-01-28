<?php
/**
 * Base class for Whups test cases
 *
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * @author     Michael J. Rubinsky <mrubinsk@horde.org>
 * @package    Whups
 * @subpackage UnitTests
 */
class Whups_TestBase Extends PHPUnit_Framework_TestCase {

    function setUp()
    {
        require_once dirname(__FILE__) . '/../Application.php';
        Horde_Registry::appInit('whups', array('cli' => true));
    }

    /**
     * Asserts that the supplied result is not a PEAR_Error
     *
     * Fails with a descriptive message if so
     * @param mixed $result  The value to check
     * @return boolean  Whether the assertion was successful
     */
    function assertOk($result)
    {
        if (is_a($result, 'DB_Error')) {
            $this->fail($result->getDebugInfo());
            return false;
        } elseif (is_a($result, 'PEAR_Error')) {
            $this->fail($result->getMessage());
            return false;
        }

        return true;
    }

}

class Whups_Driver_sql {

    var $_queues = array(
        array('queue_id' => 1,
              'queue_name' => 'queue one',
              'queue_description' => 'queue one description',
              'queue_versioned' => 1),
        array('queue_id' => 3,
              'queue_name' => 'queue three',
              'queue_description' => 'queue three description',
              'queue_versioned' => 0));

    function initialise()
    {
        return true;
    }

    function getQueuesInternal()
    {
        foreach ($this->_queues as $queue) {
            $q[$queue['queue_id']] = $queue['queue_name'];
        }
        return $q;
    }

}

/**
 * Permissions class to use when we need to pretend we are checking permissions
 *
 * Must instantiate it, then overwrite the global $perms object before calling
 * any method that needs to check permissions.
 */
class Whups_Test_Perms {

    function getPermissions($permission, $user = null, $creator = null)
    {
        return true;
    }

    function exists($permission)
    {
        return true;
    }

    function hasPermission($permission, $user, $perm, $creator = null)
    {
        return true;
    }

}


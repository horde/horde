<?php
/**
 * Base class for Whups test cases
 *
 * $Horde: whups/lib/tests/TestBase.php,v 1.11 2009/07/09 06:09:03 slusarz Exp $
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * @author     Michael J. Rubinsky <mrubinsk@horde.org>
 * @package    Whups
 * @subpackage UnitTests
 */
class Whups_TestBase Extends PHPUnit_Framework_TestCase {

    function setUp()
    {
        // TODO: Do we need to actually fake auth for any tests?
        @define('AUTH_HANDLER', true);
        @define('HORDE_BASE', dirname(__FILE__) . '/../../..');
        @define('WHUPS_BASE', dirname(__FILE__) . '/../..');
        require_once HORDE_BASE . '/lib/core.php';

        // Need to ensure $browser is in the global scope.
        $GLOBALS['browser'] = $browser;

        // Set up the CLI enviroment.
        Horde_Cli::init();

        // Need to load registry. For some reason including base.php doesn't
        // work properly yet. ($registry is not set when prefs.php loads)?
        $GLOBALS['registry'] = Horde_Registry::singleton();
        define('WHUPS_TEMPLATES', $GLOBALS['registry']->get('templates', 'whups'));
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


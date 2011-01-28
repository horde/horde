<?php

require_once dirname(__FILE__) . '/TestBase.php';

/**
 * API tests for Whups.
 *
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * @author     Michael J. Rubinsky <mrubinsk@horde.org>
 * @package    Whups
 * @subpackage UnitTests
 */
class Whups_ApiTest Extends Whups_TestBase {
    function setUp()
    {
        parent::setUp();
        require_once WHUPS_BASE . '/lib/api.php';
    }

    function test_listQueues_returns_hash()
    {
        $GLOBALS['perms'] = new Whups_Test_Perms();
        $result = _whups_listQueues();

        // Make sure it's not a PEAR_Error
        $this->assertOk($result);

        // Validate the results
        $this->assertEquals('queue one', $result[1]);
        $this->assertEquals('queue three', $result[3]);
    }

}

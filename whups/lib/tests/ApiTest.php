<?php

require_once __DIR__ . '/TestBase.php';

/**
 * API tests for Whups.
 *
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
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

        // Validate the results
        $this->assertEquals('queue one', $result[1]);
        $this->assertEquals('queue three', $result[3]);
    }

}

<?php
/**
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @license http://www.horde.org/licenses/gpl GPLv2
 * @category Horde
 * @package Horde_ActiveSync
 * @subpackage UnitTests
 */
class Horde_ActiveSync_StateTest_Base extends Horde_Test_Case
{
    protected static $state;

    public function _testGetStateWithNoState()
    {
        self::$state->loadState(array(), 0, Horde_ActiveSync::REQUEST_TYPE_FOLDERSYNC);
    }

    public static function tearDownAfterClass()
    {
        self::$state = null;
    }
}
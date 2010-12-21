<?php
/**
 * Test the base modules.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Cli
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Cli
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the base modules.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Kolab
 * @package    Kolab_Cli
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Cli
 */
class Horde_Kolab_Cli_Unit_Cli_Module_BaseTest
extends Horde_Kolab_Cli_TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->_storeErrorHandling();
    }

    public function tearDown()
    {
        $this->_restoreErrorHandling();
        parent::tearDown();
    }

    public function testNotice()
    {
        $this->assertTrue((bool) (error_reporting() & E_NOTICE));
    }

    public function testMissingNoticeWithRoundcubeDriver()
    {
        $options = array('driver' => 'roundcube');
        $arguments = array();
        $base = new Horde_Kolab_Cli_Module_Base();
        $base->handleArguments($options, $arguments);
        $this->assertFalse((bool) (error_reporting() & E_NOTICE));
    }

    public function testMissingNoticeWithHordeDriver()
    {
        $options = array('driver' => 'horde');
        $arguments = array();
        $base = new Horde_Kolab_Cli_Module_Base();
        $base->handleArguments($options, $arguments);
        $this->assertTrue((bool) (error_reporting() & E_NOTICE));
    }

    public function testLoggerOption()
    {
        $options = array('log' => $this->getLogFile());
        $arguments = array();
        $base = new Horde_Kolab_Cli_Module_Base();
        $base->handleArguments($options, $arguments);
        $this->assertContains('log', array_keys($options));
    }

    public function testLoggerClass()
    {
        $options = array('log' => $this->getLogFile());
        $arguments = array();
        $base = new Horde_Kolab_Cli_Module_Base();
        $base->handleArguments($options, $arguments);
        $this->assertInstanceOf('Horde_Log_Logger', $options['log']);
    }

    private function _storeErrorHandling()
    {
        $this->_error_handling = error_reporting();
    }

    private function _restoreErrorHandling()
    {
        error_reporting($this->_error_handling);
    }
}

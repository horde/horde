<?php
/**
 * Test the registry wrapper.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Pear
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Pear
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the registry wrapper.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Pear
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Pear
 */
class Horde_Pear_Server_RegistryTest
extends Horde_Pear_TestCase
{
    private $_config;

    public function setUp()
    {
        $config = self::getConfig('PEAR_TEST_CONFIG');
        if ($config && !empty($config['pear']['config'])) {
            $this->_config = $config['pear']['config'];
            setenv('PHP_PEAR_SYSCONF_DIR', $this->_config);
        } else {
            $this->markTestSkipped('Missing configuration!');
        }
    }

    public function testListPackages()
    {
        $this->assertType(
            'array',
            $this->_getRegistry()->listPackages()
        );
    }

    private function _getRegistry()
    {
        return new Horde_Pear_Registry();
    }
}

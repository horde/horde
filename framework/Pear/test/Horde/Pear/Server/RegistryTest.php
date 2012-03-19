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
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Pear
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../Autoload.php';

/**
 * Test the registry wrapper.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    Pear
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
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
            putenv('PHP_PEAR_SYSCONF_DIR=' . $this->_config);
        } else {
            $this->markTestSkipped('Missing configuration!');
        }
    }

    public function testListPackages()
    {
        $this->assertInternalType(
            'array',
            $this->_getRegistry()->listPackages()
        );
    }

    private function _getRegistry()
    {
        return new Horde_Pear_Registry();
    }
}

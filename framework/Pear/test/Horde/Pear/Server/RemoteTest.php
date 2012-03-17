<?php
/**
 * Test the remote server handler.
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
 * Test the remote server handler.
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
class Horde_Pear_Server_RemoteTest
extends Horde_Pear_TestCase
{
    private $_server;

    public function setUp()
    {
        if (!class_exists('Horde_Http_Client')) {
            $this->markTestSkipped('Horde_Http is missing!');
        }
        $config = self::getConfig('PEAR_TEST_CONFIG');
        if ($config && !empty($config['pear']['server'])) {
            $this->_server = $config['pear']['server'];
        } else {
            $this->markTestSkipped('Missing configuration!');
        }
    }

    public function testPackageList()
    {
        $this->assertContains(
            'Horde_Core',
            $this->_getRemote()->listPackages()
        );
    }

    /**
     * CAUTION: Will fail with each new Horde_Autoloader release!
     */
    public function testLatestUri()
    {
        $this->assertEquals(
            'http://pear.horde.org/get/Horde_Autoloader-1.0.0.tgz',
            $this->_getRemote()->getLatestDownloadUri('Horde_Autoloader')
        );
    }

    public function testChannel()
    {
        $this->assertContains(
            '<name>pear.horde.org</name>',
            $this->_getRemote()->getChannel()
        );
    }

    public function testDependencies()
    {
        $deps = $this->_getRemote()->getDependencies('Horde_Translation', '1.0.0');
        $keys = array();
        foreach ($deps as $dep) {
            if (isset($dep['channel']) && isset($dep['name'])) {
                $keys[] = $dep['channel'] . '/' . $dep['name'];
            }
        }
        $this->assertContains(
            'pear.horde.org/Horde_Exception',
            $keys
        );
    }

    private function _getRemote()
    {
        return new Horde_Pear_Remote($this->_server);
    }
}

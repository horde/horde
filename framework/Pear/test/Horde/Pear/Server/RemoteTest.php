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
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Pear
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the remote server handler.
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

    public function testDependencies()
    {
        $deps = $this->_getRemote()->getDependencies('Horde_Translation', '1.0.0');
        $this->assertEquals(
            array(
                'name' => 'Horde_Exception',
                'channel' => 'pear.horde.org',
                'min' => '1.0.0',
                'max' => '2.0.0',
                'exclude' => '2.0.0'
            ),
            $deps['required']['package']
        );
    }

    private function _getRemote()
    {
        return new Horde_Pear_Remote($this->_server);
    }
}

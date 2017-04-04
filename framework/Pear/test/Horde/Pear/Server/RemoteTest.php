<?php
/**
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pear
 * @subpackage UnitTests
 */

/**
 * Test the remote server handler.
 *
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @category   Horde
 * @copyright  2011-2017 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pear
 * @subpackage UnitTests
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

    public function testLatestUri()
    {
        $this->assertEquals(
            'https://pear.horde.org/get/Horde_DataTree-2.0.1.tgz',
            $this->_getRemote()->getLatestDownloadUri('Horde_DataTree')
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

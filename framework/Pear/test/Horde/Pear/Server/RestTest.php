<?php
/**
 * Test the REST connector.
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
 * Test the REST connector.
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
class Horde_Pear_Server_RestTest
extends Horde_Pear_TestCase
{
    private $_server;

    public function setUp()
    {
        $config = self::getConfig('PEAR_TEST_CONFIG');
        if ($config && !empty($config['pear']['server'])) {
            $this->_server = $config['pear']['server'];
        } else {
            $this->markTestSkipped('Missing configuration!');
        }
    }

    public function testFetchPackageList()
    {
        $this->assertType(
            'resource',
            $this->_getRest()->fetchPackageList()
        );
    }

    public function testPackageListResponse()
    {
        $response = $this->_getRest()->fetchPackageList();
        rewind($response);
        $this->assertContains(
            'rest.categorypackages',
            stream_get_contents($response)
        );
    }

    private function _getRest()
    {
        return new Horde_Pear_Rest(
            new Horde_Http_Client(),
            $this->_server
        );
    }
}

<?php
/**
 * Test the mock connection factory.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../../Autoload.php';

/**
 * Test the mock connection factory.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Class_Server_Factory_Conn_MockTest
extends PHPUnit_Framework_TestCase
{
    public function testMethodGetconfigurationHasResultArrayTheConnectionConfiguration()
    {
        $factory = new Horde_Kolab_Server_Factory_Conn_Mock();
        $factory->setConfiguration(array('basedn' => 'test'));
        $this->assertEquals(
            array('basedn' => 'test'),
            $factory->getConfiguration()
        );
    }

    public function testMethodSetconfigurationHasPostconditionThatTheConfigurationWasSaved()
    {
        $factory = new Horde_Kolab_Server_Factory_Conn_Mock();
        $factory->setConfiguration(array());
        $this->assertEquals(
            array(),
            $factory->getConfiguration()
        );
    }

    public function testMethodGetconfigurationThrowsExceptionIfNoConfigurationHasBeenSet()
    {
        $factory = new Horde_Kolab_Server_Factory_Conn_Mock();
        try {
            $factory->getConfiguration();
            $this->fail('No exception!');
        } catch (Horde_Kolab_Server_Exception $e) {
            $this->assertEquals(
                'The configuration has not been set!',
                $e->getMessage()
            );
        }
    }

    public function testMethodGetconnectionHasResultConnectionmock()
    {
        $factory = new Horde_Kolab_Server_Factory_Conn_Mock();
        $factory->setConfiguration(array('basedn' => 'test'));
        $this->assertType(
            'Horde_Kolab_Server_Connection_Mock',
            $factory->getConnection()
        );
    }
}
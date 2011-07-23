<?php
/**
 * Test the dispatcher of the free/busy system.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test dispatching calls in the free/busy system.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Integration_DispatchTest
extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideUrls
     */
    public function testDispatching($url, $response)
    {
        $params = array(
            'script' => '/freebusy/freebusy.php',
            'request' => array(
                'class' => 'Horde_Controller_Request_Mock',
                'params' => array(
                    'server' => array(
                        'REQUEST_URI' => $url
                    )
                )
            ),
            'request_config' => array(
                'prefix' => 'Horde_Kolab_FreeBusy_Stub_Controller_',
            ),
            'logger' => array(
                'Horde_Log_Handler_Null' => array(),
            ),
        );
        $application = new Horde_Kolab_FreeBusy('Freebusy', 'Kolab', $params);
        ob_start();
        $application->dispatch();
        $output = ob_get_clean();
        $this->assertEquals($response, $output);
    }

    public function provideUrls()
    {
        return array(
            array(
                '/freebusy/test@example.com.ifb',
                'fetched "ifb" data for user "test@example.com"',
            ),
            array(
                '/freebusy/test@example.com.vfb',
                'fetched "vfb" data for user "test@example.com"',
            ),
            array(
                '/freebusy/test@example.com.xfb',
                'fetched "xfb" data for user "test@example.com"',
            ),
            array(
                '/freebusy/test@example.com.zfb',
                '',
            ),
            array(
                '/freebusy/delete/test@example.com',
                'deleted data for user "test@example.com"',
            ),
            array(
                '/freebusy/delete',
                '',
            ),
            array(
                '/freebusy/delete/',
                '',
            ),
            array(
                '/freebusy/delete/i',
                'deleted data for user "i"',
            ),
            array(
                '/freebusy/delete/i/j',
                '',
            ),
            array(
                '/freebusy/regenerate',
                'regenerated',
            ),
            array(
                '/freebusy/regenerate/',
                'regenerated',
            ),
            array(
                '/freebusy/regenerate/j',
                '',
            ),
            array(
                '/freebusy/trigger/test@example.com/Kalender.pfb',
                'triggered folder "test@example.com/Kalender"',
            ),
            array(
                '/freebusy/trigger/test@example.com/Kalender.xfb',
                '',
            ),
            array(
                '/freebusy/trigger',
                '',
            ),
            array(
                '/freebusy/test@example.com/Kalender.pfb',
                'triggered folder "test@example.com/Kalender" and retrieved data of type "pfb"',
            ),
            array(
                '/freebusy/test@example.com/Kalender.pxfb',
                'triggered folder "test@example.com/Kalender" and retrieved data of type "pxfb"',
            ),
            array(
                '/freebusy/test@example.com/Kalender.ifb',
                '',
            ),
            array(
                '/freebusy',
                '',
            ),
        );
    }
}
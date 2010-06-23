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
     * Test destruction.
     *
     * @return NULL
     */
    public function tearDown()
    {
        Horde_Kolab_FreeBusy::destroy();
    }

    /**
     * Test dispatching a "fetch" call.
     *
     * @return NULL
     */
    public function testDispatching()
    {
        $urls = array(
            '/freebusy/test@example.com.ifb' => 'fetched "ifb" data for user "test@example.com"',
            '/freebusy/test@example.com.vfb' => 'fetched "vfb" data for user "test@example.com"',
            '/freebusy/test@example.com.xfb' => 'fetched "xfb" data for user "test@example.com"',
            '/freebusy/test@example.com.zfb' => false,
            '/freebusy/delete/test@example.com' => 'deleted data for user "test@example.com"',
            '/freebusy/delete' => false,
            '/freebusy/delete/' => false,
            '/freebusy/delete/i' => 'deleted data for user "i"',
            '/freebusy/delete/i/j' => false,
            '/freebusy/regenerate' => 'regenerated',
            '/freebusy/regenerate/' => 'regenerated',
            '/freebusy/regenerate/j' => false,
            '/freebusy/trigger/test@example.com/Kalender.pfb' => 'triggered folder "test@example.com/Kalender"',
            '/freebusy/trigger/test@example.com/Kalender.xfb' => false,
            '/freebusy/trigger' => false,
            '/freebusy/test@example.com/Kalender.pfb' => 'triggered folder "test@example.com/Kalender" and retrieved data of type "pfb"',
            '/freebusy/test@example.com/Kalender.pxfb' => 'triggered folder "test@example.com/Kalender" and retrieved data of type "pxfb"',
            '/freebusy/test@example.com/Kalender.ifb' => false,
            '/freebusy' => false,
        );

        foreach ($urls as $key => $result) {
            $params = array(
                'script' => '/freebusy/freebusy.php',
                'request' => array(
                    'params' => array(
                        'server' => array(
                            'REQUEST_URI' => $key
                        )
                    )
                ),
                'response' => array(
                    'class' => 'Horde_Controller_Response_Mock',
                ),
                'dispatch' => array(
                    'controllerDir' => dirname(__FILE__) . '/../Stub/Controller',
                ),
                'mapper' => array(
                    'controller' => 'stubfreebusy',
                ),
                'logger' => array(
                    'Horde_Log_Handler_Null' => array(),
                )
            );

            $application = new Horde_Kolab_FreeBusy('Kolab', 'Freebusy', $params);
            Horde_Kolab_FreeBusy::setInstance($application);

            $output = '';

            if (empty($result)) {
                try {
                    ob_start();
                    $application->dispatch();
                    $output = ob_get_contents();
                    ob_end_clean();
                } catch (Horde_Controller_Exception $e) {
                    $this->assertEquals('No routes match the path: "' . trim($key, '/') . '"', $e->getMessage());
                }
                $this->assertEquals('', $output);
            } else {
                ob_start();
                $application->dispatch();
                $output = ob_get_contents();
                ob_end_clean();
                $this->assertEquals($result, $output);
            }
            Horde_Kolab_FreeBusy::destroy();
        }
    }
}
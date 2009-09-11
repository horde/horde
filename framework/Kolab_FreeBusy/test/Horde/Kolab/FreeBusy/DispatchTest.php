<?php
/**
 * Test the dispatcher of the free/busy system.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Autoloader.php';

/**
 * Test dispatching calls in the free/busy system.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_DispatchTest extends Horde_Kolab_Test_FreeBusy
{

    /**
     * Test setup.
     */
    public function setUp()
    {
        /**
         * The controller automatically starts a session. But we don't want to
         * send out cookie headers since we are running in PHPUnit.
         */
        ini_set('session.use_cookies', 0);
        ini_set('session.use_only_cookies', 0);
        session_cache_limiter(null);
    }

    /**
     * Test destruction.
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
    public function testFetch()
    {
        $urls = array(
            '/freebusy/test@example.com.ifb' => array(
                'ifb', 'test@example.com'),
            '/freebusy/test@example.com.vfb' => array(
                'vfb', 'test@example.com'),
            '/freebusy/test@example.com.xfb' => array(
                'xfb', 'test@example.com'),
            '/freebusy/test@example.com.pfb' => array(
                'pfb', 'test@example.com'),
            '/freebusy/test@example.com.pxfb' => array(
                'pxfb', 'test@example.com'),
            '/freebusy/test@example.com.zfb' => false,
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
                'dispatch' => array(
                    'controllerDir' => dirname(__FILE__) . '/Mock/Controller',
                )
            );
            $application = Horde_Kolab_FreeBusy::singleton($params);
            if (empty($result)) {
                try {
                    $application->dispatch();
                } catch (Horde_Controller_Exception $e) {
                    $this->assertEquals('No routes match the path: "' . substr($key, 1) . '"', $e->getMessage());
                }
            } else {
                ob_start();
                $application->dispatch();
                $output = ob_get_contents();
                ob_end_clean();
                $this->assertEquals('fetched "' . $result[0] . '" data for user "' . $result[1] . '"', $output);
            }
            Horde_Kolab_FreeBusy::destroy();
        }
    }

    /**
     * Test dispatching a "trigger" call.
     *
     * @return NULL
     */
    public function testTrigger()
    {
    }

    /**
     * Test dispatching a "regenerate" call.
     *
     * @return NULL
     */
    public function testRegenerate()
    {
    }

    /**
     * Test dispatching a "delete" call.
     *
     * @return NULL
     */
    public function testDelete()
    {
    }
}
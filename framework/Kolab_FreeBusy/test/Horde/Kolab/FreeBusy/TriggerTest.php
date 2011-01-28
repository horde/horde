<?php
/**
 * Test triggering free/busy generation.
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
 * Test triggering the generation/caching of free/busy data.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_FreeBusy_TriggerTest extends Horde_Kolab_Test_FreeBusy
{

    /**
     * Test setup.
     *
     * @return NULL
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
     *
     * @return NULL
     */
    public function tearDown()
    {
        Horde_Kolab_FreeBusy::destroy();
    }

    /**
     * Test triggering a folder.
     *
     * @return NULL
     */
    public function testTriggering()
    {
        $params = array(
            'script' => '/freebusy/freebusy.php',
            'request' => array(
                'params' => array(
                    'server' => array(
                        'REQUEST_URI' => '/freebusy/test@example.com/Kalender.pxfb'
                    )
                )
            ),
            'logger' => array(
                'Horde_Log_Handler_Null' => array(),
            )
        );
        
        $application = Horde_Kolab_FreeBusy::singleton($params);

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
<?php
/**
 * Test triggering free/busy generation.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../Autoload.php';

/**
 * Test triggering the generation/caching of free/busy data.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_FreeBusy
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Integration_TriggerTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test triggering a folder.
     *
     * @return NULL
     */
    public function testTriggering()
    {
        $this->markTestIncomplete();

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
        
        $application = new Horde_Kolab_FreeBusy('Kolab', 'Freebusy', $params);
        ob_start();
        $application->dispatch();
        $output = ob_get_clean();
        $this->assertEquals('', $output);
    }
}
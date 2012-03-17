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
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../Autoload.php';

/**
 * Test dispatching calls in the free/busy system.
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
class Horde_Kolab_FreeBusy_Integration_DispatchTest
extends Horde_Kolab_FreeBusy_TestCase
{
    /**
     * @dataProvider provideUrls
     */
    public function testDispatching($url, $response)
    {
        $this->assertEquals(
            $response,
            $this->dispatch(
                $url,
                array(),
                array(
                    'Horde_Kolab_FreeBusy_Provider' => new Horde_Kolab_FreeBusy_Stub_Provider()
                )
            )
        );
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
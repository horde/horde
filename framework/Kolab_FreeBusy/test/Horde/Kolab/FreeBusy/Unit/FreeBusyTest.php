<?php
/**
 * Test the entry point into the export system.
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
 * Test the entry point into the export system.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_FreeBusy_Unit_FreeBusyTest
extends Horde_Kolab_FreeBusy_TestCase
{
    public function testExportType()
    {
        $e = new Horde_Kolab_FreeBusy('Freebusy', 'Kolab');
        $this->assertEquals('Freebusy', $e->getExportType());
    }

    public function testBackend()
    {
        $e = new Horde_Kolab_FreeBusy('Freebusy', 'Kolab');
        $this->assertEquals('Kolab', $e->getBackend());
    }

    public function testParameters()
    {
        $e = new Horde_Kolab_FreeBusy('Freebusy', 'Kolab', true);
        $this->assertTrue($e->get('Horde_Kolab_FreeBusy_Configuration'));
    }

    public function testDispatch()
    {
        $e = new Horde_Kolab_FreeBusy(
            'Freebusy',
            'Kolab',
            array(
                'writer' => array(
                    'class' => 'Horde_Controller_ResponseWriter_WebDebug'
                )
            )
        );
        ob_start();
        $e->dispatch();
        $output = ob_get_clean();
        $this->assertContains(
            '<div><strong>Headers:',
            $output
        );
    }

    public function testDispatchError()
    {
        $e = new Horde_Kolab_FreeBusy(
            'DOESNOTEXISTS',
            'DOESNOTEXIST',
            array(
                'writer' => array(
                    'class' => 'Horde_Controller_ResponseWriter_WebDebug'
                )
            )
        );
        ob_start();
        $e->dispatch();
        $output = ob_get_clean();
        $this->assertContains(
            'Class Horde_Kolab_FreeBusy_DOESNOTEXISTS_Factory_DOESNOTEXIST does not exist',
            $output
        );
    }
}
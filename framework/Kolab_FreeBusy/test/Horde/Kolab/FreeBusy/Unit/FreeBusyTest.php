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
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the entry point into the export system.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
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
        $e = new Horde_Kolab_FreeBusy('Freebusy', 'Kolab');
        $e->dispatch();
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
<?php
/**
 * Test the base free/busy factory.
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
require_once __DIR__ . '/../../../Autoload.php';

/**
 * Test the base free/busy factory.
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
class Horde_Kolab_FreeBusy_Unit_Freebusy_Factory_BaseTest
extends Horde_Kolab_FreeBusy_TestCase
{
    public function testFreeBusyController()
    {
        $injector = $this->getInjector();
        $injector->setInstance(
            'Horde_Kolab_FreeBusy_Configuration',
            array()
        );
        $injector->setInstance(
            'Horde_Kolab_FreeBusy_Controller_MatchDict',
            $this->getTestMatchDict()
        );
        $factory = new Horde_Kolab_FreeBusy_Freebusy_Factory_Base($injector);
        $this->assertEquals(
            'Horde_Kolab_FreeBusy_Freebusy_Controller_Freebusy',
            $factory->createRequestConfiguration()->getControllerName()
        );
    }

    public function testNotFound()
    {
        $injector = $this->getInjector();
        $injector->setInstance(
            'Horde_Kolab_FreeBusy_Configuration',
            array('request_config' => array('prefix' => 'DOES_NOT_EXIST_'))
        );
        $injector->setInstance(
            'Horde_Kolab_FreeBusy_Controller_MatchDict',
            $this->getTestMatchDict()
        );
        $factory = new Horde_Kolab_FreeBusy_Freebusy_Factory_Base($injector);
        $this->assertEquals(
            'Horde_Kolab_FreeBusy_Controller_NotFound',
            $factory->createRequestConfiguration()->getControllerName()
        );
    }
}
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
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the base free/busy factory.
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
<?php
/**
 * Test the injector based connection factory.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../../Autoload.php';

/**
 * Test the injector based connection factory.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Class_Server_Factory_Connection_InjectorTest
extends PHPUnit_Framework_TestCase
{
    public function testMethodGetconnectionHasResultConnection()
    {
        $injector = new Horde_Injector(new Horde_Injector_TopLevel());
        $injector->bindImplementation(
            'Horde_Kolab_Server_Factory_Connection_Interface',
            'Horde_Kolab_Server_Factory_Connection_Mock'
        );
        $injector->setInstance(
            'Horde_Kolab_Server_Configuration',
            array()
        );
        $factory = new Horde_Kolab_Server_Factory_Connection_Injector($injector);
        $this->assertType(
            'Horde_Kolab_Server_Connection_Interface',
            $factory->getConnection()
        );
    }
}
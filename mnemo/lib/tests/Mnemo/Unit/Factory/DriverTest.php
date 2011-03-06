<?php
/**
 * Test the Mnemo library.
 *
 * PHP version 5
 *
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @category   Horde
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Mnemo
 * @package    Mnemo
 * @subpackage UnitTests
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../Autoload.php';

/**
 * Test the Mnemo library.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @category   Horde
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Mnemo
 * @package    Mnemo
 * @subpackage UnitTests
 */
class Mnemo_Unit_Factory_DriverTest extends Mnemo_TestCase
{
    public function testCreateSql()
    {
        $injector = $this->getInjector();
        $injector->setInstance('Horde_Db_Adapter', 'DUMMY');
        $factory = $injector->getInstance('Mnemo_Factory_Driver');
        $GLOBALS['conf']['storage']['driver'] = 'sql';
        $this->assertInstanceOf('Mnemo_Driver_Sql', $factory->create('test'));
    }

    public function testCreateKolab()
    {
        $injector = $this->getInjector();
        $injector->setInstance('Horde_Kolab_Storage', 'DUMMY');
        $factory = $injector->getInstance('Mnemo_Factory_Driver');
        $GLOBALS['conf']['storage']['driver'] = 'kolab';
        $this->assertInstanceOf('Mnemo_Driver_Kolab', $factory->create('test'));
    }
}

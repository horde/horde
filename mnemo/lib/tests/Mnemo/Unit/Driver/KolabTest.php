<?php
/**
 * Test the Kolab driver.
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
 * Test the Kolab driver.
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
class Mnemo_Unit_Driver_KolabTest extends Mnemo_TestCase
{
    public function testAdd()
    {
        $driver = $this->getKolabDriver();
        $id = $driver->add('TEST','Some test note.');
        $note = $driver->get($id);
        $this->assertEquals('TEST', $note['desc']);
    }

    public function testGetByUid()
    {
        $driver = $this->getKolabDriver();
        $id = $driver->add('TEST','Some test note.');
        $note = $driver->getByUID($id);
        $this->assertEquals('TEST', $note['desc']);
    }

    public function testListMemos()
    {
        $driver = $this->getKolabDriver();
        $id = $driver->add('TEST','Some test note.');
        $driver->retrieve();
        $this->assertEquals(1, count($driver->listMemos()));
    }
}

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
        $id = $driver->add('TEST', 'Some test note.');
        $note = $driver->getByUID($id);
        $this->assertEquals('TEST', $note['desc']);
    }

    public function testListMemos()
    {
        $driver = $this->getKolabDriver();
        $id = $driver->add('TEST', 'Some test note.');
        $driver->retrieve();
        $this->assertEquals(1, count($driver->listMemos()));
    }

    public function testDelete()
    {
        $driver = $this->getKolabDriver();
        $driver->add('TEST', 'Some test note.');
        $id = $driver->add('TEST', 'Some test note.');
        $driver->delete($id);
        $driver->retrieve();
        $this->assertEquals(1, count($driver->listMemos()));
    }

    public function testDeleteAll()
    {
        $driver = $this->getKolabDriver();
        $driver->add('TEST', 'Some test note.');
        $driver->add('TEST', 'Some test note.');
        $driver->deleteAll();
        $driver->retrieve();
        $this->assertEquals(0, count($driver->listMemos()));
    }

    public function testMove()
    {
        $driver = $this->getKolabDriver();
        $id = $driver->add('TEST', 'Some test note.');
        $driver->move($id, $this->other_share->getName());
        $driver->retrieve();
        $this->assertEquals(0, count($driver->listMemos()));
        $other_driver = $this->factory->create($this->other_share->getName());
        $other_driver->retrieve();
        $this->assertEquals(
            1,
            count($other_driver->listMemos())
        );
    }
}

<?php
/**
 * Test the Kolab driver.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Nag
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/nag
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2
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
 * See the enclosed file COPYING for license information (GPLv2). If you did not
 * receive this file, see http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @category   Horde
 * @package    Nag
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/nag
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2
 */
class Nag_Unit_Driver_KolabTest extends Nag_TestCase
{
    public function testAdd()
    {
        $driver = $this->getKolabDriver();
        $id = $driver->add('TEST','Some test task.');
        $task = $driver->get($id[0]);
        $this->assertEquals('TEST', $task->name);
    }

    /* public function testGetByUid() */
    /* { */
    /*     $driver = $this->getKolabDriver(); */
    /*     $id = $driver->add('TEST', 'Some test note.'); */
    /*     $note = $driver->getByUID($id); */
    /*     $this->assertEquals('TEST', $note['desc']); */
    /* } */

    public function testListTasks()
    {
        $driver = $this->getKolabDriver();
        $id = $driver->add('TEST', 'Some test task.');
        $driver->retrieve();
        $this->assertEquals(1, $driver->tasks->count());
    }

    public function testListSubTasks()
    {
        $driver = $this->getKolabDriver();
        $id = $driver->add('TEST', 'Some test task.');
        $driver->add(
            'SUB', 'Some sub task.', 0, 0, 0, 0.0, 0, '', 0, null, null, $id[0]
        );
        $driver->retrieve();
        $this->assertEquals(2, $driver->tasks->count());
    }

    /* public function testDelete() */
    /* { */
    /*     $driver = $this->getKolabDriver(); */
    /*     $driver->add('TEST', 'Some test note.'); */
    /*     $id = $driver->add('TEST', 'Some test note.'); */
    /*     $driver->delete($id); */
    /*     $driver->retrieve(); */
    /*     $this->assertEquals(1, count($driver->listMemos())); */
    /* } */

    public function testDeleteAll()
    {
        $driver = $this->getKolabDriver();
        $driver->add('TEST', 'Some test task.');
        $driver->add('TEST', 'Some test task.');
        $driver->retrieve();
        $this->assertEquals(2, $driver->tasks->count());
        $driver->deleteAll();
        $driver->retrieve();
        $this->assertEquals(0, $driver->tasks->count());
    }

    /* public function testMove() */
    /* { */
    /*     $driver = $this->getKolabDriver(); */
    /*     $id = $driver->add('TEST', 'Some test note.'); */
    /*     $driver->move($id, $this->other_share->getName()); */
    /*     $driver->retrieve(); */
    /*     $this->assertEquals(0, count($driver->listMemos())); */
    /*     $other_driver = $this->factory->create($this->other_share->getName()); */
    /*     $other_driver->retrieve(); */
    /*     $this->assertEquals( */
    /*         1, */
    /*         count($other_driver->listMemos()) */
    /*     ); */
    /* } */
}

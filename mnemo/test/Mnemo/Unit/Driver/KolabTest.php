<?php
/**
 * Test the Kolab driver.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Mnemo
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/apache
 * @link       http://www.horde.org/apps/mnemo
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../Autoload.php';

/**
 * Test the Kolab driver.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category   Horde
 * @package    Mnemo
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/apache
 * @link       http://www.horde.org/apps/mnemo
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
        $other_driver = $GLOBALS['injector']->getInstance('Mnemo_Factory_Driver')
            ->create($this->other_share->getName());
        $other_driver->retrieve();
        $this->assertEquals(
            1,
            count($other_driver->listMemos())
        );
    }
}

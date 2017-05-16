<?php
/**
 * Copyright 2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Backup
 * @subpackage UnitTests
 */

namespace Horde\Backup;

use Horde_Test_Case as TestCase;
use Horde\Backup\Writer;
use Horde\Backup\Stub;

/**
 * Testing backing up to and restoring from archives.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @copyright  2017 Horde LLC
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Backup
 * @subpackage UnitTests
 */
class BackupRestoreTest extends TestCase
{
    protected $_temp;
    protected $_clean = true;

    protected function _createBackup()
    {
        $this->_temp = sys_get_temp_dir() . '/' . uniqid('horde_backup_');
        mkdir($this->_temp);
    }

    public function tearDown()
    {
        if (!is_dir($this->_temp) || !$this->_clean) {
            return;
        }
        foreach (glob($this->_temp . '/*') as $file) {
            unlink($file);
        }
        rmdir($this->_temp);
    }

    public function testBackupMultipleUsers()
    {
        $this->_createBackup();
        $backup = new Writer($this->_temp);
        $application = new Stub\Application();
        $application2 = new Stub\Application2();
        $backup->backup('calendar', $application->backup());
        $backup->backup('addressbook', $application2->backup());
        $backup->save();
        $this->assertFileExists($this->_temp . '/john.zip');
        $this->assertFileExists($this->_temp . '/jane.zip');
        $this->_clean = false;
        return $this->_temp;
    }

    /**
     * @depends testBackupMultipleUsers
     */
    public function testRestoreMultipleUsers($temp)
    {
        $this->_temp = $temp;
        $backup = new Reader($this->_temp);
        $users = iterator_to_array($backup->listBackups());
        sort($users);
        $this->assertEquals(
            array($this->_temp . '/jane.zip', $this->_temp . '/john.zip'),
            $users
        );
        $data = $backup->restore();
        $this->assertInternalType('array', $data);
        $this->assertCount(2, $data);
        $this->assertArrayHasKey('calendar', $data);
        $this->assertArrayHasKey('addressbook', $data);
        $matrix = array();
        $applications = array(
            'calendar' => new Stub\Application(),
            'addressbook' => new Stub\Application2()
        );
        foreach ($data as $application => $collections) {
            foreach ($collections as $collection) {
                $user = $collection->getUser();
                $type = $collection->getType();
                $matrix[$user][$application][$type] = true;
                $this->assertTrue(
                    $applications[$application]->restore($collection)
                );
            }
        }
        ksort($matrix);
        $this->assertEquals(
            array(
                'jane' => array(
                    'calendar' => array(
                        'events' => true
                    ),
                    'addressbook' => array(
                        'addressbooks' => true,
                        'contacts' => true,
                    ),
                ),
                'john' => array(
                    'calendar' => array(
                        'events' => true,
                        'calendars' => true,
                    ),
                ),
            ),
            $matrix
        );
    }

    public function testBackupSingleUser()
    {
    }

    public function testBackupSingleApplication()
    {
    }

    public function testBackupToTar()
    {
    }
}

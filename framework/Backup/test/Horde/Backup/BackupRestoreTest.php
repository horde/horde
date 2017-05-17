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
use Horde\Backup;
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
        return $this->_backupTest(
            array(
                'calendar' => new Stub\Application(),
                'addressbook' => new Stub\Application2()
            ),
            null,
            array('john', 'jane')
        );
    }

    /**
     * @depends testBackupMultipleUsers
     */
    public function testRestoreMultipleUsers($temp)
    {
        $this->_restoreTest(
            $temp,
            array(
                'calendar' => new Stub\Application(),
                'addressbook' => new Stub\Application2()
            ),
            array('jane', 'john')
        );
    }

    public function testBackupSingleUser()
    {
        return $this->_backupTest(
            array(
                'calendar' => new Stub\Application(),
                'addressbook' => new Stub\Application2()
            ),
            array('jane'),
            array('jane')
        );
    }

    /**
     * @depends testBackupSingleUser
     */
    public function testRestoreSingleUser($temp)
    {
        $this->_restoreTest(
            $temp,
            array(
                'calendar' => new Stub\Application(),
                'addressbook' => new Stub\Application2()
            ),
            array('jane')
        );
    }

    public function testBackupSingleApplication()
    {
        return $this->_backupTest(
            array(
                'calendar' => new Stub\Application(),
            ),
            null,
            array('john', 'jane')
        );
    }

    /**
     * @depends testBackupSingleApplication
     */
    public function testRestoreSingleApplication($temp)
    {
        $this->_restoreTest(
            $temp,
            array(
                'calendar' => new Stub\Application(),
            ),
            array('jane', 'john')
        );
    }

    public function testBackupToTar()
    {
        return $this->_backupTest(
            array(
                'calendar' => new Stub\Application(),
                'addressbook' => new Stub\Application2()
            ),
            null,
            array('john', 'jane'),
            Backup::FORMAT_TAR
        );
    }

    /**
     * @depends testBackupToTar
     */
    public function testRestoreFromTar($temp)
    {
        $this->_restoreTest(
            $temp,
            array(
                'calendar' => new Stub\Application(),
                'addressbook' => new Stub\Application2()
            ),
            array('jane', 'john'),
            Backup::FORMAT_TAR
        );
    }

    protected function _backupTest(
        $applications, $backupUsers, $users, $format = Backup::FORMAT_ZIP
    )
    {
        $this->_createBackup();
        $backup = new Writer($this->_temp);
        foreach ($applications as $application => $instance) {
            $backup->backup($application, $instance->backup($users));
        }
        $backup->save($format);
        foreach ($users as $user) {
            $this->assertFileExists(
                $this->_temp . '/' . $user
                    . ($format == Backup::FORMAT_ZIP ? '.zip' : '.tar')
            );
        }
        $this->_clean = false;
        return $this->_temp;
    }

    protected function _restoreTest(
        $temp, $applications, $users, $format = Backup::FORMAT_ZIP
    )
    {
        $this->_clean = true;
        $this->_temp = $temp;
        $backup = new Reader($this->_temp);
        $backups = iterator_to_array($backup->listBackups());
        foreach ($users as $user) {
            $this->assertContains(
                $this->_temp . '/' . $user
                    . ($format == Backup::FORMAT_ZIP ? '.zip' : '.tar'),
                $backups
            );
        }
        $data = $backup->restore();
        $this->assertInternalType('array', $data);
        $this->assertCount(count($applications), $data);
        foreach (array_keys($applications) as $application) {
            $this->assertArrayHasKey($application, $data);
        }
        $matrix = array();
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
        $expected = array(
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
        );
        if (!isset($applications['addressbook'])) {
            unset($expected['jane']['addressbook']);
        }
        if (!in_array('john', $users)) {
            unset($expected['john']);
        }
        $this->assertEquals($expected, $matrix);
    }
}

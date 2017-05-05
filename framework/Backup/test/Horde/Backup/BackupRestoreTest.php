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
        $this->_temp = sys_get_temp_dir() . '/' . uniqid();
        mkdir($this->_temp);
    }

    protected function _cleanBackup()
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
        $backup = new Backup($this->_temp);
        $application = new Stub\Application();
        $application2 = new Stub\Application2();
        $backup->backup('calendar', $application->backup());
        $backup->backup('adressbook', $application2->backup());
        $backup->save();
        $this->assertFileExists($this->_temp . '/john.zip');
        $this->assertFileExists($this->_temp . '/jane.zip');
        $this->_clean = false;
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

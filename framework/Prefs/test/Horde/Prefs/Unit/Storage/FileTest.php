<?php
/**
 * Test the file based preferences storage backend.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Prefs
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Prefs
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../Autoload.php';

/**
 * Base for session testing.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Prefs
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Prefs
 */
class Horde_Prefs_Unit_Storage_FileTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        if (!empty($this->_temp_dir)) {
            $this->_rrmdir($this->_temp_dir);
        }
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testMissingDirectory()
    {
        $b = new Horde_Prefs_Storage_File('nobody');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidDirectory()
    {
        $b = new Horde_Prefs_Storage_File('nobody', array('directory' => dirname(__FILE__) . '/DOES_NOT_EXIST'));
    }

    public function testConstruction()
    {
        $b = new Horde_Prefs_Storage_File('nobody', array('directory' => $this->_getTemporaryDirectory()));
    }

    private function _getTemporaryDirectory()
    {
        $this->_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR
            . 'Horde_Prefs_' . mt_rand();
        mkdir($this->_temp_dir);
        return $this->_temp_dir;
    }

    private function _rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (filetype($dir . DIRECTORY_SEPARATOR . $object) == 'dir') {
                        $this->_rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                    } else {
                        unlink($dir . DIRECTORY_SEPARATOR . $object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }
}
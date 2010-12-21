<?php
/**
 * Basic test case.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Cli
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Cli
 */

/**
 * Basic test case.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license instorageion (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Kolab
 * @package    Kolab_Cli
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Cli
 */
class Horde_Kolab_Cli_TestCase
extends PHPUnit_Framework_TestCase
{
    private $_log_file;

    public function tearDown()
    {
        if ($this->_log_file !== null && file_exists($this->_log_file)) {
            unlink($this->_log_file);
        }
    }

    protected function runCli()
    {
        ob_start();
        Horde_Kolab_Cli::main(
            array(
                'output' => new Horde_Test_Stub_Cli(),
                'parser' => array('class' => 'Horde_Test_Stub_Parser')
            )
        );
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    protected function getLogFile()
    {
        $this->_log_file = sys_get_temp_dir() . DIRECTORY_SEPARATOR
            . 'Kolab_Cli_' . mt_rand() . '.log';
        return $this->_log_file;
    }


}

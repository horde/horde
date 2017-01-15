<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl LGPL
 * @category   Horde
 * @package    Spam
 * @subpackage UnitTests
 */
class Horde_Spam_ProgramTest extends Horde_Spam_TestBase
{
    public function setUp()
    {
        parent::setUp();
        $this->out_template = sys_get_temp_dir() . '/horde_spam.*.out';
        $this->spam_out = str_replace('*', 'spam', $this->out_template);
        $this->ham_out = str_replace('*', 'ham', $this->out_template);
        $this->binary = 'php -r \'file_put_contents(str_replace("*", $argv[1], "'
            . $this->out_template . '"), stream_get_contents(STDIN));\'';
    }

    public function tearDown()
    {
        foreach (glob($this->out_template) as $out_file) {
            unlink($out_file);
        }
    }

    public function testReportSpamSuccess()
    {
        $horde_spam = new Horde_Spam_Program($this->binary . ' spam');
        $horde_spam->setLogger(
            new Horde_Log_Logger(new Horde_Log_Handler_Cli())
        );
        $this->_testReportSpamSuccess($horde_spam);
        $this->assertFileEquals($this->spam_file, $this->spam_out);
    }

    public function testReportSpamSuccessAsStream()
    {
        $horde_spam = new Horde_Spam_Program($this->binary . ' spam');
        $horde_spam->setLogger(
            new Horde_Log_Logger(new Horde_Log_Handler_Cli())
        );
        $this->_testReportSpamSuccess($horde_spam, true);
        $this->assertFileEquals($this->spam_file, $this->spam_out);
    }

    public function testReportHamSuccess()
    {
        $horde_spam = new Horde_Spam_Program($this->binary . ' ham');
        $horde_spam->setLogger(
            new Horde_Log_Logger(new Horde_Log_Handler_Cli())
        );
        $this->_testReportHamSuccess($horde_spam);
        $this->assertFileEquals($this->ham_file, $this->ham_out);
    }
}

<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl LGPL
 * @category   Horde
 * @package    Spam
 * @subpackage UnitTests
 */
class Horde_Spam_TestBase extends Horde_Test_Case
{
    public function setUp()
    {
        $this->spam_file = __DIR__ . '/fixtures/sample-spam.txt';
        $this->spam = file_get_contents($this->spam_file);
        $this->ham_file = __DIR__ . '/fixtures/sample-nonspam.txt';
        $this->ham = file_get_contents($this->ham_file);
    }

    public function _testReportSpamSuccess(Horde_Spam_Base $spam, $stream = false)
    {
        $content = $stream ? fopen($this->spam_file, 'r') : $this->spam;
        $this->assertEquals(
            1,
            $spam->report(array($content), Horde_Spam::SPAM)
        );
    }

    public function _testReportHamSuccess(Horde_Spam_Base $spam, $stream = false)
    {
        $content = $stream ? fopen($this->ham_file, 'r') : $this->ham;
        $this->assertEquals(
            1,
            $spam->report(array($content), Horde_Spam::INNOCENT)
        );
    }
}

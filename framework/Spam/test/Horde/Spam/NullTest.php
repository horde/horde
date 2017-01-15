<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl LGPL
 * @category   Horde
 * @package    Spam
 * @subpackage UnitTests
 */
class Horde_Spam_NullTest extends Horde_Spam_TestBase
{
    public function testReportSpamSuccess()
    {
        $this->_testReportSpamSuccess(new Horde_Spam_Null(true));
    }

    public function testReportSpamFail()
    {
        $spam = new Horde_Spam_Null(false);
        $this->assertEquals(
            0,
            $spam->report(array($this->spam), Horde_Spam::SPAM)
        );
    }

    public function testReportHamSuccess()
    {
        $this->_testReportHamSuccess(new Horde_Spam_Null(true));
    }

    public function testReportHamFail()
    {
        $spam = new Horde_Spam_Null(false);
        $this->assertEquals(
            0,
            $spam->report(array($this->ham), Horde_Spam::INNOCENT)
        );
    }
}

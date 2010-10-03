<?php
/**
 * Test cases for Ingo_Script_sieve:: class
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author     Jason M. Felice <jason.m.felice@gmail.com>
 * @package    Ingo
 * @subpackage UnitTests
 */

require_once dirname(__FILE__) . '/TestBase.php';

class Ingo_SieveTest extends Ingo_TestBase {

    function store($ob)
    {
        return $GLOBALS['ingo_storage']->store($ob);
    }

    function setUp()
    {
        $GLOBALS['conf']['spam'] = array('enabled' => true,
                                         'char' => '*',
                                         'header' => 'X-Spam-Level');
        $GLOBALS['ingo_storage'] = Ingo_Storage::factory(
            'mock',
            array('maxblacklist' => 3,
                  'maxwhitelist' => 3));
        $GLOBALS['ingo_script'] = Ingo_Script::factory(
            'sieve',
            array('spam_compare' => 'string',
                  'spam_header' => 'X-Spam-Level',
                  'spam_char' => '*',
                  'date_format' => '%x',
                  'time_format' => '%R'));
    }

    function testForwardKeep()
    {
        $forward = new Ingo_Storage_Forward();
        $forward->setForwardAddresses('joefabetes@example.com');
        $forward->setForwardKeep(true);

        $this->store($forward);
        $this->assertScript('if true {
redirect "joefabetes@example.com";
}
if true {
keep;
stop;
}');
    }

    function testForwardNoKeep()
    {
        $forward = new Ingo_Storage_Forward();
        $forward->setForwardAddresses('joefabetes@example.com');
        $forward->setForwardKeep(false);

        $this->store($forward);
        $this->assertScript('if true {
redirect "joefabetes@example.com";
stop;
}');
    }

    function testBlacklistMarker()
    {
        $bl = new Ingo_Storage_Blacklist(3);
        $bl->setBlacklist(array('spammer@example.com'));
        $bl->setBlacklistFolder(Ingo::BLACKLIST_MARKER);

        $this->store($bl);
        $this->assertScript('require "imapflags";
if address :all :comparator "i;ascii-casemap" :is ["From", "Sender", "Resent-From"] "spammer@example.com"  {
addflag "\\\\Deleted";
keep;
removeflag "\\\\Deleted";
stop;
}');
    }

    function testWhitelist()
    {
        $wl = new Ingo_Storage_Whitelist(3);
        $wl->setWhitelist(array('spammer@example.com'));

        $this->store($wl);
        $this->assertScript('if address :all :comparator "i;ascii-casemap" :is ["From", "Sender", "Resent-From"] "spammer@example.com"  {
keep;
stop;
}');
    }

    function testVacationDisabled()
    {
        $vacation = new Ingo_Storage_VacationTest();
        $vacation->setVacationAddresses(array('from@example.com'));
        $vacation->setVacationSubject('Subject');
        $vacation->setVacationReason("Because I don't like working!");

        $this->store($vacation);
        $this->assertScript('');
    }

    function testVacationEnabled()
    {
        $vacation = new Ingo_Storage_VacationTest();
        $vacation->setVacationAddresses(array('from@example.com'));
        $vacation->setVacationSubject('Subject');
        $vacation->setVacationReason("Because I don't like working!");

        $this->store($vacation);
        $this->_enableRule(Ingo_Storage::ACTION_VACATION);

        $this->assertScript('require ["vacation", "regex"];
if allof ( not exists ["list-help", "list-unsubscribe", "list-subscribe", "list-owner", "list-post", "list-archive", "list-id", "Mailing-List"], not header :comparator "i;ascii-casemap" :is "Precedence" ["list", "bulk", "junk"], not header :comparator "i;ascii-casemap" :matches "To" "Multiple recipients of*" ) {
vacation :days 7 :addresses "from@example.com" :subject "Subject" "Because I don\'t like working!";
}');
    }

    function testSpamDisabled()
    {
        $spam = new Ingo_Storage_Spam();
        $spam->setSpamLevel(7);
        $spam->setSpamFolder("Junk");

        $this->store($spam);
        $this->assertScript('');
    }

    function testSpamEnabled()
    {
        $spam = new Ingo_Storage_Spam();
        $spam->setSpamLevel(7);
        $spam->setSpamFolder("Junk");

        $this->store($spam);
        $this->_enableRule(Ingo_Storage::ACTION_SPAM);
        $this->assertScript('require "fileinto";
if header :comparator "i;ascii-casemap" :contains "X-Spam-Level" "*******"  {
fileinto "Junk";
stop;
}');
    }

}

<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category   Horde
 * @copyright  2014 Horde LLC
 * @license    http://www.horde.org/licenses/apache ASL
 * @package    Ingo
 * @subpackage UnitTests
 */

/**
 * Test cases for Ingo_Script_Sieve class
 *
 * @author     Jason M. Felice <jason.m.felice@gmail.com>
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/apache ASL
 * @package    Ingo
 * @subpackage UnitTests
 */

class Ingo_Unit_SieveTest extends Ingo_Unit_TestBase
{
    public function setUp()
    {
        parent::setUp();
        $this->script = new Ingo_Script_Sieve(array(
            'date_format' => '%x',
            'skip' => array(),
            'spam_compare' => 'string',
            'spam_header' => 'X-Spam-Level',
            'spam_char' => '*',
            'time_format' => '%R',
            'storage' => $this->storage,
            'transport' => array(
                Ingo::RULE_ALL => array(
                    'driver' => 'Null'
                )
            )
        ));
    }

    public function testForwardKeep()
    {
        $forward = new Ingo_Storage_Forward();
        $forward->setForwardAddresses('joefabetes@example.com');
        $forward->setForwardKeep(true);
        $this->storage->store($forward);
        $this->_enableRule(Ingo_Storage::ACTION_FORWARD);
        $this->_assertScript('if true {
redirect "joefabetes@example.com";
}
if true {
keep;
stop;
}');
    }

    public function testForwardNoKeep()
    {
        $forward = new Ingo_Storage_Forward();
        $forward->setForwardAddresses('joefabetes@example.com');
        $forward->setForwardKeep(false);
        $this->storage->store($forward);
        $this->_enableRule(Ingo_Storage::ACTION_FORWARD);
        $this->_assertScript('if true {
redirect "joefabetes@example.com";
stop;
}');
    }

    public function testBlacklistMarker()
    {
        $bl = new Ingo_Storage_Blacklist(3);
        $bl->setBlacklist(array('spammer@example.com'));
        $bl->setBlacklistFolder(Ingo::BLACKLIST_MARKER);
        $this->storage->store($bl);
        $this->_assertScript('require "imap4flags";
if address :all :comparator "i;ascii-casemap" :is ["From", "Sender", "Resent-From"] "spammer@example.com"  {
addflag ["\\\\Deleted"];
keep;
removeflag ["\\\\Deleted"];
stop;
}');
    }

    public function testWhitelist()
    {
        $wl = new Ingo_Storage_Whitelist(3);
        $wl->setWhitelist(array('spammer@example.com'));
        $this->storage->store($wl);
        $this->_assertScript('if address :all :comparator "i;ascii-casemap" :is ["From", "Sender", "Resent-From"] "spammer@example.com"  {
keep;
stop;
}');
    }

    public function testVacationDisabled()
    {
        $vacation = new Ingo_Storage_VacationTest();
        $vacation->setVacationAddresses(array('from@example.com'));
        $vacation->setVacationSubject('Subject');
        $vacation->setVacationReason("Because I don't like working!");
        $this->storage->store($vacation);
        $this->_assertScript('');
    }

    public function testVacationEnabled()
    {
        $vacation = new Ingo_Storage_VacationTest();
        $vacation->setVacationAddresses(array('from@example.com'));
        $vacation->setVacationSubject('Subject');
        $vacation->setVacationReason("Because I don't like working!");
        $this->storage->store($vacation);
        $this->_enableRule(Ingo_Storage::ACTION_VACATION);
        $this->_assertScript('require ["vacation", "regex"];
if allof ( not exists "list-help", not exists "list-unsubscribe", not exists "list-subscribe", not exists "list-owner", not exists "list-post", not exists "list-archive", not exists "list-id", not exists "Mailing-List", not header :comparator "i;ascii-casemap" :is "Precedence" ["list", "bulk", "junk"], not header :comparator "i;ascii-casemap" :matches "To" "Multiple recipients of*" ) {
vacation :days 7 :addresses "from@example.com" :subject "Subject" "Because I don\'t like working!";
}');
    }

    public function testSpamDisabled()
    {
        $spam = new Ingo_Storage_Spam();
        $spam->setSpamLevel(7);
        $spam->setSpamFolder("Junk");
        $this->storage->store($spam);
        $this->_assertScript('');
    }

    public function testSpamEnabled()
    {
        $spam = new Ingo_Storage_Spam();
        $spam->setSpamLevel(7);
        $spam->setSpamFolder("Junk");
        $this->storage->store($spam);
        $this->_enableRule(Ingo_Storage::ACTION_SPAM);
        $this->_assertScript('require "fileinto";
if header :comparator "i;ascii-casemap" :contains "X-Spam-Level" "*******"  {
fileinto "Junk";
stop;
}');
    }
}

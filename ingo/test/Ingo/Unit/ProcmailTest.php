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
 * Test cases for Ingo_Script_Procmail class
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

class Ingo_Unit_ProcmailTest extends Ingo_Unit_TestBase
{
    public function setUp()
    {
        parent::setUp();
        $this->script = new Ingo_Script_Procmail(array(
            'path_style' => 'mbox',
            'skip' => array(),
            'spam_compare' => 'string',
            'spam_header' => 'X-Spam-Level',
            'spam_char' => '*',
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
        $this->_assertScript(':0 c
{
:0
*$ ! ^From *\/[^  ]+
*$ ! ^Sender: *\/[^   ]+
*$ ! ^From: *\/[^     ]+
*$ ! ^Reply-to: *\/[^     ]+
{
OUTPUT = `formail -zxFrom:`
}
:0 E
{
OUTPUT = $MATCH
}
:0 c
* !^FROM_MAILER
* !^X-Loop: to-joefabetes@example.com
| formail -A"X-Loop: to-joefabetes@example.com" | $SENDMAIL -oi -f $OUTPUT joefabetes@example.com
:0 E
$DEFAULT
:0
/dev/null
}');
    }

    public function testForwardNoKeep()
    {
        $forward = new Ingo_Storage_Forward();
        $forward->setForwardAddresses('joefabetes@example.com');
        $forward->setForwardKeep(false);
        $this->storage->store($forward);
        $this->_enableRule(Ingo_Storage::ACTION_FORWARD);
        $this->_assertScript(':0
{
:0
*$ ! ^From *\/[^  ]+
*$ ! ^Sender: *\/[^   ]+
*$ ! ^From: *\/[^     ]+
*$ ! ^Reply-to: *\/[^     ]+
{
OUTPUT = `formail -zxFrom:`
}
:0 E
{
OUTPUT = $MATCH
}
:0 c
* !^FROM_MAILER
* !^X-Loop: to-joefabetes@example.com
| formail -A"X-Loop: to-joefabetes@example.com" | $SENDMAIL -oi -f $OUTPUT joefabetes@example.com
:0 E
$DEFAULT
:0
/dev/null
}');
    }

    public function testBlacklistWithFolder()
    {
        $bl = new Ingo_Storage_Blacklist(3);
        $bl->setBlacklist(array('spammer@example.com'));
        $bl->setBlacklistFolder('Junk');
        $this->storage->store($bl);
        $this->_assertScript(':0
* ^From:(.*\<)?spammer@example\.com
Junk');
    }

    public function testBlacklistMarker()
    {
        $bl = new Ingo_Storage_Blacklist(3);
        $bl->setBlacklist(array('spammer@example.com'));
        $bl->setBlacklistFolder(Ingo::BLACKLIST_MARKER);
        $this->storage->store($bl);
        $this->_assertScript(':0
* ^From:(.*\<)?spammer@example\.com
++DELETE++');
    }

    public function testBlacklistDiscard()
    {
        $bl = new Ingo_Storage_Blacklist(3);
        $bl->setBlacklist(array('spammer@example.com'));
        $bl->setBlacklistFolder(null);
        $this->storage->store($bl);
        $this->_assertScript(':0
* ^From:(.*\<)?spammer@example\.com
/dev/null');
    }

    public function testWhitelist()
    {
        $wl = new Ingo_Storage_Whitelist(3);
        $wl->setWhitelist(array('spammer@example.com'));
        $this->storage->store($wl);
        $this->_assertScript(':0
* ^From:(.*\<)?spammer@example\.com
$DEFAULT');
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
        $this->_assertScript(':0
{
:0
* ^TO_from@example.com
{
FILEDATE=`test -f ${VACATION_DIR:-.}/\'.vacation.from@example.com\' && ls -lcn --time-style=+%s ${VACATION_DIR:-.}/\'.vacation.from@example.com\' | awk \'{ print $6 + (604800) }\'`
DATE=`date +%s`
DUMMY=`test -f ${VACATION_DIR:-.}/\'.vacation.from@example.com\' && test $FILEDATE -le $DATE && rm ${VACATION_DIR:-.}/\'.vacation.from@example.com\'`
:0 h
SUBJECT=| formail -xSubject:
:0 Whc: ${VACATION_DIR:-.}/vacation.lock
{
:0 Wh
* ^TO_from@example.com
* !^X-Loop: from@example.com
* !^X-Spam-Flag: YES
* !^FROM_DAEMON
| formail -rD 8192 ${VACATION_DIR:-.}/.vacation.from@example.com
:0 eh
| (formail -rI"Precedence: junk" \
-a"From: <from@example.com>" \
-A"X-Loop: from@example.com" \
-i"Subject: Subject (Re: $SUBJECT)" ; \
echo -e "Because I don\'t like working!" \
) | $SENDMAIL -ffrom@example.com -oi -t
}
}
}');
    }
}

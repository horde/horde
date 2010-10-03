<?php
/**
 * Test cases for Ingo_Script_procmail:: class
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author     Jason M. Felice <jason.m.felice@gmail.com>
 * @package    Ingo
 * @subpackage UnitTests
 */

require_once dirname(__FILE__) . '/TestBase.php';

class Ingo_ProcmailTest extends Ingo_TestBase {

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
            'procmail',
            array('path_style' => 'mbox',
                  'spam_compare' => 'string',
                  'spam_header' => 'X-Spam-Level',
                  'spam_char' => '*'));
    }

    function testForwardKeep()
    {
        $forward = new Ingo_Storage_Forward();
        $forward->setForwardAddresses('joefabetes@example.com');
        $forward->setForwardKeep(true);

        $this->store($forward);
        $this->assertScript(':0 c
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

    function testForwardNoKeep()
    {
        $forward = new Ingo_Storage_Forward();
        $forward->setForwardAddresses('joefabetes@example.com');
        $forward->setForwardKeep(false);

        $this->store($forward);
        $this->assertScript(':0
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

    function testBlacklistWithFolder()
    {
        $bl = new Ingo_Storage_Blacklist(3);
        $bl->setBlacklist(array('spammer@example.com'));
        $bl->setBlacklistFolder('Junk');

        $this->store($bl);
        $this->assertScript(':0
* ^From:(.*\<)?spammer@example\.com
Junk');
    }

    function testBlacklistMarker()
    {
        $bl = new Ingo_Storage_Blacklist(3);
        $bl->setBlacklist(array('spammer@example.com'));
        $bl->setBlacklistFolder(Ingo::BLACKLIST_MARKER);

        $this->store($bl);
        $this->assertScript(':0
* ^From:(.*\<)?spammer@example\.com
++DELETE++');
    }

    function testBlacklistDiscard()
    {
        $bl = new Ingo_Storage_Blacklist(3);
        $bl->setBlacklist(array('spammer@example.com'));
        $bl->setBlacklistFolder(null);

        $this->store($bl);
        $this->assertScript(':0
* ^From:(.*\<)?spammer@example\.com
/dev/null');
    }

    function testWhitelist()
    {
        $wl = new Ingo_Storage_Whitelist(3);
        $wl->setWhitelist(array('spammer@example.com'));

        $this->store($wl);
        $this->assertScript(':0
* ^From:(.*\<)?spammer@example\.com
$DEFAULT');
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

        $this->assertScript(':0
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
}');
    }

}

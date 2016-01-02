<?php
/**
 * Copyright 2014-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category   Horde
 * @copyright  2014-2016 Horde LLC
 * @license    http://www.horde.org/licenses/apache ASL
 * @package    Ingo
 * @subpackage UnitTests
 */

/**
 * Test cases for Ingo_Script_Maildrop class
 *
 * @author     Jason M. Felice <jason.m.felice@gmail.com>
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2014-2016 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/apache ASL
 * @package    Ingo
 * @subpackage UnitTests
 */

class Ingo_Unit_MaildropTest extends Ingo_Unit_TestBase
{
    public function setUp()
    {
        parent::setUp();

        $this->script = new Ingo_Script_Maildrop(array(
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
        $forward = new Ingo_Rule_System_Forward();
        $forward->addAddresses('joefabetes@example.com');
        $forward->keep = true;

        $this->storage->updateRule($forward);

        $this->_assertScript('if( \
/^From:\s*.*/:h \
)
exception {
cc "! joefabetes@example.com"
to "${DEFAULT}"
}');
    }

    public function testForwardNoKeep()
    {
        $forward = new Ingo_Rule_System_Forward();
        $forward->addAddresses('joefabetes@example.com');
        $forward->keep = false;

        $this->storage->updateRule($forward);

        $this->_assertScript('if( \
/^From:\s*.*/:h \
)
exception {
cc "! joefabetes@example.com"
exit
}');
    }

    public function testBlacklistWithFolder()
    {
        $bl = new Ingo_Rule_System_Blacklist();
        $bl->addAddresses('spammer@example.com');
        $bl->mailbox = 'Junk';

        $this->storage->updateRule($bl);

        $this->_assertScript('if( \
/^From:\s*.*spammer@example\.com/:h \
)
exception {
to Junk
}');
    }

    public function testBlacklistMarker()
    {
        $bl = new Ingo_Rule_System_Blacklist();
        $bl->addAddresses('spammer@example.com');
        $bl->mailbox = Ingo_Rule_System_Blacklist::DELETE_MARKER;

        $this->storage->updateRule($bl);

        $this->_assertScript('if( \
/^From:\s*.*spammer@example\.com/:h \
)
exception {
to ++DELETE++
}');
    }

    public function testBlacklistDiscard()
    {
        $bl = new Ingo_Rule_System_Blacklist();
        $bl->addAddresses('spammer@example.com');

        $this->storage->updateRule($bl);

        $this->_assertScript('if( \
/^From:\s*.*spammer@example\.com/:h \
)
exception {
exit
}');
    }

    public function testWhitelist()
    {
        $wl = new Ingo_Rule_System_Whitelist();
        $wl->addAddresses('spammer@example.com');

        $this->storage->updateRule($wl);

        $this->_assertScript('if( \
/^From:\s*.*spammer@example\.com/:h \
)
exception {
to "${DEFAULT}"
}');
    }

}

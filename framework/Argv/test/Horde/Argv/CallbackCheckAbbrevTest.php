<?php

require_once dirname(__FILE__) . '/TestCase.php';

/**
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     Mike Naberezny <mike@maintainable.com>
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @category   Horde
 * @package    Horde_Argv
 * @subpackage UnitTests
 */

class Horde_Argv_CallbackCheckAbbrevTest extends Horde_Argv_TestCase
{
    public function setUp()
    {
        $this->parser = new Horde_Argv_Parser();
        $this->parser->addOption('--foo-bar', array('action' => 'callback',
                                                    'callback' => array($this, 'checkAbbrev')));
    }

    public function checkAbbrev($option, $opt, $value, $parser)
    {
        $this->assertEquals($opt, '--foo-bar');
    }

    public function testAbbrevCallbackExpansion()
    {
        $this->assertParseOk(array('--foo'), array(), array());
    }
}

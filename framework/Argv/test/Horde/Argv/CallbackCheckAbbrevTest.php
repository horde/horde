<?php

require_once __DIR__ . '/TestCase.php';

/**
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @author     Mike Naberezny <mike@maintainable.com>
 * @license    http://www.horde.org/licenses/bsd BSD
 * @category   Horde
 * @package    Argv
 * @subpackage UnitTests
 */

class Horde_Argv_CallbackCheckAbbrevTest extends Horde_Argv_TestCase
{
    public function setUp()
    {
        parent::setUp();
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

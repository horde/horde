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

class Horde_Argv_MatchAbbrevTest extends Horde_Argv_TestCase
{
    public function testMatchAbbrev()
    {
        $this->assertEquals(Horde_Argv_Parser::matchAbbrev("--f",
            array("--foz" => null,
                  "--foo" => null,
                  "--fie" => null,
                  "--f"   => null)),
            '--f');
    }

    public function testMatchAbbrevError()
    {
        $s = '--f';
        $wordmap = array("--foz" => null, "--foo" => null, "--fie" => null);

        try {
            Horde_Argv_Parser::matchAbbrev($s, $wordmap);
            $this->fail();
        } catch (Horde_Argv_BadOptionException $e) {
            $this->assertEquals("ambiguous option: --f (--fie, --foo, --foz?)",
                                $e->getMessage());
        }
    }
}

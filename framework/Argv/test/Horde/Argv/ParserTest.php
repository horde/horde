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

class Horde_Argv_ParserTest extends Horde_Argv_TestCase
{
    public function setUp()
    {
        $this->parser = new Horde_Argv_Parser();
        $this->parser->addOption('-v', '--verbose', '-n', '--noisy',
                                  array('action' => 'store_true', 'dest' => 'verbose'));
        $this->parser->addOption('-q', '--quiet', '--silent',
                                  array('action' => 'store_false', 'dest' => 'verbose'));
    }

    public function testAddOptionNoOption()
    {
        $this->assertTypeError(array($this->parser, 'addOption'),
                               "not an Option instance: NULL", array(null));
    }

    public function testAddOptionInvalidArguments()
    {
        $this->assertTypeError(array($this->parser, 'addOption'),
                               "invalid arguments", null);
    }

    public function testGetOption()
    {
        $opt1 = $this->parser->getOption("-v");
        $this->assertType('Horde_Argv_Option', $opt1);
        $this->assertEquals($opt1->shortOpts, array("-v", "-n"));
        $this->assertEquals($opt1->longOpts, array("--verbose", "--noisy"));
        $this->assertEquals($opt1->action, "store_true");
        $this->assertEquals($opt1->dest, "verbose");
    }

    public function testGetOptionEquals()
    {
        $opt1 = $this->parser->getOption("-v");
        $opt2 = $this->parser->getOption("--verbose");
        $opt3 = $this->parser->getOption("-n");
        $opt4 = $this->parser->getOption("--noisy");
        $this->assertEquals($opt1, $opt2);
        $this->assertEquals($opt1, $opt3);
        $this->assertEquals($opt1, $opt4);
    }

    public function testHasOption()
    {
        $this->assertTrue($this->parser->hasOption("-v"));
        $this->assertTrue($this->parser->hasOption("--verbose"));
    }

    public function assertRemoved()
    {
        $this->assertNull($this->parser->getOption("-v"));
        $this->assertNull($this->parser->getOption("--verbose"));
        $this->assertNull($this->parser->getOption("-n"));
        $this->assertNull($this->parser->getOption("--noisy"));

        $this->assertFalse($this->parser->hasOption("-v"));
        $this->assertFalse($this->parser->hasOption("--verbose"));
        $this->assertFalse($this->parser->hasOption("-n"));
        $this->assertFalse($this->parser->hasOption("--noisy"));

        $this->assertTrue($this->parser->hasOption("-q"));
        $this->assertTrue($this->parser->hasOption("--silent"));
    }

    public function testRemoveShortOpt()
    {
        $this->parser->removeOption('-n');
        $this->assertRemoved();
    }

    public function testRemoveLongOpt()
    {
        $this->parser->removeOption('--verbose');
        $this->assertRemoved();
    }

    public function testRemoveNonexistent()
    {
        $this->assertRaises(array($this->parser, 'removeOption'), array('foo'), 'InvalidArgumentException', "no such option 'foo'");
    }

    /**
    def test_refleak(self):
        # If a Horde_Argv_Parser is carrying around a reference to a large
        # object, various cycles can prevent it from being GC'd in
        # a timely fashion.  destroy() breaks the cycles to ensure stuff
        # can be cleaned up.
        big_thing = [42]
        refcount = sys.getrefcount(big_thing)
        parser = Horde_Argv_Parser()
        parser.addOption("-a", "--aaarggh")
        parser.big_thing = big_thing

        parser.destroy()
        del parser
        $this->assertEquals(refcount, sys.getrefcount(big_thing))
    */

}

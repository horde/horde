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

class Horde_Argv_CallbackTest extends Horde_Argv_TestCase
{
    public function setUp()
    {
        $options = array(
            new Horde_Argv_Option('-x', null,
                array('action' => 'callback', 'callback' => array($this, 'processOpt'))),
            new Horde_Argv_Option('-f', '--file',
                array('action' => 'callback',
                      'callback' => array($this, 'processOpt'),
                      'type' => 'string',
                      'dest' => 'filename')),
        );

        $this->parser = new Horde_Argv_Parser(array('optionList' => $options));
    }

    public function processOpt($option, $opt, $value, $parser_)
    {
        if ($opt == '-x') {
            $this->assertEquals(array('-x'), $option->shortOpts);
            $this->assertEquals(array(), $option->longOpts);
            $this->assertType(get_class($this->parser), $parser_);
            $this->assertNull($value);
            $this->assertEquals(array('filename' => null), iterator_to_array($parser_->values));

            $parser_->values->x = 42;
        } else if ($opt == '--file') {
            $this->assertEquals(array('-f'), $option->shortOpts);
            $this->assertEquals(array('--file'), $option->longOpts);
            $this->assertType(gettype($this->parser), $parser_);
            $this->assertEquals('foo', $value);
            $this->assertEquals(array('filename' => null, 'x' => 42), iterator_to_array($parser_->values));

            $parser_->values->{$option->dest} = $value;
        } else {
            $this->fail(sprintf('Unknown option %r in processOpt.', $opt));
        }
    }

    public function testCallback()
    {
        $this->assertParseOk(array('-x', '--file=foo'),
                             array('filename' => 'foo', 'x' => 42),
                             array());
    }

    public function testCallbackHelp()
    {
        // This test was prompted by SF bug #960515 -- the point is
        // not to inspect the help text, just to make sure that
        // formatHelp() doesn't crash.
        $parser = new Horde_Argv_Parser(array('usage' => Horde_Argv_Option::SUPPRESS_USAGE));
        $parser->removeOption('-h');
        $parser->addOption('-t', '--test',
                           array('action' => 'callback', 'callback' => array($this, 'returnNull'),
                                 'type' => 'string', 'help' => 'foo'));

        $expectedHelp = "Options:\n  -t TEST, --test=TEST  foo\n";
        $this->assertHelp($parser, $expectedHelp);
    }

    public function returnNull()
    {}
}

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

class Horde_Argv_CallbackMeddleArgsTest extends Horde_Argv_TestCase
{
    public function setUp()
    {
        $options = array();
        for ($i = -1; $i > -6; $i--) {
            $options[] = $this->makeOption((string)$i, array('action' => 'callback',
                                                             'callback' => array($this, 'process_n'),
                                                             'dest' => 'things'));
        }
        $this->parser = new Horde_Argv_Parser(array('optionList' => $options));
    }

    /**
     * Callback that meddles in rargs, largs
     */
    public function process_n($option, $opt, $value, $parser)
    {
        // option is -3, -5, etc.
        $nargs = (int)substr($opt, 1);
        $rargs =& $parser->rargs;
        if (count($rargs) < $nargs) {
            $this->fail(sprintf("Expected %d arguments for %s option.", $nargs, $opt));
        }

        $parser->values->{$option->dest}[] = array_splice($rargs, 0, $nargs);
        $parser->largs[] = $nargs;
    }

    public function testCallbackMeddleArgs()
    {
        $this->assertParseOK(array("-1", "foo", "-3", "bar", "baz", "qux"),
                             array('things' => array(array('foo'), array('bar', 'baz', 'qux'))),
                             array(1, 3));
    }

    public function testCallbackMeddleArgsSeparator()
    {
        $this->assertParseOK(array("-2", "foo", "--"),
                             array('things' => array(array('foo', '--'))),
                             array(2));
    }

}

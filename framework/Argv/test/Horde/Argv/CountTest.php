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

class Horde_Argv_CountTest extends Horde_Argv_TestCase
{
    public function setUp()
    {
        $this->parser = new Horde_Argv_InterceptingParser(array('usage' => Horde_Argv_Option::SUPPRESS_USAGE));
        $this->vOpt = $this->makeOption('-v', array('action' => 'count', 'dest' => 'verbose'));
        $this->parser->addOption($this->vOpt);
        $this->parser->addOption('--verbose', array('type' => 'int', 'dest' => 'verbose'));
        $this->parser->addOption('-q', '--quiet',
                                 array('action' => 'store_const', 'dest' => 'verbose', 'const' => 0));
    }

    public function testEmpty()
    {
        $this->assertParseOk(array(), array('verbose' => null), array());
    }

    public function testCountOne()
    {
        $this->assertParseOk(array('-v'), array('verbose' => 1), array());
    }

    public function testCountThree()
    {
        $this->assertParseOk(array('-vvv'), array('verbose' => 3), array());
    }

    public function testCountThreeApart()
    {
        $this->assertParseOk(array('-v', '-v', '-v'), array('verbose' => 3), array());
    }

    public function testCountOverrideAmount()
    {
        $this->assertParseOk(array('-vvv', '--verbose=2'), array('verbose' => 2), array());
    }

    public function testCountOverrideQuiet()
    {
        $this->assertParseOk(array('-vvv', '--verbose=2', '-q'), array('verbose' => 0), array());
    }

    public function testCountOverriding()
    {
        $this->assertParseOk(array('-vvv', '--verbose=2', '-q', '-v'),
                             array('verbose' => 1), array());
    }

    public function testCountInterspersedArgs()
    {
        $this->assertParseOk(array('--quiet', '3', '-v'),
                             array('verbose' => 1),
                             array('3'));
    }

    public function testCountNoInterspersedArgs()
    {
        $this->parser->disableInterspersedArgs();
        $this->assertParseOk(array('--quiet', '3', '-v'),
                             array('verbose' => 0),
                             array('3', '-v'));
    }

    public function testCountNoSuchOption()
    {
        $this->assertParseFail(array('-q3', '-v'), 'no such option: -3');
    }

    public function testCountOptionNoValue()
    {
        $this->assertParseFail(array('--quiet=3', 'v'),
                               '--quiet option does not take a value');
    }

    public function testCountWithDefault()
    {
        $this->parser->setDefault('verbose', 0);
        $this->assertParseOk(array(), array('verbose' => 0), array());
    }

    public function testCountOverridingDefault()
    {
        $this->parser->setDefault('verbose', 0);
        $this->assertParseOk(array('-vvv', '--verbose=2', '-q', '-v'),
                             array('verbose' => 1), array());
    }
}

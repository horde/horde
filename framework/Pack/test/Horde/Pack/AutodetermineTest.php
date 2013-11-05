<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2013 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pack
 * @subpackage UnitTests
 */

/**
 * Test for the Autodetermine object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2013 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pack
 * @subpackage UnitTests
 */
class Horde_Pack_AutodetermineTest extends Horde_Test_Case
{
    private $types;

    public function setUp()
    {
        $this->types = array(
            true,
            1,
            1.234,
            'foo',
            null,
            array(),
            new stdClass
        );
    }

    public function testNegativeResults()
    {
        foreach ($this->types as $val) {
            $this->_runTest(1, false);
        }

        $this->_runTest($this->types, false);
    }

    public function testPositiveResults()
    {
        $this->_runTest($this, true);
        $this->_runTest(array_merge($this->types, array($this)), true);

        $a = new stdClass;
        $a->a = $this;
        $b = new stdClass;
        $b->b = array($a);
        $c = new stdClass;
        $c->c = array($b);
        $this->_runTest(array($c), true);
    }

    protected function _runTest($data, $expected)
    {
        $ob = new Horde_Pack_Autodetermine($data);
        if ($expected) {
            $this->assertTrue($ob->phpob);
        } else {
            $this->assertFalse($ob->phpob);
        }
    }

}

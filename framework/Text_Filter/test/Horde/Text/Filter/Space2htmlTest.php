<?php
/**
 * Horde_Text_Filter_Space2html tests.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package    Text_Filter
 * @subpackage UnitTests
 */

class Horde_Text_Filter_Space2htmlTest extends PHPUnit_Framework_TestCase
{
    public function testSpace2html()
    {
        $spaces = array(
            'x x',
            'x  x',
            'x   x',
            'x	x',
            'x		x'
        );

        $results = array(
            'x x',
            'x&nbsp; x',
            'x&nbsp; &nbsp;x',
            'x&nbsp; &nbsp; &nbsp; &nbsp; x',
            'x&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; x',
        );

        $results_encode_all = array(
            'x&nbsp;x',
            'x&nbsp;&nbsp;x',
            'x&nbsp;&nbsp;&nbsp;x',
            'x&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;x',
            'x&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;x'
        );

        foreach ($spaces as $key => $val) {
            $filter = Horde_Text_Filter::filter($val, 'space2html', array(
                'encode_all' => false
            ));
            $this->assertEquals($results[$key], $filter);

            $filter = Horde_Text_Filter::filter($val, 'space2html', array(
                'encode_all' => true
            ));
            $this->assertEquals($results_encode_all[$key], $filter);
        }
    }

}

<?php
/**
 * Horde_Text_Filter_Space2html tests.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Text_Filter
 * @subpackage UnitTests
 */

class Horde_Text_Filter_Space2htmlTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider space2htmlProvider
     */
    public function testSpace2html($spaces, $results, $results_encode_all)
    {
        $this->assertEquals(
            $results,
            Horde_Text_Filter::filter($spaces, 'space2html', array(
                'encode_all' => false
            ))
        );

        $this->assertEquals(
            $results_encode_all,
            Horde_Text_Filter::filter($spaces, 'space2html', array(
                'encode_all' => true
            ))
        );
    }

    public function space2htmlProvider()
    {
        return array(
            array(
                'x x',
                'x x',
                'x&nbsp;x'
            ),
            array(
                'x  x',
                'x&nbsp; x',
                'x&nbsp;&nbsp;x'
            ),
            array(
                'x   x',
                'x&nbsp; &nbsp;x',
                'x&nbsp;&nbsp;&nbsp;x'
            ),
            array(
                'x	x',
                'x&nbsp; &nbsp; &nbsp; &nbsp; x',
                'x&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;x'
            ),
            array(
                'x		x',
                'x&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; x',
                'x&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;x'
            )
        );
    }

}

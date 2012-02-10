<?php
/**
 * Horde_Text_Filter_Csstidy tests.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/gpl GPL
 * @package    Text_Filter_Csstidy
 * @subpackage UnitTests
 */

class Horde_Text_Filter_Csstidy_CsstidyTest extends PHPUnit_Framework_TestCase
{
    public function testMediaExpression()
    {
        $this->markTestSkipped('Skipping until csstidy supports Media Queries');

        $css = '@media all and (max-width: 670px) and (min-width: 0){}';

        $ob = new Horde_Text_Filter_Csstidy();

        $this->assertEquals(
            $css,
            $ob->postProcess($css)
        );
    }

}

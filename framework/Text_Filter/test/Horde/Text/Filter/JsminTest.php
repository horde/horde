<?php
/**
 * Javascript minification tests.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Text_Filter
 * @subpackage UnitTests
 */

class Horde_Text_Filter_JsminTest extends PHPUnit_Framework_TestCase
{
    public function testJsmin()
    {
        $javascript = <<<EOT
function foo(bar)
{
    if (bar == 2) {
        return true;
    } else {
        return false;
    }
}
EOT;

        $this->assertEquals(
            "function foo(bar)\n{if(bar==2){return true;}else{return false;}}",
             Horde_Text_Filter::filter($javascript, 'JavascriptMinify')
        );
    }

}

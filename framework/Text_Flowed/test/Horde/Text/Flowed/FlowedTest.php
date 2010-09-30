<?php
/**
 * Horde_Text_Flowed tests.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package    Text_Flowed
 * @subpackage UnitTests
 */

class Horde_Text_Flowed_FlowedTest extends PHPUnit_Framework_TestCase
{
    public function testFixedToFlowed()
    {
        $flowed = new Horde_Text_Flowed("Hello, world!");
        $this->assertEquals(
            "Hello, world!\n",
            $flowed->toFlowed()
        );

        $flowed = new Horde_Text_Flowed("Hello, \nworld!");
        $this->assertEquals(
            "Hello,\nworld!\n",
            $flowed->toFlowed()
        );

        $flowed = new Horde_Text_Flowed("Hello, \n world!");
        $this->assertEquals(
            "Hello,\n  world!\n",
            $flowed->toFlowed()
        );

        $flowed = new Horde_Text_Flowed("From");
        $this->assertEquals(
            " From\n",
            $flowed->toFlowed()
        );

        // See Bug #2969
        $flowed = new Horde_Text_Flowed("   >--------------------------------------------------------------------------------------------------------------------------------");
        $this->assertEquals(
            "    \n>-------------------------------------------------------------------------------------------------------------------------------- \n",
            $flowed->toFlowed()
        );
    }

    public function testFlowedWrap()
    {
        $text = <<<EOT
>this is a long line this is a long line this is a long line this is a long line this is a long line this is a long line
this is a long line this is a long line this is a long line this is a long line this is a long line this is a long line
EOT;
        $expected = <<<EOT
> this is a long line this is a long line this is a long line this is a 
> long line this is a long line this is a long line
this is a long line this is a long line this is a long line this is a long line this is a long line this is a long line

EOT;

        $flowed = new Horde_Text_Flowed($text);
        $flowed->setMaxLength(70);
        $this->assertEquals(
            $expected,
            $flowed->toFlowed(false, array('nowrap' => true))
        );

    }

    public function testFlowedToFixed()
    {
        $flowed = new Horde_Text_Flowed(">line 1 \n>line 2 \n>line 3");
        $this->assertEquals(
            ">line 1 line 2 line 3",
            $flowed->toFixed()
        );

        // See Bug #4832
        $flowed = new Horde_Text_Flowed("line 1\n>from line 2\nline 3");
        $this->assertEquals(
            "line 1\n>from line 2\nline 3",
            $flowed->toFixed()
        );

        $flowed = new Horde_Text_Flowed("line 1\n From line 2\nline 3");
        $this->assertEquals(
            "line 1\nFrom line 2\nline 3",
            $flowed->toFixed()
        );
    }
}

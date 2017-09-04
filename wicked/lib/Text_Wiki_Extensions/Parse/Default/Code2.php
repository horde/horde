<?php

require_once 'Text/Wiki/Parse/Default/Code.php';

/**
 * Placeholder class as a complement to the Code2 renderer.
 *
 * @package Wicked
 */
class Text_Wiki_Parse_Code2 extends Text_Wiki_Parse_Code
{
    public $regex = ';^<code(\s[^>]*)?>(.*?)\n</code>(\s|$);msi';
}

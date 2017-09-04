<?php

require_once 'Text/Wiki/Parse/Cowiki/Heading.php';

/**
 * Parsers class as a complement to the Header2 renderer.
 *
 * Works around broken parser, adding additional spaces inside headers.
 *
 * @package Wicked
 */
class Text_Wiki_Parse_Heading2 extends Text_Wiki_Parse_Heading
{
    public $regex = '/^(\++ *(.*)/m';
}

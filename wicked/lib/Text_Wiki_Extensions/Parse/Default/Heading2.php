<?php

require_once 'Text/Wiki/Parse/Default/Heading.php';

/**
 * Parsers class as a complement to the Header2 renderer.
 *
 * Works around broken Default Prefilter parser, adding additional spaces
 * inside headers.
 *
 * @package Wicked
 */
class Text_Wiki_Parse_Heading2 extends Text_Wiki_Parse_Heading
{
    public $regex = '/^(\+{1,6}) *(.*)/m';
}

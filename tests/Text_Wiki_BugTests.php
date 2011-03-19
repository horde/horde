<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once 'Text/Wiki.php';

// class to test the Text_Wiki::transform() with different wiki markups
class Text_Wiki_BugTests extends PHPUnit_Framework_TestCase
{
    /**
     * @see http://pear.php.net/bugs/bug.php?id=18289
     */
    public function test18289()
    {
        $text = <<<EOT
* level one
 * level two
* level one
 * level two
EOT;

        $wiki = new Text_Wiki();
        $html = $wiki->transform($text);

        // strip all whitespace to make assertEquals() easier
        $html = preg_replace('/\s+/','',$html);

        $assertion  = '<ul><li>level one<ul><li>level two</li></ul></li>';
        $assertion .= '<li>level one<ul><li>level two</li></ul></li></ul>';
        $this->assertEquals($assertion, $html);
    }
}

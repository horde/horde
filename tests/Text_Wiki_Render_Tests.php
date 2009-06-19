<?php

require_once 'PHPUnit/Framework.php';
require_once 'Text/Wiki.php';
require_once 'Text/Wiki/Render.php';
require_once 'Text/Wiki/Render/Xhtml.php';
require_once 'Text/Wiki/Render/Xhtml/Address.php';

class Text_Wiki_Render_Tests extends PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        $obj = Text_Wiki::singleton();
        $this->obj = new Text_Wiki_Render($obj);

        $this->conf = array('firstConf' => 'firstConfValue', 
                            'secondConf' => 'secondConfValue',
                            'thirdConf' => 'thirdConfValue',
                            'img_ext' => array('jpg', 'jpeg', 'gif', 'png'),
                            'css_table' => 'className',
                      );
    }

    public function testTextWikiRenderConstructor()
    {
        /* It is hard to test directly the constructor of the class Text_Wiki_Render as it
         * internally has logic expecting a child class name (to define the $this->rule and
         * $this->format variables). That is why we are creating an instance of
         * Text_Wiki_Render_Xhtml and Text_Wiki_Render_Xhtml_Address instead. If you have a
         * better idea feel free to improve this test
         */ 
        $wiki = Text_Wiki::singleton();
        
        $obj = new Text_Wiki_Render_Xhtml($wiki);
        $this->assertEquals($wiki, $obj->wiki, 'Should set reference to Text_Wiki object');
        $this->assertEquals('Xhtml', $obj->format);
        $this->assertNull($obj->rule);
        $this->assertEquals(array('translate' => 1, 'quotes' => 2, 'charset' => 'ISO-8859-1'), $obj->conf);

        $obj = new Text_Wiki_Render_Xhtml_Address($wiki);
        $this->assertEquals($wiki, $obj->wiki, 'Should set reference to Text_Wiki object');
        $this->assertEquals('Xhtml', $obj->format);
        $this->assertEquals('Address', $obj->rule);
        $this->assertEquals(array('css' => null), $obj->conf);
    }
    
    public function testGetConfShouldReturnConfValue()
    {
        $this->obj->conf = $this->conf;
        
        foreach ($this->conf as $key => $value) {
            $this->assertEquals($value, $this->obj->getConf($key));
            $this->assertEquals($value, $this->obj->getConf($key, 'DefaultValue'));
        }
    }

    public function testGetConfShouldReturnDefaultValue()
    {
        $this->obj->conf = $this->conf;
        $this->assertEquals('DefaultValue', $this->obj->getConf('InvalidKey', 'DefaultValue'));
    }
    
    public function testFormatConfShouldReturnSprinfFormatedValue()
    {
        $this->obj->conf = $this->conf;
        foreach ($this->conf as $key => $value) {
            $this->assertEquals(" class=\"$value\"", $this->obj->formatConf(' class="%s"', $key));
        }
    }

    public function testFormatConfShouldReturnNull()
    {
        $this->obj->conf = $this->conf;
        $this->assertNull($this->obj->formatConf(' class="%s"', 'InvalidKey'));
        $this->assertNull($this->obj->formatConf(' class="%s"', null));        
    }

    public function testUrlEncode()
    {
        $texts = array('ftp://user:foo @+%/@ftp.example.com/x.txt' => 'ftp%3A%2F%2Fuser%3Afoo%20%40%2B%25%2F%40ftp.example.com%2Fx.txt',
                       'http://example.com/department_list_script/sales and marketing/Miami' => 'http%3A%2F%2Fexample.com%2Fdepartment_list_script%2Fsales%20and%20marketing%2FMiami'
                 );
        foreach ($texts as $inputString => $outputString) {
            $this->assertEquals($outputString, $this->obj->urlEncode($inputString));
        }
    }

    public function testTextEncode()
    {
        // need more strings to test
        $text = "<a href='test'>Test</a>";
        $this->assertEquals("&lt;a href='test'&gt;Test&lt;/a&gt;", $this->obj->textEncode($text));
    }
    
}

?>
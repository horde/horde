<?php
/**
 * @author     Michael Slusarz <slusarz@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Util
 * @subpackage UnitTests
 */
class Horde_Util_DomhtmlTest extends PHPUnit_Framework_TestCase
{
    public function testBug9567()
    {
        $text = <<<EOT
<html>
 <head>
  <meta http-equiv=3DContent-Type content=3D"text/html; charset=3Diso-8859-1">
 </head>
 <body>
  pr=E9parer =E0 vendre d&#8217;ao=FBt&nbsp;;
 </body>
</html>
EOT;

        $expected = "préparer à vendre d’août ;";

        $dom = new Horde_Domhtml(quoted_printable_decode($text), 'iso-8859-1');

        $this->assertEquals(
            Horde_String::convertCharset($expected, 'UTF-8', 'iso-8859-1'),
            trim($dom->returnBody())
        );

        $this->assertEquals(
            'iso-8859-1',
            $dom->getCharset()
        );

        /* Test auto-detect. */
        $dom = new Horde_Domhtml(quoted_printable_decode($text));

        $this->assertEquals(
            Horde_String::convertCharset($expected, 'UTF-8', 'iso-8859-1'),
            trim($dom->returnBody())
        );

        $this->assertEquals(
            'iso-8859-1',
            $dom->getCharset()
        );
    }

    public function testBug9714()
    {
        $text = "<html><body>J'ai r=E9ussi J ai r=E9ussi</body></html>";
        $expected = "J'ai réussi J ai réussi";

        $dom = new Horde_Domhtml(quoted_printable_decode($text), 'iso-8859-15');
        $this->assertEquals(
            Horde_String::convertCharset($expected, 'UTF-8', 'iso-8859-15'),
            trim($dom->returnBody())
        );

        /* iso-8859-15 is not recognized, so UTF-8 is used internally. */
        $this->assertEquals(
            'UTF-8',
            $dom->getCharset()
        );

        /* Test auto-detect. */
        $dom = new Horde_Domhtml(quoted_printable_decode($text));

        $this->assertEquals(
            Horde_String::convertCharset($expected, 'UTF-8', 'iso-8859-15'),
            trim($dom->returnBody())
        );

        /* iso-8859-1 is used for auto-detection. */
        $this->assertEquals(
            'iso-8859-1',
            $dom->getCharset()
        );
    }

    public function testBug9992()
    {
        $text = base64_decode('dGVzdDogtbno6bvtu+nt/eHpu7797Txicj4K');
        $expected = '<p>test: ľščéťíťéíýáéťžýí<br/></p>';

        $dom = new Horde_Domhtml($text, 'iso-8859-2');
        $this->assertEquals(
            Horde_String::convertCharset($expected, 'UTF-8', 'iso-8859-2'),
            trim($dom->returnBody())
        );

        $this->assertEquals(
            'UTF-8',
            $dom->getCharset()
        );
    }

    public function testIterator()
    {
        $text = file_get_contents(__DIR__ . '/fixtures/domhtml_test.html');
        $dom = new Horde_Domhtml($text);

        $tags = array(
            'html',
            'body',
            'div',
            'head',
            'title'
        );

        foreach ($dom as $node) {
            if ($node instanceof DOMElement) {
                if ($node->tagName != reset($tags)) {
                    $this->fail('Wrong tag name.');
                }
                array_shift($tags);
            }
        }
    }

    public function testHrefSpaces()
    {
        $text = <<<EOT
<html>
 <body>
  <a href="  http://foo.example.com/">Foo</a>
 </body>
</html>
EOT;

        $dom = new Horde_Domhtml($text, 'UTF-8');

        foreach ($dom as $val) {
            if (($val instanceof DOMElement) &&
                ($val->tagName == 'a')) {
                $this->assertEquals(
                    '  http://foo.example.com/',
                    $val->getAttribute('href')
                );
            }
        }

        $this->assertEquals(
            'UTF-8',
            $dom->getCharset()
        );
    }

    public function testHeadGeneration()
    {
        $dom = new Horde_Domhtml('<div>foo</div>');
        $head = $dom->getHead();

        $this->assertNull($head->previousSibling);

        $this->assertEquals(
            'iso-8859-1',
            $dom->getCharset()
        );
    }

}

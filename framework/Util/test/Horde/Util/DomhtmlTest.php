<?php
/**
 * Require our basic test case definition
 */
require_once dirname(__FILE__) . '/Autoload.php';

/**
 * @author     Michael Slusarz <slusarz@horde.org>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
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

        /* Test auto-detect. */
        $dom = new Horde_Domhtml(quoted_printable_decode($text));

        $this->assertEquals(
            Horde_String::convertCharset($expected, 'UTF-8', 'iso-8859-1'),
            trim($dom->returnBody())
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

        /* Test auto-detect. */
        $dom = new Horde_Domhtml(quoted_printable_decode($text));

        $this->assertEquals(
            Horde_String::convertCharset($expected, 'UTF-8', 'iso-8859-15'),
            trim($dom->returnBody())
        );
    }

}

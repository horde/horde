<?php
/**
 * Horde_Text_Filter_Msoffice tests.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Text_Filter
 * @subpackage UnitTests
 */
class Horde_Text_Filter_Msoffice_Test extends PHPUnit_Framework_TestCase
{
    public function testMsoNormalCss()
    {
        $html = <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<html><body>
<p class="MsoNormal"><span style='font-size:11.0pt;font-family:"Calibri","sans-serif";color:#1F497D'>Danke f&uuml;r die rasche Erledigung!</span></p>
<p class="MsoNormal"><span style='font-size:11.0pt;font-family:"Calibri","sans-serif";color:#1F497D'>&nbsp;</span></p>
<p class="MsoNormal foo"><span style='font-size:11.0pt;font-family:"Calibri","sans-serif";color:#1F497D'>W&uuml;nsche ein sch&ouml;nes Weihnachtsfest und f&uuml;r 2015 alles Gute!</span></p>
<p class="foo MsoNormal"><span style='font-size:11.0pt;font-family:"Calibri","sans-serif";color:#1F497D'>&nbsp;</span></p>
</body></html>
HTML;

        $expected = <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<html><body>
<span style='font-size:11.0pt;font-family:"Calibri","sans-serif";color:#1F497D'>Danke f&uuml;r die rasche Erledigung!</span><br>

<div class="foo"><span style='font-size:11.0pt;font-family:"Calibri","sans-serif";color:#1F497D'>W&uuml;nsche ein sch&ouml;nes Weihnachtsfest und f&uuml;r 2015 alles Gute!</span></div>
<div class="foo"><span style='font-size:11.0pt;font-family:"Calibri","sans-serif";color:#1F497D'>&nbsp;</span></div>
</body></html>

HTML;

        $filtered = Horde_Text_Filter::filter($html, 'Msoffice');
        $this->assertEquals($expected, $filtered);
    }

    public function testOfficeNamespace()
    {
        $html = <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<html xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns:m="http://schemas.microsoft.com/office/2004/12/omml" xmlns="http://www.w3.org/TR/REC-html40">
<body>
<p><span>Danke f&uuml;r die rasche Erledigung!<o:p>&nbsp;</o:p></span></p>
<p><span><o:p>&nbsp;</o:p></span></p>
</body></html>
HTML;

        $expected = <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<html xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns:m="http://schemas.microsoft.com/office/2004/12/omml" xmlns="http://www.w3.org/TR/REC-html40">
<body>
<p><span>Danke f&uuml;r die rasche Erledigung!</span></p>
<p><span></span></p>
</body></html>

HTML;

        $filtered = Horde_Text_Filter::filter($html, 'Msoffice');
        $this->assertEquals($expected, $filtered);
    }

    public function testCombination()
    {
        $html = <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<html><body>
<p class="MsoNormal"><span style='font-size:11.0pt;font-family:"Calibri","sans-serif";color:#1F497D'>Danke f&uuml;r die rasche Erledigung!</span></p>
<p class="MsoNormal"><span style='font-size:11.0pt;font-family:"Calibri","sans-serif";color:#1F497D'><o:p>&nbsp;</o:p></span></p>
<p class="MsoNormal foo"><span style='font-size:11.0pt;font-family:"Calibri","sans-serif";color:#1F497D'>W&uuml;nsche ein sch&ouml;nes Weihnachtsfest und f&uuml;r 2015 alles Gute!<o:p>&nbsp;</o:p></span></p>
<p class="foo MsoNormal"><span style='font-size:11.0pt;font-family:"Calibri","sans-serif";color:#1F497D'>&nbsp;</span></p>
</body></html>
HTML;

        $expected = <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<html><body>
<span style='font-size:11.0pt;font-family:"Calibri","sans-serif";color:#1F497D'>Danke f&uuml;r die rasche Erledigung!</span><br>

<div class="foo"><span style='font-size:11.0pt;font-family:"Calibri","sans-serif";color:#1F497D'>W&uuml;nsche ein sch&ouml;nes Weihnachtsfest und f&uuml;r 2015 alles Gute!</span></div>
<div class="foo"><span style='font-size:11.0pt;font-family:"Calibri","sans-serif";color:#1F497D'>&nbsp;</span></div>
</body></html>

HTML;

        $filtered = Horde_Text_Filter::filter($html, 'Msoffice');
        $this->assertEquals($expected, $filtered);
    }
}

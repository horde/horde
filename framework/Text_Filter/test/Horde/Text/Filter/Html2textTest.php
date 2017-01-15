<?php
/**
 * Horde_Text_Filter_Html2text tests.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Text_Filter
 * @subpackage UnitTests
 */

class Horde_Text_Filter_Html2textTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider html2textProvider
     */
    public function testHtml2text($input, $expected)
    {
        $filter = Horde_Text_Filter::filter($input, 'Html2text', array(
            'width' => 70
        ));
        $this->assertEquals($expected, $filter);
    }

    public function html2textProvider()
    {
        $data = array(
            array(
                '<h1>Level 1 Header</h1>',
                'LEVEL 1 HEADER'
            ),
            array(
                '<h2>Level 2 Header</h2>',
                'LEVEL 2 HEADER'
            ),
            array(
                '<h3>Level 3 Header</h3>',
                'LEVEL 3 HEADER'
            ),
            array(
                '<h4>Level 4 Header</h4>',
                'Level 4 Header'
            ),
            array(
                '<h5>Level 5 Header</h5>',
                'Level 5 Header'
            ),
            array(
                '<h6>Level 6 Header</h6>',
                'Level 6 Header'
            ),
            array(
                '    Some text with leading and trailing whitespace  ',
                'Some text with leading and trailing whitespace'
            ),
            array(
                "A<br />B",
                "A\nB"
            ),
            array(
                "A\n<br />B\n",
                "A\nB"
            ),
            array(
                "\n\n<br />\n\n",
                ""
            ),
            array(
                '<hr />',
                '-------------------------'
            ),
            array(
                "<p>You can make various levels of heading by putting equals-signs before and\nafter the text (all on its own line):</p>",
                "You can make various levels of heading by putting equals-signs before\nand after the text (all on its own line):"
            ),
            array(
                '<ul><li>Bullet one<ul><li>Sub-bullet</li></ul></li></ul>',
                "  * Bullet one\n\n    * Sub-bullet"
            ),
            array(
                '<ol><li>Numero uno</li><li>Number two<ol><li>Sub-item</li></ol></li></ol>',
                "  * Numero uno\n  * Number two\n\n    * Sub-item"
            ),
            array(
                '&auml; &eacute; &copy; &trade; &#x0110;',
                'ä é © ™ Đ'
            )
        );

        // Table
        $html = <<<EOT
<table class="table">
    <tr>
        <th>Type</th>
        <th>Representation</th>
    </tr>
    <tr>
        <td class="table-cell">emphasis text</td>
        <td class="table-cell"><em>emphasis text</em></td>
    </tr>
    <tr>
        <td class="table-cell">strong text</td>
        <td class="table-cell"><strong>strong text</strong></td>
    </tr>
    <tr>
        <td class="table-cell">italic text</td>
        <td class="table-cell"><i>italic text</i></td>
    </tr>
    <tr>
        <td class="table-cell">bold text</td>
        <td class="table-cell"><b>bold text</b></td>
    </tr>
    <tr>
        <td class="table-cell">emphasis and strong</td>
        <td class="table-cell"><em><strong>emphasis and strong</strong></em></td>
    </tr>
    <tr>
        <td class="table-cell">underline text</td>
        <td class="table-cell"><u>underline text</u></td>
    </tr>
</table>
EOT;
        $expected = <<<EOT
  TYPE 	REPRESENTATION
  emphasis text 	/emphasis text/
  strong text 	STRONG TEXT
  italic text 	/italic text/
  bold text 	BOLD TEXT
  emphasis and strong 	/EMPHASIS AND STRONG/
  underline text 	_underline text_
EOT;
        $data[] = array($html, $expected);

        // Links
        $html = <<<EOT
<a href="http://www.horde.org">Horde Homepage</a><br />
<a href="mailto:test@example.com">Test User</a><br />
Some inline <a href="http://www.horde.org">link</a>.<br />
<a href="http://www.example.com">http://www.example.com</a><br />
EOT;
        $expected = <<<EOT
Horde Homepage[1]
Test User[2]
Some inline link[1].
http://www.example.com


Links:
------
[1] http://www.horde.org
[2] mailto:test@example.com
EOT;
        $data[] = array($html, $expected);

        // Mixed lists
        $html = <<<EOT
<ol>
    <li>Number one<ul>
        <li>Bullet</li>
        <li>Bullet</li>
    </ul></li>
    <li>Number two<ul>
        <li>Bullet</li>
        <li>Bullet<ul>
            <li>Sub-bullet<ol>
                <li>Sub-sub-number</li>
                <li>Sub-sub-number</li>
            </ol></li>
        </ul></li>
    </ul></li>
    <li>Number three<ul>
        <li>Bullet</li>
        <li>Bullet</li>
    </ul></li>
</ol>
EOT;
        $expected = <<<EOT
  * Number one

    * Bullet
    * Bullet

  * Number two

    * Bullet
    * Bullet

      * Sub-bullet

        * Sub-sub-number
        * Sub-sub-number

  * Number three

    * Bullet
    * Bullet
EOT;
        $data[] = array($html, $expected);

        // Blockquote
        $html = <<<EOT
<blockquote type="cite">
<a href="http://www.horde.org">Horde Homepage</a><br />
Some inline <a href="http://www.horde.org">link</a>.<br />
</blockquote>
EOT;
        $expected = <<<EOT
> Horde Homepage[1]
> Some inline link[1].



Links:
------
[1] http://www.horde.org
EOT;
        $data[] = array($html, $expected);

        $html = <<<EOT
<blockquote type="cite">
<h2>Heading inside quoting</h2>
<p>This is a paragraph inside a block quoting. The result should be several
lines prefixed with the &gt; character.</p>
</blockquote>
EOT;
        $expected = <<<EOT
> HEADING INSIDE QUOTING
>
> This is a paragraph inside a block quoting. The result should be
> several lines prefixed with the > character.
EOT;
        $data[] = array($html, $expected);

        // Complex examples
        $html = <<<EOT
<p>Zitat von John Doe &lt;john.doe@example.com&gt;:</p>
  <blockquote type="cite"> 
    <div class="Section1"> 
      <p class="MsoNormal"><font size="2" face="Arial"><span style="font-size: 10pt; font-family: Arial;">Hallo lieber John,<o:p /></span></font></p> 
      <p class="MsoNormal"><font size="2" face="Arial"><span style="font-size: 10pt; font-family: Arial;"><o:p> </o:p></span></font></p> 
      <p class="MsoNormal"><font size="2" face="Arial"><span style="font-size: 10pt; font-family: Arial;">Blah, blah.'<o:p /></span></font></p> 
      <p class="MsoNormal"><font size="2" face="Arial"><span style="font-size: 10pt; font-family: Arial;"><o:p> </o:p></span></font></p> 
      <p class="MsoNormal"><font size="3" face="Times New Roman"><span lang="EN-GB" style="font-size: 12pt;"><o:p> </o:p></span></font></p> 
    </div> 
  </blockquote> 
  <p> </p>
  <p class="imp-signature"><!--begin_signature-->-- <br />
Some signature<br /><a target="_blank" href="http://www.example.com">http://www.example.com</a><!--end_signature--></p>
EOT;
        $expected = <<<EOT
Zitat von John Doe <john.doe@example.com>:

> Hallo lieber John,
>
> Blah, blah.'

--
Some signature
http://www.example.com
EOT;
        $data[] = array($html, $expected);

        $html = <<<EOT
<p>Zitat von Jane Doe &lt;jane.doe@example.com&gt;:</p>
  <blockquote type="cite">
Jan Schneider a écrit&nbsp;:<br/>

    <blockquote type="cite" cite="mid:20081007135151.190315kzjzymtbhc@neo.wg.de">Zitat von Jane Doe
<a href="mailto:jane.doe@example.com" class="moz-txt-link-rfc2396E">&lt;jane.doe@example.com&gt;</a>:
  <br /> <br /> 
      <blockquote type="cite">Hi,
    <br /> <br />
I prepare the last &quot;horde-webmail-1.2&quot; for production level but I have
few questions:
    <br />
- is there a way to disable &quot;external_display_cal&quot; in kronolith, I
don't want seeing birthdays calendars (turba) and task list (nag)
    <br /> </blockquote> <br />
They aren't displayed by default, or do you mean you don't want them to
appear in the top right calendar panel?
  <br /> 
    </blockquote>
Yes I don't want them to appear in the top right calendar panel but I
want user can create their external_cal<br />
  </blockquote><br />
  <p class="imp-signature"><!--begin_signature-->Jan.<br /> <br />
-- <br />
Do you need professional PHP or Horde consulting?<br /> <a target="_blank" href="http://horde.org/consulting/">http://horde.org/consulting/</a><!--end_signature--></p>
EOT;
        $expected = <<<EOT
Zitat von Jane Doe <jane.doe@example.com>:

> Jan Schneider a écrit :
>
>> Zitat von Jane Doe <jane.doe@example.com>[1]:
>>
>>> Hi,
>>>
>>> I prepare the last "horde-webmail-1.2" for production level but I
>>> have few questions:
>>> - is there a way to disable "external_display_cal" in kronolith,
>>> I don't want seeing birthdays calendars (turba) and task list
>>> (nag)
>>
>> They aren't displayed by default, or do you mean you don't want
>> them to appear in the top right calendar panel?
>
> Yes I don't want them to appear in the top right calendar panel but
> I want user can create their external_cal

Jan.

--
Do you need professional PHP or Horde consulting?
http://horde.org/consulting/


Links:
------
[1] mailto:jane.doe@example.com
EOT;
        $data[] = array($html, $expected);

        $html = <<<EOT
<p>Zitat von Roberto Maurizzi &lt;foo@example.com&gt;:</p>
  <blockquote type="cite">
    <div class="gmail_quote">
      <blockquote style="border-left: 1px solid #cccccc; margin: 0pt 0pt 0pt 0.8ex; padding-left: 1ex;" class="gmail_quote">
        <blockquote style="border-left: 1px solid #cccccc; margin: 0pt 0pt 0pt 0.8ex; padding-left: 1ex;" class="gmail_quote"> 
          <div class="Ih2E3d">
            <blockquote style="border-left: 1px solid #cccccc; margin: 0pt 0pt 0pt 0.8ex; padding-left: 1ex;" class="gmail_quote">4) In Turba, I can select a VFS driver to use. Currently it is set to<br />

None and turba seems to be working fine. What does Turba use the VFS<br />
for?<br /> </blockquote> 
          </div>
        </blockquote><br />
You can attach files to contacts with that.<br /> <br />
Jan.<br /><font color="#888888"> </font>
      </blockquote>
      <div><br /></div>
    </div>Anything similar for Kronolith, maybe in the new version?<br />I've googled a little and only found a discussion in 2004 about having attachment (or links) from VFS in Kronolith.<br />
I'd really like to be able to attach all my taxes forms to the day I have to pay them ;-) and more in general all the extra documentation regarding an appointment.<br /><br />Ciao,<br />&nbsp; Roberto<br /><br /> 
  </blockquote> 
  <p>Some unquoted line with single ' quotes.</p>
  <p class="imp-signature"><!--begin_signature-->Jan.<br /> <br />
-- <br />
Do you need professional PHP or Horde consulting?<br /> <a target="_blank" href="http://horde.org/consulting/">http://horde.org/consulting/</a><!--end_signature--></p>
EOT;

        $expected = <<<EOT
Zitat von Roberto Maurizzi <foo@example.com>:

>>>> 4) In Turba, I can select a VFS driver to use. Currently it is
>>>> set to
>>>> None and turba seems to be working fine. What does Turba use the
>>>> VFS
>>>> for?
>>
>> You can attach files to contacts with that.
>>
>> Jan.
>
> Anything similar for Kronolith, maybe in the new version?
> I've googled a little and only found a discussion in 2004 about
> having attachment (or links) from VFS in Kronolith.
> I'd really like to be able to attach all my taxes forms to the day
> I have to pay them ;-) and more in general all the extra
> documentation regarding an appointment.
>
> Ciao,
>   Roberto

Some unquoted line with single ' quotes.

Jan.

--
Do you need professional PHP or Horde consulting?
http://horde.org/consulting/
EOT;
        $data[] = array($html, $expected);

        return $data;
    }

    public function testHtml2textLinks()
    {
        $html = <<<EOT
<ul>
  <li>This is a short line.</li>
  <li>This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line.</li>
  <li>And again a short line.</li>
</ul>
EOT;

        $text_wrap = <<<EOT
  * This is a short line.
  * This is a long line. This is a long line. This
is a long line. This is a long line. This is a
long line. This is a long line. This is a long
line. This is a long line. This is a long line.
This is a long line. This is a long line. This is
a long line.
  * And again a short line.
EOT;

        $text_nowrap = <<<EOT
  * This is a short line.
  * This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line. This is a long line.
  * And again a short line.
EOT;

        $filter = Horde_Text_Filter::filter($html, 'Html2text', array(
            'width' => 50
        ));
        $this->assertEquals($text_wrap, $filter);

        $filter = Horde_Text_Filter::filter($html, 'Html2text', array(
            'width' => 0
        ));
        $this->assertEquals($text_nowrap, $filter);
    }

    public function testHtml2TextSpacing()
    {
        $html = '<span><span>Normal</span> <strong>Strong</strong> Normal</span> <em>Italics</em> Normal <u>Underline</u> Normal <strike>Strike</strike> Normal';
        $filter = Horde_Text_Filter::filter($html, 'Html2text');

        $this->assertEquals(
            'Normal STRONG Normal /Italics/ Normal _Underline_ Normal Strike Normal',
            $filter
        );
    }

    public function testHtml2TextNestingLimit()
    {
        $html = '<span>Foo</span><div><div><div><div>Bar</div></div></div></div>';
        $filter = Horde_Text_Filter::filter($html, 'Html2text', array('nestingLimit' => 3));
        $this->assertEquals('Foo', $filter);

        $filter = Horde_Text_Filter::filter($html, 'Html2text', array('nestingLimit' => 4));
        $this->assertEquals('FooBar', $filter);

    }

}

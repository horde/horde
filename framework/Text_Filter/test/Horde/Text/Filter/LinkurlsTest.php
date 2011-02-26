<?php
/**
 * Horde_Text_Filter_Linkurls tests.
 *
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package    Text_Filter
 * @subpackage UnitTests
 */
class Horde_Text_Filter_LinkurlsTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider urlProvider
     */
    public function testLinkurls($testText, $expected)
    {
        // need to update regexp per http://daringfireball.net/2010/07/improved_regex_for_matching_urls to fully pass
        $actual = Horde_Text_Filter::filter($testText, 'linkurls', array('target' => null));
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test data from
     * http://daringfireball.net/misc/2010/07/url-matching-regex-test-data.text
     */
    public function urlProvider()
    {
        return array(
            /* No match */
            array('6:00p', '6:00p'),
            array('filename.txt', 'filename.txt'),

            /* Not matchng mailto: intentionally */
            array('What about <mailto:gruber@daringfireball.net?subject=TEST> (including brokets).', 'What about <mailto:gruber@daringfireball.net?subject=TEST> (including brokets).'),
            array('mailto:name@example.com', 'mailto:name@example.com'),

            /* Matched correctly */
            array('http://foo.com/blah_blah', '<a href="http://foo.com/blah_blah">http://foo.com/blah_blah</a>'),
            array('http://foo.com/blah_blah/', '<a href="http://foo.com/blah_blah/">http://foo.com/blah_blah/</a>'),
            array('(Something like http://foo.com/blah_blah)', '(Something like <a href="http://foo.com/blah_blah">http://foo.com/blah_blah</a>)'),
            array('http://foo.com/blah_blah_(wikipedia)', '<a href="http://foo.com/blah_blah_(wikipedia)">http://foo.com/blah_blah_(wikipedia)</a>'),
            array('http://foo.com/more_(than)_one_(parens)', '<a href="http://foo.com/more_(than)_one_(parens)">http://foo.com/more_(than)_one_(parens)</a>'),
            array('(Something like http://foo.com/blah_blah_(wikipedia))', '(Something like <a href="http://foo.com/blah_blah_(wikipedia)">http://foo.com/blah_blah_(wikipedia)</a>)'),
            array('http://foo.com/blah_(wikipedia)#cite-1', '<a href="http://foo.com/blah_(wikipedia)#cite-1">http://foo.com/blah_(wikipedia)#cite-1</a>'),
            array('http://foo.com/blah_(wikipedia)_blah#cite-1', '<a href="http://foo.com/blah_(wikipedia)_blah#cite-1">http://foo.com/blah_(wikipedia)_blah#cite-1</a>'),
            array('http://foo.com/unicode_(✪)_in_parens', '<a href="http://foo.com/unicode_(✪)_in_parens">http://foo.com/unicode_(✪)_in_parens</a>'),
            array('http://foo.com/(something)?after=parens', '<a href="http://foo.com/(something)?after=parens">http://foo.com/(something)?after=parens</a>'),
            array('http://foo.com/blah_blah.', '<a href="http://foo.com/blah_blah">http://foo.com/blah_blah</a>.'),
            array('http://foo.com/blah_blah/.', '<a href="http://foo.com/blah_blah/">http://foo.com/blah_blah/</a>.'),
            array('<http://foo.com/blah_blah>', '<<a href="http://foo.com/blah_blah">http://foo.com/blah_blah</a>>'),
            array('<http://foo.com/blah_blah/>', '<<a href="http://foo.com/blah_blah/">http://foo.com/blah_blah/</a>>'),
            array('http://foo.com/blah_blah,', '<a href="http://foo.com/blah_blah">http://foo.com/blah_blah</a>,'),
            array('http://www.extinguishedscholar.com/wpglob/?p=364.', '<a href="http://www.extinguishedscholar.com/wpglob/?p=364">http://www.extinguishedscholar.com/wpglob/?p=364</a>.'),
            array('http://✪df.ws/1234', '<a href="http://✪df.ws/1234">http://✪df.ws/1234</a>'),
            array('rdar://1234', '<a href="rdar://1234">rdar://1234</a>'),
            array('rdar:/1234', '<a href="rdar:/1234">rdar:/1234</a>'),
            array('x-yojimbo-item://6303E4C1-6A6E-45A6-AB9D-3A908F59AE0E', '<a href="x-yojimbo-item://6303E4C1-6A6E-45A6-AB9D-3A908F59AE0E">x-yojimbo-item://6303E4C1-6A6E-45A6-AB9D-3A908F59AE0E</a>'),
            array('message://%3c330e7f840905021726r6a4ba78dkf1fd71420c1bf6ff@mail.gmail.com%3e', '<a href="message://%3c330e7f840905021726r6a4ba78dkf1fd71420c1bf6ff@mail.gmail.com%3e">message://%3c330e7f840905021726r6a4ba78dkf1fd71420c1bf6ff@mail.gmail.com%3e</a>'),
            array('http://➡.ws/䨹', '<a href="http://➡.ws/䨹">http://➡.ws/䨹</a>'),
            array('www.c.ws/䨹', '<a href="http://www.c.ws/䨹">www.c.ws/䨹</a>'),
            array('<tag>http://example.com</tag>', '<tag><a href="http://example.com">http://example.com</a></tag>'),
            array('Just a www.example.com link.', 'Just a <a href="http://www.example.com">www.example.com</a> link.'),
            array('http://example.com/something?with,commas,in,url, but not at end', '<a href="http://example.com/something?with,commas,in,url">http://example.com/something?with,commas,in,url</a>, but not at end'),
            array('bit.ly/foo', '<a href="http://bit.ly/foo">bit.ly/foo</a>'),
            array('“is.gd/foo/”', '“<a href="http://is.gd/foo/">is.gd/foo/</a>”'),
            array('WWW.EXAMPLE.COM', '<a href="http://WWW.EXAMPLE.COM">WWW.EXAMPLE.COM</a>'),
            array('http://www.asianewsphoto.com/(S(neugxif4twuizg551ywh3f55))/Web_ENG/View_DetailPhoto.aspx?PicId=752', '<a href="http://www.asianewsphoto.com/(S(neugxif4twuizg551ywh3f55))/Web_ENG/View_DetailPhoto.aspx?PicId=752">http://www.asianewsphoto.com/(S(neugxif4twuizg551ywh3f55))/Web_ENG/View_DetailPhoto.aspx?PicId=752</a>'),
            array('http://www.asianewsphoto.com/(S(neugxif4twuizg551ywh3f55))', '<a href="http://www.asianewsphoto.com/(S(neugxif4twuizg551ywh3f55))">http://www.asianewsphoto.com/(S(neugxif4twuizg551ywh3f55))</a>'),
            array('http://lcweb2.loc.gov/cgi-bin/query/h?pp/horyd:@field(NUMBER+@band(thc+5a46634))', '<a href="http://lcweb2.loc.gov/cgi-bin/query/h?pp/horyd:@field(NUMBER+@band(thc+5a46634))">http://lcweb2.loc.gov/cgi-bin/query/h?pp/horyd:@field(NUMBER+@band(thc+5a46634))</a>'),

            /* Known failures */
            // array('http://example.com/quotes-are-“part”', 'http://example.com/quotes-are-“part”'),
            // array('✪df.ws/1234', '✪df.ws/1234'),
            array('example.com', 'example.com'),
            array('example.com/', 'example.com/'),
        );
    }
}

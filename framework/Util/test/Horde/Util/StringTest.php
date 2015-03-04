<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Util
 * @subpackage UnitTests
 */
class Horde_Util_StringTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        setlocale(LC_ALL, '');
    }

    public function testUpper()
    {
        $this->assertEquals(
            'ABCDEFGHII',
            Horde_String::upper('abCDefGHiI', true, 'us-ascii')
        );
        $this->assertEquals(
            'ABCDEFGHII',
            Horde_String::upper('abCDefGHiI', true, 'Big5')
        );
        $this->assertEquals(
            'ABCDEFGHİI',
            Horde_String::convertCharset(
                Horde_String::upper('abCDefGHiI', true, 'iso-8859-9'),
                'iso-8859-9', 'utf-8')
        );
    }

    public function testUpperTurkish()
    {
        if (!setlocale(LC_ALL, 'tr_TR')) {
            $this->markTestSkipped('No Turkish locale installed.');
        }
        $one = Horde_String::convertCharset(strtoupper('abCDefGHiI'),
                                            'iso-8859-9', 'utf-8');
        $two = Horde_String::upper('abCDefGHiI');
        setlocale(LC_ALL, 'C');
        $this->assertEquals('ABCDEFGHİI', $one);
        $this->assertEquals('ABCDEFGHII', $two);
    }

    public function testLower()
    {
        $this->assertEquals(
            'abcdefghii',
            Horde_String::lower('abCDefGHiI', true, 'us-ascii')
        );
        $this->assertEquals(
            'abcdefghii',
            Horde_String::lower('abCDefGHiI', true, 'Big5')
        );
        $this->assertEquals(
            'abcdefghiı',
            Horde_String::convertCharset(
                Horde_String::lower('abCDefGHiI', true, 'iso-8859-9'),
                'iso-8859-9', 'utf-8')
        );
    }

    public function testLowerTurkish()
    {
        if (!setlocale(LC_ALL, 'tr_TR')) {
            $this->markTestSkipped('No Turkish locale installed.');
        }
        $one = Horde_String::convertCharset(strtolower('abCDefGHiI'),
                                            'iso-8859-9', 'utf-8');
        $two = Horde_String::lower('abCDefGHiI');
        setlocale(LC_ALL, 'C');
        $this->assertEquals('abcdefghiı', $one);
        $this->assertEquals('abcdefghii', $two);
    }

    public function testUcfirst()
    {
        $this->assertEquals(
            'Integer',
            Horde_String::ucfirst('integer', true, 'us-ascii')
        );
        $this->assertEquals(
            'Integer',
            Horde_String::ucfirst('integer', true, 'Big5')
        );
        $this->assertEquals(
            'İnteger',
            Horde_String::convertCharset(
                Horde_String::ucfirst('integer', true, 'iso-8859-9'),
                'iso-8859-9', 'utf-8')
        );
    }

    public function testUcwords()
    {
        $this->assertEquals(
            'Integer  Inside',
            Horde_String::ucwords('integer  inside', true, 'us-ascii')
        );
        $this->assertEquals(
            'Integer  Inside',
            Horde_String::ucwords('integer  inside', true, 'Big5')
        );
        $this->assertEquals(
            'İnteger  İnside',
            Horde_String::convertCharset(
                Horde_String::ucwords('integer  inside', true, 'iso-8859-9'),
                'iso-8859-9', 'utf-8')
        );
    }

    public function testUcfirstTurkish()
    {
        if (!setlocale(LC_ALL, 'tr_TR')) {
            $this->markTestSkipped('No Turkish locale installed.');
        }
        $one = Horde_String::convertCharset(ucfirst('integer'),
                                            'iso-8859-9', 'utf-8');
        $two = Horde_String::ucfirst('integer');
        setlocale(LC_ALL, 'C');
        $this->assertEquals('İnteger', $one);
        $this->assertEquals('Integer', $two);
    }

    public function testLength()
    {
        $this->assertEquals(7, Horde_String::length('Welcome', 'Big5'));
        $this->assertEquals(7, Horde_String::length('Welcome', 'Big5'));
        $this->assertEquals(
            2,
            Horde_String::length(
                Horde_String::convertCharset('歡迎', 'utf-8', 'Big5'),
                'Big5'));
        $this->assertEquals(2, Horde_String::length('歡迎', 'utf-8'));

        /* The following strings were taken with permission from the UTF-8
         * sampler by Frank da Cruz <fdc@columbia.edu> and the Kermit Project
         * (http://www.columbia.edu/kermit/).  The original page is located at
         * http://www.columbia.edu/kermit/utf8.html */

        // French 50
        $this->assertEquals(
            50,
            Horde_String::length('Je peux manger du verre, ça ne me fait pas de mal.', 'utf-8'));

        // Spanish 36
        $this->assertEquals(
            36,
            Horde_String::length('Puedo comer vidrio, no me hace daño.', 'utf-8'));

        // Portuguese 34
        $this->assertEquals(
            34,
            Horde_String::length('Posso comer vidro, não me faz mal.', 'utf-8'));

        // Brazilian Portuguese 34
        $this->assertEquals(
            34,
            Horde_String::length('Posso comer vidro, não me machuca.', 'utf-8'));

        // Italian 41
        $this->assertEquals(
            41,
            Horde_String::length('Posso mangiare il vetro e non mi fa male.', 'utf-8'));

        // English 39
        $this->assertEquals(
            39,
            Horde_String::length('I can eat glass and it doesn\'t hurt me.', 'utf-8'));

        // Norsk/Norwegian/Nynorsk 33 
        $this->assertEquals(
            33,
            Horde_String::length('Eg kan eta glas utan å skada meg.', 'utf-8'));

        // Svensk/Swedish 36
        $this->assertEquals(
            36,
            Horde_String::length('Jag kan äta glas utan att skada mig.', 'utf-8'));

        // Dansk/Danish 45
        $this->assertEquals(
            45,
            Horde_String::length('Jeg kan spise glas, det gør ikke ondt på mig.', 'utf-8'));

        // Deutsch/German 41
        $this->assertEquals(
            41,
            Horde_String::length('Ich kann Glas essen, ohne mir weh zu tun.', 'utf-8'));

        // Russian 38
        $this->assertEquals(
            38,
            Horde_String::length('Я могу есть стекло, оно мне не вредит.', 'utf-8'));
    }

    /**
     * @dataProvider posProvider
     */
    public function testPos($str, $search, $pos)
    {
        $this->assertEquals(
            $pos,
            Horde_String::pos($str, $search)
        );
    }

    public function posProvider()
    {
        return array(
            array('Schöne Neue Welt', 'ö', 3),
            array('Schöne Neue Welt', 'N', 7),
            array('Schöne Neue Welt', 'e', 5),
            array('Schöne Neue Welt', ' ', 6),
            array('Schöne Neue Welt', 'a', false)
        );
    }

    /**
     * @dataProvider iposProvider
     */
    public function testIpos($str, $search, $pos)
    {
        $this->assertEquals(
            $pos,
            Horde_String::ipos($str, $search)
        );
    }

    public function iposProvider()
    {
        return array(
            array('Schöne Neue Welt', 'Ö', 3),
            array('Schöne Neue Welt', 'N', 4),
            array('Schöne Neue Welt', 'E', 5),
            array('Schöne Neue Welt', ' ', 6),
            array('Schöne Neue Welt', 'a', false)
        );
    }

    /**
     * @dataProvider rposProvider
     */
    public function testRpos($str, $search, $pos)
    {
        $this->assertEquals(
            $pos,
            Horde_String::rpos($str, $search)
        );
    }

    public function rposProvider()
    {
        return array(
            array('Schöne Neue Welt', 'ö', 3),
            array('Schöne Neue Welt', 'N', 7),
            array('Schöne Neue Welt', 'e', 13),
            array('Schöne Neue Welt', ' ', 11),
            array('Schöne Neue Welt', 'a', false)
        );
    }

    /**
     * @dataProvider riposProvider
     */
    public function testRipos($str, $search, $pos)
    {
        $this->assertEquals(
            $pos,
            Horde_String::ripos($str, $search)
        );
    }

    public function riposProvider()
    {
        return array(
            array('Schöne Neue Welt', 'Ö', 3),
            array('Schöne Neue Welt', 'N', 7),
            array('Schöne Neue Welt', 'E', 13),
            array('Schöne Neue Welt', ' ', 11),
            array('Schöne Neue Welt', 'a', false)
        );
    }

    public function testPad()
    {
        /* Simple single byte tests. */
        $this->assertEquals(
            'abc',
            Horde_String::pad('abc', 2));
        $this->assertEquals(
            'abc',
            Horde_String::pad('abc', 3));
        $this->assertEquals(
            'abc ',
            Horde_String::pad('abc', 4));
        $this->assertEquals(
            ' abc',
            Horde_String::pad('abc', 4, ' ', STR_PAD_LEFT));
        $this->assertEquals(
            'abc ',
            Horde_String::pad('abc', 4, ' ', STR_PAD_RIGHT));
        $this->assertEquals(
            'abc ',
            Horde_String::pad('abc', 4, ' ', STR_PAD_BOTH));
        $this->assertEquals(
            '  abc',
            Horde_String::pad('abc', 5, ' ', STR_PAD_LEFT));
        $this->assertEquals(
            'abc  ',
            Horde_String::pad('abc', 5, ' ', STR_PAD_RIGHT));
        $this->assertEquals(
            ' abc ',
            Horde_String::pad('abc', 5, ' ', STR_PAD_BOTH));

        /* Long padding tests. */
        $this->assertEquals(
            '=-+=-+=abc',
            Horde_String::pad('abc', 10, '=-+', STR_PAD_LEFT));
        $this->assertEquals(
            'abc=-+=-+=',
            Horde_String::pad('abc', 10, '=-+', STR_PAD_RIGHT));
        $this->assertEquals(
            '=-+abc=-+=',
            Horde_String::pad('abc', 10, '=-+', STR_PAD_BOTH));

        /* Multibyte tests. */
        $this->assertEquals(
            ' äöü',
            Horde_String::pad('äöü', 4, ' ', STR_PAD_LEFT, 'utf-8'));
        $this->assertEquals(
            'äöü ',
            Horde_String::pad('äöü', 4, ' ', STR_PAD_RIGHT, 'utf-8'));
        $this->assertEquals(
            'äöü ',
            Horde_String::pad('äöü', 4, ' ', STR_PAD_BOTH, 'utf-8'));
        $this->assertEquals(
            'äöüäöüäabc',
            Horde_String::pad('abc', 10, 'äöü', STR_PAD_LEFT, 'utf-8'));
        $this->assertEquals(
            'abcäöüäöüä',
            Horde_String::pad('abc', 10, 'äöü', STR_PAD_RIGHT, 'utf-8'));
        $this->assertEquals(
            'äöüabcäöüä',
            Horde_String::pad('abc', 10, 'äöü', STR_PAD_BOTH, 'utf-8'));

        /* Special cases. */
        $this->assertEquals(
            'abc ',
            Horde_String::pad('abc', 4, ' ', STR_PAD_RIGHT, 'utf-8'));
    }

    /**
     * @dataProvider substrProvider
     */
    public function testSubstr($match, $string, $start, $length)
    {
        $this->assertEquals(
            $match,
            Horde_String::substr($string, $start, $length, 'utf-8')
        );
    }

    public function substrProvider()
    {
        return array(
            array(
                't ämet',
                "Lörem ipsüm dölör sit ämet",
                20,
                null
            ),
            array(
                't ämet',
                "Lörem ipsüm dölör sit ämet",
                -6,
                null
            ),
            array(
                'Lörem',
                "Lörem ipsüm dölör sit ämet",
                0,
                5
            ),
            array(
                'Lörem',
                "Lörem ipsüm dölör sit ämet",
                0,
                -21
            ),
            array(
                'ipsüm',
                "Lörem ipsüm dölör sit ämet",
                6,
                5
            ),
            /* These are illegal UTF-8 encodings. */
            array(
                '',
                base64_decode('2KvYpw=='),
                2,
                2
            ),
            array(
                '',
                base64_decode('2KU='),
                1,
                1
            ),
            array(
                '',
                base64_decode('2KvYpw=='),
                2,
                2
            ),
            array(
                '',
                base64_decode('2KI='),
                1,
                1
            ),
            array(
                '',
                base64_decode('5L6L'),
                1,
                1
            ),
            array(
                '',
                base64_decode('5rWL'),
                1,
                1
            ),
            array(
                '',
                base64_decode('5ris'),
                1,
                1
            ),
            array(
                '',
                base64_decode('0L/RgNC40LzQtQ=='),
                5,
                5
            ),
            array(
                '',
                base64_decode('0LA='),
                1,
                1
            ),
            array(
                '',
                base64_decode('4KSJ'),
                1,
                1
            ),
            array(
                '',
                base64_decode('4KSq4KSw4KSV'),
                3,
                3
            ),
        );
    }

    public function testWordwrap()
    {
        // Test default parameters and break character.
        $string = "Lörem ipsüm dölör sit ämet, cönsectetüer ädipiscing elit. Aliqüäm söllicitüdin fäücibüs mäüris ämet.";
        $this->assertEquals(
<<<EOT
Lörem ipsüm dölör sit ämet, cönsectetüer ädipiscing elit. Aliqüäm
söllicitüdin fäücibüs mäüris ämet.
EOT
,
            Horde_String::wordwrap($string));
        $this->assertEquals(
<<<EOT
Lörem ipsüm dölör sit ämet,
  cönsectetüer ädipiscing elit.
  Aliqüäm söllicitüdin fäücibüs
  mäüris ämet.
EOT
,
            Horde_String::wordwrap($string, 30, "\n  "));

        // Test existing line breaks.
        $string = "Lörem ipsüm dölör sit ämet, cönsectetüer ädipiscing elit.\nAliqüäm söllicitüdin fäücibüs mäüris ämet.";
        $this->assertEquals(
<<<EOT
Lörem ipsüm dölör sit ämet, cönsectetüer ädipiscing elit.
Aliqüäm söllicitüdin fäücibüs mäüris ämet.
EOT
,
            Horde_String::wordwrap($string));
        $string = "Lörem ipsüm dölör sit ämet, cönsectetüer ädipiscing elit. Aliqüäm\nsöllicitüdin fäücibüs mäüris ämet.";
        $this->assertEquals(
<<<EOT
Lörem ipsüm dölör sit ämet, cönsectetüer ädipiscing elit. Aliqüäm
söllicitüdin fäücibüs mäüris ämet.
EOT
,
            Horde_String::wordwrap($string));
        $string = "Lörem ipsüm dölör sit ämet, cönsectetüer ädipiscing elit. Aliqüäm söllicitüdin\nfäücibüs mäüris ämet.";
        $this->assertEquals(
<<<EOT
Lörem ipsüm dölör sit ämet, cönsectetüer ädipiscing elit. Aliqüäm
söllicitüdin
fäücibüs mäüris ämet.
EOT
,
            Horde_String::wordwrap($string));
        $string = "Lörem ipsüm dölör sit ämet, cönsectetüer ädipiscing elit. Aliqüäm söllicitüdin fäücibüs mäüris ämet.\nLörem ipsüm dölör sit ämet.\nLörem ipsüm dölör sit ämet, cönsectetüer ädipiscing elit. Aliqüäm söllicitüdin fäücibüs mäüris ämet.";
        $this->assertEquals(
<<<EOT
Lörem ipsüm dölör sit ämet, cönsectetüer ädipiscing elit. Aliqüäm
söllicitüdin fäücibüs mäüris ämet.
Lörem ipsüm dölör sit ämet.
Lörem ipsüm dölör sit ämet, cönsectetüer ädipiscing elit. Aliqüäm
söllicitüdin fäücibüs mäüris ämet.
EOT
,
            Horde_String::wordwrap($string));

        // Test overlong words and word cut.
        $string = "Löremipsümdölörsitämet, cönsectetüerädipiscingelit.";
        $this->assertEquals(
<<<EOT
Löremipsümdölörsitämet,
cönsectetüerädipiscingelit.
EOT
,
            Horde_String::wordwrap($string, 15));
        $string = "Löremipsümdölörsitämet, cönsectetüerädipiscingelit.";
        $this->assertEquals(
<<<EOT
Löremipsümdölör
sitämet,
cönsectetüerädi
piscingelit.
EOT
,
            Horde_String::wordwrap($string, 15, "\n", true));

        // Test whitespace at wrap width.
        $string = "Lörem ipsüm dölör sit ämet, cönsectetüer ädipiscing";
        $this->assertEquals(
<<<EOT
Lörem ipsüm dölör sit ämet,
cönsectetüer ädipiscing
EOT
,
            Horde_String::wordwrap($string, 27));
        $this->assertEquals(
<<<EOT
Lörem ipsüm dölör sit ämet,
cönsectetüer ädipiscing
EOT
,
            Horde_String::wordwrap($string, 28));

        // Test line folding.
        $string = "Löremipsümdölörsitämet, cönsectetüerädipiscingelit.";
        $this->assertEquals(
<<<EOT
Löremipsümdölör
sitämet,
 cönsectetüeräd
ipiscingelit.
EOT
,
            Horde_String::wordwrap($string, 15, "\n", true, 'utf-8', true));
        $string = "Lörem ipsüm dölör sit ämet,  cönsectetüer ädipiscing elit.  Aliqüäm söllicitüdin fäücibüs mäüris ämet.";
        $this->assertEquals(
<<<EOT
Lörem ipsüm dölör sit ämet,
  cönsectetüer ädipiscing elit.
  Aliqüäm söllicitüdin fäücibüs
 mäüris ämet.
EOT
,
            Horde_String::wordwrap($string, 31, "\n", false, 'utf-8', true));
        $string = "Lörem ipsüm dölör sit; ämet:  cönsectetüer ädipiscing elit.  Aliqüäm söllicitüdin fäücibüs mäüris ämet.";
        $this->assertEquals(
<<<EOT
Lörem ipsüm dölör sit;
 ämet:
  cönsectetüer ädipiscing elit.
  Aliqüäm söllicitüdin fäücibüs
 mäüris ämet.
EOT
,
            Horde_String::wordwrap($string, 31, "\n", false, 'utf-8', true));
        $string = "Lörem ipsüm dölör sit; ämet:cönsectetüer ädipiscing elit.  Aliqüäm söllicitüdin fäücibüs mäüris ämet.";
        $this->assertEquals(
<<<EOT
Lörem ipsüm dölör sit;
 ämet:cönsectetüer ädipiscing
 elit.  Aliqüäm söllicitüdin
 fäücibüs mäüris ämet.
EOT
,
            Horde_String::wordwrap($string, 31, "\n", false, 'utf-8', true));
        $string = "Lörem ipsüm dölör sit; ämet;  cönsectetüer ädipiscing elit.  Aliqüäm söllicitüdin fäücibüs mäüris ämet.";
        $this->assertEquals(
<<<EOT
Lörem ipsüm dölör sit;
 ämet;
  cönsectetüer ädipiscing elit.
  Aliqüäm söllicitüdin fäücibüs
 mäüris ämet.
EOT
,
            Horde_String::wordwrap($string, 31, "\n", false, 'utf-8', true));
        $string = "Lörem ipsüm dölör sit; ämet;cönsectetüer ädipiscing elit.  Aliqüäm söllicitüdin fäücibüs mäüris ämet.";
        $this->assertEquals(
<<<EOT
Lörem ipsüm dölör sit;
 ämet;cönsectetüer ädipiscing
 elit.  Aliqüäm söllicitüdin
 fäücibüs mäüris ämet.
EOT
,
            Horde_String::wordwrap($string, 31, "\n", false, 'utf-8', true));
    }

    public function testCommon()
    {
        $this->assertEquals('', Horde_String::common('foo', 'bar'));
        $this->assertEquals('foo', Horde_String::common('foobar', 'fooxyx'));
        $this->assertEquals('foo', Horde_String::common('foo', 'foobar'));
    }

    public function testBug9528()
    {
        $this->assertEquals(
            "<html>",
            Horde_String::convertCharset("<html>", 'UTF-8', 'Windows-1258')
        );
    }

    public function testLongStringsBreakUtf8DetectionRegex()
    {
        $string = str_repeat('1 A B', 10000);

        /* Failing test will cause a PHP segfault here. */
        Horde_String::validUtf8($string);
    }

    /**
     * @dataProvider validUtf8Provider
     */
    public function testValidUtf8($in)
    {
        $this->assertTrue(Horde_String::validUtf8($in));
    }

    public function validUtf8Provider()
    {
        // Examples from:
        // http://www.php.net/manual/en/reference.pcre.pattern.modifiers.php#54805
        return array(
            // Valid ASCII
            array("a"),
            // Valid 2 Octet Sequence
            array("\xc3\xb1"),
            // Valid 3 Octet Sequence
            array("\xe2\x82\xa1"),
            // Valid 4 Octet Sequence
            array("\xf0\x90\x8c\xbc"),
            // Bug #11930
            array('ö ä ü ß\n\nMit freundlichen Grüßen'),
            // Bug #11930-2
            array('öäüß')
        );
    }

    /**
     * @dataProvider invalidUtf8Provider
     */
    public function testInvalidUtf8($in)
    {
        $this->assertFalse(Horde_String::validUtf8($in));
    }

    public function invalidUtf8Provider()
    {
        // Examples from:
        // http://www.php.net/manual/en/reference.pcre.pattern.modifiers.php#54805
        return array(
            // Invalid 2 Octet Sequence
            array("\xc3\x28"),
            // Invalid Sequence Identifier
            array("\xa0\xa1"),
            // Invalid 3 Octet Sequence (in 2nd Octet)
            array("\xe2\x28\xa1"),
            // Invalid 3 Octet Sequence (in 3rd Octet)
            array("\xe2\x82\x28"),
            // Invalid 4 Octet Sequence (in 2nd Octet)
            array("\xf0\x28\x8c\xbc"),
            // Invalid 4 Octet Sequence (in 3rd Octet)
            array("\xf0\x90\x28\xbc"),
            // Invalid 4 Octet Sequence (in 4th Octet)
            array("\xf0\x28\x8c\x28"),
            // Valid 5 Octet Sequence (but not Unicode!)
            array("\xf8\xa1\xa1\xa1\xa1"),
            // Valid 6 Octet Sequence (but not Unicode!)
            array("\xfc\xa1\xa1\xa1\xa1\xa1")
        );
    }

}

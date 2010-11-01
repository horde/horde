<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @category   Horde
 * @package    Util
 * @subpackage UnitTests
 */

class Horde_Util_StringTest extends PHPUnit_Framework_TestCase
{
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

    public function testSubstr()
    {
        $string = "Lörem ipsüm dölör sit ämet";
        $this->assertEquals(
            't ämet',
            Horde_String::substr($string, 20, null, 'utf-8'));
        $this->assertEquals(
            't ämet',
            Horde_String::substr($string, -6, null, 'utf-8'));
        $this->assertEquals(
            'Lörem',
            Horde_String::substr($string, 0, 5, 'utf-8'));
        $this->assertEquals(
            'Lörem',
            Horde_String::substr($string, 0, -21, 'utf-8'));
        $this->assertEquals(
            'ipsüm',
            Horde_String::substr($string, 6, 5, 'utf-8'));
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
}

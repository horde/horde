<?php
/**
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */

/**
 * Tests for the IMAP string tokenizer.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @ignore
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */
class Horde_Imap_Client_TokenizeTest extends PHPUnit_Framework_TestCase
{
    public function testTokenizeSimple()
    {
        $test1 = 'FOO BAR';
        $token1 = new Horde_Imap_Client_Tokenize($test1);

        $tmp = iterator_to_array($token1);

        $this->assertEquals(
            'FOO',
            $tmp[0]
        );

        $this->assertEquals(
            'BAR',
            $tmp[1]
        );
    }

    public function testTokenizeQuotes()
    {
        $test1 = 'FOO "BAR"';
        $token1 = new Horde_Imap_Client_Tokenize($test1);

        $tmp = iterator_to_array($token1);

        $this->assertEquals(
            'FOO',
            $tmp[0]
        );

        $this->assertEquals(
            'BAR',
            $tmp[1]
        );

        $test2 = '"\"BAR\""';
        $token2 = new Horde_Imap_Client_Tokenize($test2);

        $tmp = iterator_to_array($token2);

        $this->assertEquals(
            '"BAR"',
            $tmp[0]
        );
    }

    public function testTokenizeLiteral()
    {
        $test1 = 'FOO {3}BAR BAZ';
        $token1 = new Horde_Imap_Client_Tokenize($test1);

        $tmp = iterator_to_array($token1);

        $this->assertEquals(
            'FOO',
            $tmp[0]
        );

        $this->assertEquals(
            'BAR',
            $tmp[1]
        );

        $this->assertEquals(
            'BAZ',
            $tmp[2]
        );
    }

    public function testTokenizeNil()
    {
        $test1 = 'FOO NIL';
        $token1 = new Horde_Imap_Client_Tokenize($test1);
        $token1->rewind();

        $this->assertEquals(
            'FOO',
            $token1->next()
        );
        $this->assertNull($token1->next());
        $this->assertFalse($token1->next());
    }

    public function testTokenizeList()
    {
        $test1 = 'FOO ("BAR") BAZ';
        $token1 = new Horde_Imap_Client_Tokenize($test1);
        $token1->rewind();

        $this->assertEquals(
            'FOO',
            $token1->next()
        );

        $this->assertTrue($token1->next());

        $this->assertEquals(
            'BAR',
            $token1->next()
        );

        $this->assertFalse($token1->next());

        $this->assertEquals(
            'BAZ',
            $token1->next()
        );

        $this->assertFalse($token1->next());

        $test2 = '(BAR NIL NIL)';
        $token2 = new Horde_Imap_Client_Tokenize($test2);
        $token2->rewind();

        $this->assertTrue($token2->next());

        $this->assertEquals(
            'BAR',
            $token2->next()
        );

        $this->assertNull($token2->next());
        $this->assertNull($token2->next());
        $this->assertFalse($token2->next());

        $test3 = '(\Foo)';
        $token3 = new Horde_Imap_Client_Tokenize($test3);
        $token3->rewind();

        $this->assertTrue($token3->next());

        $this->assertEquals(
            '\\Foo',
            $token3->next()
        );

        $this->assertFalse($token3->next());
    }

    public function testTokenizeBadWhitespace()
    {
        $test1 = '  FOO  BAR ';
        $token1 = new Horde_Imap_Client_Tokenize($test1);

        $tmp = iterator_to_array($token1);

        $this->assertEquals(
            'FOO',
            $tmp[0]
        );

        $this->assertEquals(
            'BAR',
            $tmp[1]
        );
    }

    public function testTokenizeComplexFetchExample()
    {
        $test = <<<EOT
* 8 FETCH (UID 39210 BODYSTRUCTURE (("text" "plain" ("charset" "ISO-8859-1") NIL NIL "quoted-printable" 1559 40 NIL NIL NIL NIL)("text" "html" ("charset" "ISO-8859-1") NIL NIL {16}quoted-printable 25318 427 NIL NIL NIL NIL) {11}alternative ("boundary" "_Part_1_xMiAxODoyNjozNyAtMDQwMA==") NIL NIL NIL))
EOT;

        $token = new Horde_Imap_Client_Tokenize($test);
        $token->rewind();

        $this->assertEquals(
            '*',
            $token->next()
        );
        $this->assertEquals(
            '8',
            $token->next()
        );
        $this->assertEquals(
            'FETCH',
            $token->next()
        );

        $this->assertTrue($token->next());

        $this->assertEquals(
            'UID',
            $token->next()
        );
        $this->assertEquals(
            '39210',
            $token->next()
        );
        $this->assertEquals(
            'BODYSTRUCTURE',
            $token->next()
        );

        $this->assertTrue($token->next());
        $this->assertTrue($token->next());

        $this->assertEquals(
            'text',
            $token->next()
        );
        $this->assertEquals(
            'plain',
            $token->next()
        );

        $this->assertTrue($token->next());

        $this->assertEquals(
            'charset',
            $token->next()
        );
        $this->assertEquals(
            'ISO-8859-1',
            $token->next()
        );

        $this->assertFalse($token->next());

        $this->assertNull($token->next());
        $this->assertNull($token->next());
        $this->assertEquals(
            'quoted-printable',
            $token->next()
        );
        $this->assertEquals(
            1559,
            $token->next()
        );
        $this->assertEquals(
            40,
            $token->next()
        );
        $this->assertNull($token->next());
        $this->assertNull($token->next());
        $this->assertNull($token->next());
        $this->assertNull($token->next());
        $this->assertFalse($token->next());

        $this->assertTrue($token->next());

        $this->assertEquals(
            'text',
            $token->next()
        );
        $this->assertEquals(
            'html',
            $token->next()
        );

        $this->assertTrue($token->next());

        $this->assertEquals(
            'charset',
            $token->next()
        );
        $this->assertEquals(
            'ISO-8859-1',
            $token->next()
        );

        $this->assertFalse($token->next());

        $this->assertNull($token->next());
        $this->assertNull($token->next());
        $this->assertEquals(
            'quoted-printable',
            $token->next()
        );
        $this->assertEquals(
            25318,
            $token->next()
        );
        $this->assertEquals(
            427,
            $token->next()
        );
        $this->assertNull($token->next());
        $this->assertNull($token->next());
        $this->assertNull($token->next());
        $this->assertNull($token->next());
        $this->assertFalse($token->next());

        $this->assertEquals(
            'alternative',
            $token->next()
        );

        $this->assertTrue($token->next());

        $this->assertEquals(
            'boundary',
            $token->next()
        );
        $this->assertEquals(
            '_Part_1_xMiAxODoyNjozNyAtMDQwMA==',
            $token->next()
        );
        $this->assertFalse($token->next());

        $this->assertNull($token->next());
        $this->assertNull($token->next());
        $this->assertNull($token->next());
        $this->assertFalse($token->next());
        $this->assertFalse($token->next());
        $this->assertFalse($token->next());
    }

    public function testBug11450()
    {
        $test = '* NAMESPACE (("INBOX." ".")) (("user." ".")) (("" "."))';
        $token = new Horde_Imap_Client_Tokenize($test);
        $token->rewind();

        $this->assertEquals(
            '*',
            $token->next()
        );
        $this->assertEquals(
            'NAMESPACE',
            $token->next()
        );

        $this->assertTrue($token->next());
        $this->assertTrue($token->next());

        $this->assertEquals(
            'INBOX.',
            $token->next()
        );
        $this->assertEquals(
            '.',
            $token->next()
        );

        $this->assertFalse($token->next());
        $this->assertFalse($token->next());

        $this->assertTrue($token->next());
        $this->assertTrue($token->next());

        $this->assertEquals(
            'user.',
            $token->next()
        );
        $this->assertEquals(
            '.',
            $token->next()
        );

        $this->assertFalse($token->next());
        $this->assertFalse($token->next());

        $this->assertTrue($token->next());
        $this->assertTrue($token->next());

        $this->assertEquals(
            '',
            $token->next()
        );
        $this->assertEquals(
            '.',
            $token->next()
        );

        $this->assertFalse($token->next());
        $this->assertFalse($token->next());
    }

}

<?php
/**
 * Tests for the IMAP string tokenizer.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Imap_Client
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Imap_Client
 */

/**
 * Tests for the IMAP string tokenizer.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Imap_Client
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Imap_Client
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

        $this->assertEquals(
            'FOO',
            $token1->rewind()
        );
        $this->assertNull($token1->next());
        $this->assertFalse($token1->next());
    }

    public function testTokenizeList()
    {
        $test1 = 'FOO ("BAR") BAZ';
        $token1 = new Horde_Imap_Client_Tokenize($test1);

        $this->assertEquals(
            'FOO',
            $token1->rewind()
        );

        $inner = $token1->next();
        $this->assertTrue($inner instanceof Horde_Imap_Client_Tokenize);

        $this->assertEquals(
            'BAR',
            $inner->rewind()
        );
        $this->assertFalse($inner->next());

        $this->assertEquals(
            'BAZ',
            $token1->next()
        );

        $test2 = '(BAR NIL NIL)';
        $token2 = new Horde_Imap_Client_Tokenize($test2);

        $inner = $token2->rewind();
        $this->assertTrue($inner instanceof Horde_Imap_Client_Tokenize);

        $this->assertEquals(
            'BAR',
            $inner->rewind()
        );
        $this->assertNull($inner->next());
        $this->assertNull($inner->next());
        $this->assertFalse($inner->next());

        $test3 = '(\Foo)';
        $token3 = new Horde_Imap_Client_Tokenize($test3);

        $inner = $token3->rewind();
        $this->assertTrue($inner instanceof Horde_Imap_Client_Tokenize);

        $this->assertEquals(
            '\\Foo',
            $inner->rewind()
        );
    }

    public function testTokenizeListWithoutExhaustingInnerStream()
    {
        $test1 = 'FOO ("BAR") BAZ';
        $token1 = new Horde_Imap_Client_Tokenize($test1);

        $this->assertEquals(
            'FOO',
            $token1->rewind()
        );

        $inner = $token1->next();

        $this->assertEquals(
            'BAZ',
            $token1->next()
        );
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

        $this->assertEquals(
            '*',
            $token->rewind()
        );
        $this->assertEquals(
            '8',
            $token->next()
        );
        $this->assertEquals(
            'FETCH',
            $token->next()
        );

        $inner = $token->next();
        $this->assertTrue($inner instanceof Horde_Imap_Client_Tokenize);

        $this->assertEquals(
            'UID',
            $inner->rewind()
        );
        $this->assertEquals(
            '39210',
            $inner->next()
        );
        $this->assertEquals(
            'BODYSTRUCTURE',
            $inner->next()
        );

        $inner2 = $inner->next();
        $this->assertTrue($inner2 instanceof Horde_Imap_Client_Tokenize);

        $inner3 = $inner2->rewind();
        $this->assertTrue($inner3 instanceof Horde_Imap_Client_Tokenize);

        $this->assertEquals(
            'text',
            $inner3->rewind()
        );
        $this->assertEquals(
            'plain',
            $inner3->next()
        );

        $inner4 = $inner3->next();
        $this->assertTrue($inner4 instanceof Horde_Imap_Client_Tokenize);

        $this->assertEquals(
            'charset',
            $inner4->rewind()
        );
        $this->assertEquals(
            'ISO-8859-1',
            $inner4->next()
        );

        $this->assertFalse($inner4->next());

        $this->assertNull($inner3->next());
        $this->assertNull($inner3->next());
        $this->assertEquals(
            'quoted-printable',
            $inner3->next()
        );
        $this->assertEquals(
            1559,
            $inner3->next()
        );
        $this->assertEquals(
            40,
            $inner3->next()
        );
        $this->assertNull($inner3->next());
        $this->assertNull($inner3->next());
        $this->assertNull($inner3->next());
        $this->assertNull($inner3->next());
        $this->assertFalse($inner3->next());

        $inner3 = $inner2->next();
        $this->assertTrue($inner3 instanceof Horde_Imap_Client_Tokenize);

        $this->assertEquals(
            'text',
            $inner3->rewind()
        );
        $this->assertEquals(
            'html',
            $inner3->next()
        );

        $inner4 = $inner3->next();
        $this->assertTrue($inner4 instanceof Horde_Imap_Client_Tokenize);

        $this->assertEquals(
            'charset',
            $inner4->rewind()
        );
        $this->assertEquals(
            'ISO-8859-1',
            $inner4->next()
        );

        $this->assertFalse($inner4->next());

        $this->assertNull($inner3->next());
        $this->assertNull($inner3->next());
        $this->assertEquals(
            'quoted-printable',
            $inner3->next()
        );
        $this->assertEquals(
            25318,
            $inner3->next()
        );
        $this->assertEquals(
            427,
            $inner3->next()
        );
        $this->assertNull($inner3->next());
        $this->assertNull($inner3->next());
        $this->assertNull($inner3->next());
        $this->assertNull($inner3->next());
        $this->assertFalse($inner3->next());

        $this->assertEquals(
            'alternative',
            $inner2->next()
        );

        $inner3 = $inner2->next();
        $this->assertTrue($inner3 instanceof Horde_Imap_Client_Tokenize);

        $this->assertEquals(
            'boundary',
            $inner3->rewind()
        );
        $this->assertEquals(
            '_Part_1_xMiAxODoyNjozNyAtMDQwMA==',
            $inner3->next()
        );
        $this->assertFalse($inner3->next());

        $this->assertNull($inner2->next());
        $this->assertNull($inner2->next());
        $this->assertNull($inner2->next());
        $this->assertFalse($inner2->next());

        $this->assertFalse($inner->next());

        $this->assertFalse($token->next());
    }

    public function testBug11450()
    {
        $test = '* NAMESPACE (("INBOX." ".")) (("user." ".")) (("" "."))';
        $token = new Horde_Imap_Client_Tokenize($test);

        $this->assertEquals(
            '*',
            $token->rewind()
        );
        $this->assertEquals(
            'NAMESPACE',
            $token->next()
        );

        $inner = $token->next();
        $this->assertTrue($inner instanceof Horde_Imap_Client_Tokenize);

        $inner2 = $inner->rewind();
        $this->assertTrue($inner2 instanceof Horde_Imap_Client_Tokenize);

        $this->assertEquals(
            'INBOX.',
            $inner2->rewind()
        );
        $this->assertEquals(
            '.',
            $inner2->next()
        );
        $this->assertFalse($inner2->next());

        $this->assertFalse($inner->next());

        $inner = $token->next();
        $this->assertTrue($inner instanceof Horde_Imap_Client_Tokenize);

        $inner2 = $inner->rewind();
        $this->assertTrue($inner2 instanceof Horde_Imap_Client_Tokenize);

        $this->assertEquals(
            'user.',
            $inner2->rewind()
        );
        $this->assertEquals(
            '.',
            $inner2->next()
        );
        $this->assertFalse($inner2->next());

        $this->assertFalse($inner->next());

        $inner = $token->next();
        $this->assertTrue($inner instanceof Horde_Imap_Client_Tokenize);

        $inner2 = $inner->rewind();
        $this->assertTrue($inner2 instanceof Horde_Imap_Client_Tokenize);

        $this->assertEquals(
            '',
            $inner2->rewind()
        );
        $this->assertEquals(
            '.',
            $inner2->next()
        );
        $this->assertFalse($inner2->next());

        $this->assertFalse($inner->next());

        $this->assertFalse($token->next());
    }

}

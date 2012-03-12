<?php
/**
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Listheaders
 * @subpackage UnitTests
 */

class Horde_ListHeaders_ParseTest extends PHPUnit_Framework_TestCase
{
    private $parser;

    public function setUp()
    {
        $this->parser = new Horde_ListHeaders();
    }

    public function testBaseParsing()
    {
        $header = '<mailto:list@host.com?subject=help> (List Instructions)';
        $ob = $this->parser->parse('list-help', $header);

        $this->assertEquals(
            1,
            count($ob)
        );
        $this->assertEquals(
            'mailto:list@host.com?subject=help',
            $ob[0]->url
        );
        $this->assertEquals(
            'List Instructions',
            $ob[0]->comments[0]
        );
    }

    public function testSubitemParsing()
    {
        $header = '<ftp://ftp.host.com/list.txt> (FTP), <mailto:list@host.com?subject=help>';
        $ob = $this->parser->parse('list-help', $header);

        $this->assertEquals(
            2,
            count($ob)
        );

        $this->assertEquals(
            'ftp://ftp.host.com/list.txt',
            $ob[0]->url
        );
        $this->assertEquals(
            'FTP',
            $ob[0]->comments[0]
        );

        $this->assertEquals(
            'mailto:list@host.com?subject=help',
            $ob[1]->url
        );
        $this->assertEmpty($ob[1]->comments);
    }

    public function testDoubleCommentParsing()
    {
        $header = '(Foo) <mailto:foo@example.com> (Foo2)';
        $ob = $this->parser->parse('list-help', $header);

        $this->assertEquals(
            1,
            count($ob)
        );

        $this->assertEquals(
            2,
            count($ob[0]->comments)
        );

        $this->assertEquals(
            'Foo',
            $ob[0]->comments[0]
        );

        $this->assertEquals(
            'Foo2',
            $ob[0]->comments[1]
        );
    }

    public function testListPostParsing()
    {
        $header = '<mailto:foo@example.com> (Foo)';
        $ob = $this->parser->parse('list-post', $header);

        $this->assertEquals(
            1,
            count($ob)
        );
        $this->assertFalse($ob[0] instanceof Horde_ListHeaders_NoPost);

        $this->assertEquals(
            'mailto:foo@example.com',
            $ob[0]->url
        );

        $this->assertEquals(
            'Foo',
            $ob[0]->comments[0]
        );
    }

    public function testListPostParsingWhenNoPostingAllowed()
    {
        $header = 'NO (Foo)';
        $ob = $this->parser->parse('list-post', $header);

        $this->assertEquals(
            1,
            count($ob)
        );
        $this->assertTrue($ob[0] instanceof Horde_ListHeaders_NoPost);
        $this->assertNull($ob[0]->url);

        $this->assertEquals(
            'Foo',
            $ob[0]->comments[0]
        );
    }

    public function testListIdParsing()
    {
        $header = '<commonspace-users.list-id.within.com>';
        $ob = $this->parser->parse('list-id', $header);

        $this->assertTrue($ob instanceof Horde_ListHeaders_Id);
        $this->assertEquals(
            'commonspace-users.list-id.within.com',
            $ob->id
        );
        $this->assertNull($ob->label);

        $header = '"Lena\'s Personal Joke List" <lenas-jokes.da39efc25c530ad145d41b86f7420c3b.021999.localhost>';
        $ob = $this->parser->parse('list-id', $header);

        $this->assertTrue($ob instanceof Horde_ListHeaders_Id);
        $this->assertEquals(
            'lenas-jokes.da39efc25c530ad145d41b86f7420c3b.021999.localhost',
            $ob->id
        );
        $this->assertEquals(
            "Lena's Personal Joke List",
            $ob->label
        );
    }

}

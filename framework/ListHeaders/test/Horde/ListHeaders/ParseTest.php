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

    /**
     * @dataProvider parsingProvider
     */
    public function testBaseParsing($header, $value, $urls, $comments)
    {
        $ob = $this->parser->parse($header, $value);

        $this->assertEquals(
            count($urls),
            count($ob)
        );

        foreach (array_values($urls) as $key => $val) {
            if (is_null($urls[$key])) {
                $this->assertTrue($ob[$key] instanceof Horde_ListHeaders_NoPost);
                $this->assertNull($ob[$key]->url);
            } else {
                $this->assertFalse($ob[$key] instanceof Horde_ListHeaders_NoPost);
                $this->assertEquals(
                    $urls[$key],
                    $ob[$key]->url
                );
            }

            if (empty($comments[$key])) {
                $this->assertEmpty($ob[$key]->comments);
            } else {
                foreach ($comments[$key] as $key2 => $val2) {
                    $this->assertEquals(
                        $val2,
                        $ob[$key]->comments[$key2]
                    );
                }
            }
        }
    }

    public function parsingProvider()
    {
        return array(
            array(
                'list-help',
                '<mailto:list@host.com?subject=help> (List Instructions)',
                array(
                    'mailto:list@host.com?subject=help'
                ),
                array(
                    array('List Instructions')
                )
            ),
            array(
                'list-help',
                '<ftp://ftp.host.com/list.txt> (FTP), <mailto:list@host.com?subject=help>',
                array(
                    'ftp://ftp.host.com/list.txt',
                    'mailto:list@host.com?subject=help'
                ),
                array(
                    array('FTP'),
                    array()
                )
            ),
            array(
                'list-help',
                '(Foo) <mailto:foo@example.com> (Foo2)',
                array(
                    'mailto:foo@example.com'
                ),
                array(
                    array('Foo', 'Foo2'),
                )
            ),
            array(
                'list-post',
                '<mailto:foo@example.com> (Foo)',
                array(
                    'mailto:foo@example.com'
                ),
                array(
                    array('Foo')
                )
            ),
            array(
                'list-post',
                'NO (Foo)',
                array(
                    null
                ),
                array(
                    array('Foo')
                )
            ),
        );
    }

    /**
     * @dataProvider listIdParsingProvider
     */
    public function testListIdParsing($value, $id, $label)
    {
        $ob = $this->parser->parse('list-id', $value);

        $this->assertTrue($ob instanceof Horde_ListHeaders_Id);
        $this->assertEquals(
            $id,
            $ob->id
        );

        if (is_null($label)) {
            $this->assertNull($ob->label);
        } else {
            $this->assertEquals(
                $label,
                $ob->label
            );
        }
    }

    public function listIdParsingProvider()
    {
        return array(
            array(
                '<commonspace-users.list-id.within.com>',
                'commonspace-users.list-id.within.com',
                null
            ),
            array(
                '"Lena\'s Personal Joke List" <lenas-jokes.da39efc25c530ad145d41b86f7420c3b.021999.localhost>',
                'lenas-jokes.da39efc25c530ad145d41b86f7420c3b.021999.localhost',
                "Lena's Personal Joke List"
            )
        );
    }

}

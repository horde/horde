<?php
/**
 * Copyright 2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Refactor
 * @subpackage UnitTests
 */

namespace Horde\Refactor;

use Horde\Refactor\Exception;
use Horde\Refactor\Regexp;
use Horde\Refactor\Tokens;

/**
 * Tests the tokenizer.
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @copyright  2017 Horde LLC
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Refactor
 * @subpackage UnitTests
 */
class TokensTest extends \Horde_Test_Case
{
    protected $tokens;

    /**
     * @expectedException \Horde\Refactor\Exception\NotFound
     * @expectedExceptionMessage Token "{" Not Found
     */
    public function testInvalidFunction()
    {
        $tokens = new Tokens($this->tokens);
        $query = array(
            array(T_FUNCTION),
            array(T_WHITESPACE),
            array(T_STRING, 'broken')
        );
        while ($tokens->valid() &&
               !$tokens->matchesAll($query)) {
            $tokens->next();
        }
        $tokens->next();
        $tokens->findFunctionTokens();
    }

    /**
     * @expectedException \Horde\Refactor\Exception\NotFound
     * @expectedExceptionMessage Token "T_FUNCTION" Not Found
     */
    public function testNotInsideFunction()
    {
        $tokens = new Tokens($this->tokens);
        $tokens->seek(1);
        $tokens->findFunctionTokens();
    }

    public function testSkipWhitespace()
    {
        $tokens = new Tokens($this->tokens);
        $tokens->find(T_CLASS);
        $tokens->skipWhitespace(true);
        $this->assertTrue($tokens->matches(T_DOC_COMMENT));
    }

    /**
     * @expectedException \Horde\Refactor\Exception\UnexpectedToken
     * @expectedExceptionMessage Unexpected Token "T_CLASS"
     */
    public function testBracketNoString()
    {
        $tokens = new Tokens($this->tokens);
        $tokens->find(T_CLASS);
        $tokens->findMatchingBracket();
    }

    /**
     * @expectedException \Horde\Refactor\Exception\UnexpectedToken
     * @expectedExceptionMessage Unexpected Token ";"
     */
    public function testBracketNoBracket()
    {
        $tokens = new Tokens($this->tokens);
        $tokens->find(';');
        $tokens->findMatchingBracket();
    }

    public function testMultilevelBrackets()
    {
        $tokens = new Tokens($this->tokens);
        $tokens->find('{');
        $old = $tokens->key();
        $tokens->findMatchingBracket();
        $this->assertGreaterThan($old, $tokens->key());
        try {
            $tokens->findFunctionTokens();
            $this->fail('Should have thrown NotFound exception');
        } catch (Exception\NotFound $e) {
        }
    }

    public function testFindBracketsBackwards()
    {
        $tokens = new Tokens($this->tokens);
        $tokens->find('}');
        $old = $tokens->key();
        $tokens->findMatchingBracket(null, true);
        $this->assertLessThan($old, $tokens->key());
    }

    /**
     * @expectedException \Horde\Refactor\Exception\NotFound
     * @expectedExceptionMessage Token "}" Not Found
     */
    public function testDontFindBrackets()
    {
        $tokens = new Tokens(token_get_all('<?php {'));
        $tokens->find('{');
        $tokens->findMatchingBracket();
    }

    public function testMatchesInvalid()
    {
        $tokens = new Tokens(array());
        $tokens->next();
        $this->assertFalse($tokens->valid());
        $this->assertFalse($tokens->matches(''));
    }

    public function testMatches()
    {
        $tokens = new Tokens($this->tokens);
        $this->assertTrue($tokens->findConstruct(T_FUNCTION, 'broken'));
        $this->assertTrue($tokens->matches(T_STRING));
    }

    public function testMatchesRegexp()
    {
        $tokens = new Tokens($this->tokens);
        $tokens->find('{');
        $this->assertTrue($tokens->matches(new Regexp('/./')));
        $this->assertFalse($tokens->matches(new Regexp('/x/')));
        $this->assertTrue($tokens->findConstruct(T_FUNCTION, 'broken'));
        $this->assertTrue($tokens->matches(T_STRING, new Regexp('/^brok/')));
        $this->assertFalse($tokens->matches(T_STRING, new Regexp('/BROKEN/')));
    }

    public function testSpliceWithoutLength()
    {
        $tokens = new Tokens($this->tokens);
        $count = count($tokens);
        $tokens = $tokens->splice($count - 2);
        $this->assertEquals($count - 2, count($tokens));
    }

    public function setUp()
    {
        $this->tokens = token_get_all(
            <<<PHPCODE
<?php
/**
 * File-level DocBlock.
 *
 * Description.
 */

/**
 * Class-level DocBlock.
 *
 * Description.
 */
class Foo extends Bar
{
    function()
    {
        echo "";
    }

    function broken()
}
PHPCODE
        );
    }
}

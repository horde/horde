<?php
/**
 * Copyright 2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Refactor
 */

namespace Horde\Refactor;
use Horde\Refactor\Exception;

/**
 * Custom ArrayIterator implementation representing the tokens.
 *
 * <code>
 * $iterator = new Horde\Refactor\Iterator(token_get_all('<?php echo "foo"; ?>'));
 * $iterator->find(T_ECHO);
 * $iterator->find(T_CONSTANT_ENCAPSED_STRING);
 * $iterator->find(T_CONSTANT_ENCAPSED_STRING, '"foo"', true);
 * </code>
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Refactor
 */
class Tokens extends \ArrayIterator
{
    /**
     * Moves the iterator to previous entry.
     */
    public function previous()
    {
        $this->seek($this->key() - 1);
    }

    /**
     * Seeks to a certain string or token.
     *
     * @param string|integer|\Horde\Refactor\Regexp $token
     *        Token to search for. One of the T_* token constants or a plain
     *        string. See token_get_all().
     * @param string $term           If $token is a token that can have
     *                               individual content, the term to search for
     *                               in the content.
     * @param boolean $backward      Search backwards?
     *
     * @return boolean  Whether the token was found.
     */
    public function find($token, $term = null, $backward = false)
    {
        while ($this->valid()) {
            if ($this->matches($token, $term)) {
                return true;
            }
            if ($backward) {
                $this->previous();
            } else {
                $this->next();
            }
        }
        return false;
    }

    /**
     * Finds a certain construct, e.g. a function of a certain name.
     *
     * @param integer $token  Token to search for. One of the T_* token
     *                        constants.
     * @param string $name    Name to search for.
     *
     * @return boolean  Whether the token was found.
     * @throws \Horde\Refactor\Exception\UnexpectedToken
     */
    public function findConstruct($token, $name)
    {
        while ($this->find($token)) {
            $this->skipWhitespace();
            if (!$this->matches(T_STRING)) {
                throw new Exception\UnexpectedToken($this->current());
            }
            if ($this->current()[1] == $name) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the token positions of the current function.
     *
     * This is the complete function definition, including any leading doc
     * comments and whitespace, and up to the closing curly brace and trailing
     * newline.
     *
     * @return array  Tuple with the start and end position of the function
     *                tokens.
     */
    public function findFunctionTokens()
    {
        $whitelist = array(
            T_PUBLIC, T_PROTECTED, T_PRIVATE, T_ABSTRACT, T_FINAL, T_STATIC,
            T_DOC_COMMENT, T_COMMENT
        );
        $pos = $this->key();

        // Find the "function" keyword.
        if (!$this->find(T_FUNCTION, null, true)) {
            throw new Exception\NotFound(T_FUNCTION);
        }

        // Skip all function-related keywords and docs.
        $this->previous();
        while ($this->valid() &&
               ($this->matchesWhitespace() ||
                (is_array($this->current()) &&
                 in_array($this->current()[0], $whitelist)))) {
            $this->previous();
        }
        if ($this->valid()) {
            $this->next();
            $this->skipWhitespace();
        }
        $start = $this->key();

        // Find the function body.
        $this->seek($pos);
        if (!$this->find('{')) {
            throw new Exception\NotFound('{');
        }
        $this->findMatchingBracket();
        $this->skipWhitespace();
        $this->previous();

        return array($start, $this->key());
    }

    /**
     * Traverses the tokens to the next non-whitespace token.
     *
     * If the current token is not a whitespace token, it's skipped too.
     *
     * @param boolean $backward  Traverse backwards through the tokens.
     */
    public function skipWhitespace($backward = false)
    {
        if (!$this->matchesWhitespace()) {
            if ($backward) {
                $this->previous();
            } else {
                $this->next();
            }
        }
        while ($this->valid() && $this->matchesWhitespace()) {
            if ($backward) {
                $this->previous();
            } else {
                $this->next();
            }
        }
    }

    /**
     * Finds matching brackets.
     *
     * Supported are parentheses, curly braces, angle brackets, and square
     * brackets, both finding the closing to the opening and vice versa.
     *
     * @param string $bracket    The bracket to match against. Defaults to the
     *                           current token.
     * @param boolean $backward  Traverse backwards through the tokens.
     *
     * @throws \Horde\Refactor\Exception\UnexpectedToken if the current token
     *         is not a bracket and $bracket wasn't specified.
     * @throws \Horde\Refactor\Exception\NotFound if the matching backet wasn't
     *         found.
     */
    public function findMatchingBracket($bracket = null, $backward = false)
    {
        $matches = array(
            '(' => ')',
            ')' => '(',
            '{' => '}',
            '}' => '{',
            '<' => '>',
            '>' => '<',
            '[' => ']',
            ']' => '[',
        );

        if (!$bracket) {
            $bracket = $this->current();
            if (!is_string($bracket) || !isset($matches[$bracket])) {
                throw new Exception\UnexpectedToken($bracket);
            }
            if ($backward) {
                $this->previous();
            } else {
                $this->next();
            }
        }

        $level = 0;
        while ($this->valid()) {
            if ($this->current() === $matches[$bracket]) {
                if (!$level) {
                    break;
                }
                $level--;
            }
            if ($this->current() === $bracket ||
                ($bracket == '{' && is_array($this->current()) &&
                 ($this->current()[0] == T_CURLY_OPEN ||
                  $this->current()[0] == T_DOLLAR_OPEN_CURLY_BRACES ||
                  $this->current()[0] == T_STRING_VARNAME))) {
                $level++;
            }
            if ($backward) {
                $this->previous();
            } else {
                $this->next();
            }
        }
        if ($this->current() !== $matches[$bracket]) {
            throw new Exception\NotFound($matches[$bracket]);
        }
    }

    /**
     * Returns whether the current string token only contains whitespace.
     *
     * @return boolean  True if the current token is whitespace only.
     */
    public function matchesWhitespace()
    {
        if (is_array($this->current())) {
            return $this->current()[0] === T_WHITESPACE;
        }
        return preg_match('/^\s+$/', $this->current());
    }

    /**
     * Returns whether the current token matches a certain string or token.
     *
     * @param string|integer|\Horde\Refactor\Regexp $token
     *     Token to search for. One of the T_* token constants or a plain
     *     string. See token_get_all().
     * @param string|\Horde\Refactor\Regexp $term
     *     If $token is a token that can have individual content, the term to
     *     match the content against.
     *
     * @return boolean  Whether the token matched.
     */
    public function matches($token, $term = null)
    {
        if (!$this->valid()) {
            return false;
        }

        $current = $this->current();

        // Match against a token constant.
        if (is_int($token)) {
            if (!is_array($current) || $current[0] != $token) {
                return false;
            }
            if (is_null($term)) {
                return true;
            } elseif ($term instanceof Regexp) {
                return preg_match($term, $current);
            } else {
                return $current[1] == $term;
            }
            return false;
        }

        // Match against a string.
        if (!is_string($current)) {
            return false;
        }
        if ($token instanceof Regexp) {
            return preg_match($token, $current);
        }
        return $current == $token;
    }

    /**
     * Returns a slice of the tokens.
     *
     * @param integer $offset     Offset where to start the slice. See
     *                            array_slice() for details on this parameter.
     * @param integer $length     The number of tokens to return. See
     *                            array_slice() for details on this parameter.
     *
     * @return self  The requested slice.
     */
    public function slice($offset, $length = null)
    {
        return new self(array_slice($this->getArrayCopy(), $offset, $length));
    }

    /**
     * Removes a portion of the tokens and replaces it with something else.
     *
     * @param integer $offset     Offset where to start removing tokens.  See
     *                            array_splice() for details on this parameter.
     * @param integer $length     The number of tokens to remove. If null,
     *                            removes everything to the end. Use 0 if you
     *                            don't want to remove anything. See
     *                            array_splice() for details on this parameter.
     * @param array $replacement  Replace the removed tokens with these tokens.
     *
     * @return self  Contrary to array_splice(), this method doesn't return the
     *               extracted tokens, but a new iterator with the tokens
     *               replaced.
     */
    public function splice($offset, $length = null, $replacement = array())
    {
        if (is_null($length)) {
            $length = $this->count();
        }
        $copy = $this->getArrayCopy();
        array_splice($copy, $offset, $length, $replacement);
        return new self($copy);
    }

    /**
     * Returns the file code in its current state.
     *
     * @return string  The file code.
     */
    public function __toString()
    {
        $code = '';
        foreach ($this as $token) {
            if (is_array($token)) {
                $code .= $token[1];
            } else {
                $code .= $token;
            }
        }
        return $code;
    }
}

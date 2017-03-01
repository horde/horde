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

namespace Horde\Refactor\Rule;

use Horde\Refactor\Exception;
use Horde\Refactor\Rule;

/**
 * Refactors a PHP4 class file to have both the old-style and new-style
 * constructor method names, and have them in the correct order for both
 * backward and forward compatibility.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Refactor
 */
class Php4Constructor extends Rule
{
    /**
     * Applies the actual refactoring to the tokenized code.
     */
    public function run()
    {
        $this->_tokens->rewind();

        // Find all "class" tokens.
        while ($this->_tokens->find(T_CLASS)) {
            // Find the class name.
            $this->_tokens->skipWhitespace();
            if (!$this->_tokens->matches(T_STRING)) {
                throw new Exception\UnexpectedToken($this->_tokens->current());
            }
            $class = $this->_tokens->current()[1];
            $extends = null;
            while ($this->_tokens->valid()) {
                if ($this->_tokens->matches('{')) {
                    break;
                }
                if ($this->_tokens->matches(T_EXTENDS)) {
                    $this->_tokens->skipWhitespace();
                    if (!$this->_tokens->matches(T_STRING)) {
                        throw new Exception\UnexpectedToken($this->_tokens->current());
                    }
                    $extends = $this->_tokens->current()[1];
                    break;
                }
                $this->_tokens->next();
            }

            // Find PHP 4 constructor.
            $start = $this->_tokens->key();
            if (!$this->_tokens->findConstruct(T_FUNCTION, $class)) {
                continue;
            }
            $ctor4 = $this->_tokens->key();

            // Find PHP 5 constructor.
            $this->_tokens->seek($start);
            if ($this->_tokens->findConstruct(T_FUNCTION, '__construct')) {
                $ctor5 = $this->_tokens->key();
                if ($ctor5 < $ctor4) {
                    // First constructor is PHP5 style, nothing to refactor.
                    continue;
                }

                // Constructors need to be swapped.
                $this->_tokens->seek($ctor5);
                list($start, $end) = $this->_tokens->findFunctionTokens();
                $leadingWS = false;
                $this->_tokens->seek($start);
                $this->_tokens->previous();
                if (is_array($this->_tokens->current()) &&
                    $this->_tokens->current()[0] == T_WHITESPACE &&
                    $this->_tokens->current()[1][0] == "\n") {
                    $start--;
                    $leadingWS = true;
                }
                $function = $this->_tokens->slice($start, $end - $start + 1);

                // Do some juggling of whitespace to keep indentions and
                // vertical whitespace correct.
                $replacement = array();
                if ($leadingWS) {
                    $function->rewind();
                    $ws1 = $ws2 = $function->current();
                    $ws1[1] = "\n";
                    $replacement = array($ws1);

                    $ws2[1] = substr($ws2[1], 1);
                    $newlines = strspn($ws2[1], "\n", 1);
                    $leading = $trailing = $ws2;
                    $leading[1] = substr($leading[1], $newlines);
                    $trailing[1] = substr($trailing[1], 0, $newlines);
                    $function->append($trailing);
                    $function[0] = $leading;
                }
                $this->_tokens = $this->_tokens->splice(
                    $start, $end - $start + 1, $replacement
                );
                $this->_tokens->seek($ctor4);
                list($start, $end) = $this->_tokens->findFunctionTokens();
                $this->_tokens->seek($start);
                $this->_tokens->previous();
                if ($this->_tokens->matchesWhitespace()) {
                    $start--;
                }
                $this->_tokens = $this->_tokens->splice(
                    $start, 0, $function
                );
            } else {
                // Create new BC PHP 4 constructor.
                $this->_tokens->seek($ctor4);
                list($start, $end) = $this->_tokens->findFunctionTokens();
                $this->_tokens->seek($start);
                $this->_tokens->find('{');
                $this->_tokens->skipWhitespace();
                $function = $this->_tokens->slice(
                    $start, $this->_tokens->key() - $start
                );
                $function = $function->splice(0, 0, array("\n\n    "));
                $function->append('$this->__construct(');

                // Transfer function parameters.
                $this->_tokens->seek($ctor4);
                $this->_tokens->skipWhitespace();
                $this->_tokens->next();
                $afterComma = false;
                while ($this->_tokens->valid() &&
                       !$this->_tokens->matches(')')) {
                    $token = $this->_tokens->current();
                    if (is_array($token)) {
                        if ($token[0] == T_VARIABLE ||
                            $afterComma && $token[0] == T_WHITESPACE) {
                            $function->append($token);
                            $afterComma = false;
                        }
                    } elseif ($token == ',') {
                        $function->append($token);
                        $afterComma = true;
                    }
                    $this->_tokens->next();
                }
                $function->append(");\n    }");
                $this->_tokens = $this->_tokens->splice($end, 0, $function);

                // Rewrite original constructor to PHP 5.
                $this->_tokens->seek($ctor4);
                $this->_tokens[$this->_tokens->key()] = '__construct';
                while ($extends &&
                       $this->_tokens->valid() &&
                       $this->_tokens->key() < $end) {
                    $sequence = array(
                        array(T_STRING, 'parent'),
                        array(T_DOUBLE_COLON),
                        array(T_STRING, $extends),
                    );
                    if ($this->_tokens->matchesAll($sequence)) {
                        $this->_tokens[$this->_tokens->key() + 2] = '__construct';
                        break;
                    }
                    $this->_tokens->next();
                }
            }
        }
    }
}

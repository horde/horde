<?php
/**
 * This class provides a parser which can construct an SQL WHERE clause from a
 * Google-like search expression.
 *
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
 *
 * The expression recognizes boolean "AND", "OR", and "NOT" (providing no
 * operator between keywords implies "AND"), like so:
 *
 *   cat and dog
 *   cat or dog
 *   cat and not dog
 *
 * If no operator appears between keywords or quoted strings, "AND" is assumed.
 * A comma can be used instead of "OR":
 *
 *   cat dog
 *   cat, dog
 *   cat not dog
 *
 * The parser recognizes parentheses, so complex expressions can be created:
 *
 *   cat and not (dog or puppy)
 *
 * Quoted strings are also recognized, and are taken as literal keywords:
 *
 *   "cat and dog"
 *
 * Parsing is designed to be as fuzzy as possible, so it shouldn't error unless
 * people search for "AND", "OR", or "NOT" without quoting it or use unbalanced
 * parentheses.
 *
 * @author   Jason M. Felice <jason.m.felice@gmail.com>
 * @license  http://www.horde.org/licenses/bsd
 * @category Horde
 * @package  Db
 */
class Horde_Db_SearchParser
{
    /**
     * Parses a keyword expression.
     *
     * @param string $column  This is the SQL field name the resulting
     *                        expression should test against.
     * @param string $expr    This is the keyword expression we want to parse.
     *
     * @return string  The query expression.
     * @throws Horde_Db_Exception
     */
    static public function parse($column, $expr)
    {
        /* First pass - scan the string for tokens.  Bare words are tokens, or
         * the user can quote strings to have embedded spaces, keywords, or
         * parentheses.  Parentheses can be used for grouping boolean
         * operators, and the boolean operators AND, OR, and NOT are all
         * recognized.
         *
         * The tokens are returned in the $tokens array -- an array of strings.
         * Each string in the array starts with either a `!'  or a `='.  `=' is
         * a bare word or quoted string we are searching for, and `!' indicates
         * a boolean operator or parenthesis.  A token that starts with a '.'
         * indicates a PostgreSQL word boundary search. */
        $tokens = array();
        while (!empty($expr)) {
            $expr = preg_replace('/^\s+/', '', $expr);
            if (empty($expr)) {
                break;
            }
            if (substr($expr,0,1) == '(') {
                $expr = substr($expr, 1);
                $token = '!(';
            } elseif (substr($expr, 0, 1) == ')') {
                $expr = substr($expr, 1);
                $token = '!)';
            } elseif (substr($expr, 0, 1) == ',') {
                $expr = substr($expr, 1);
                $token = '!OR';
            } elseif (preg_match('/^(AND|OR|NOT)([^a-z].*)?$/i', $expr,
                                 $matches)) {
                $token = '!' . strtoupper($matches[1]);
                $expr = substr($expr, strlen($matches[1]));
            } elseif (preg_match('/^"(([^"]|\\[0-7]+|\\[Xx][0-9a-fA-F]+|\\[^Xx0-7])*)"/',
                                 $expr, $matches)) {
                $token = '=' . stripcslashes($matches[1]);
                $expr = substr($expr, strlen($matches[0]));
            } elseif (preg_match('/^[^\\s\\(\\),]+/', $expr, $matches)) {
                $token = '=' . $matches[0];
                $expr = substr($expr,strlen($token)-1);
            } else {
                throw new Horde_Db_Exception('Syntax error in search terms');
            }
            if ($token == '!AND') {
                /* !AND is implied by concatenation. */
                continue;
            }
            $tokens[] = $token;
        }

        /* Call the expression parser. */
        return self::_parseKeywords1($column, $tokens);
    }

    static protected function _parseKeywords1($column, &$tokens)
    {
        if (count($tokens) == 0) {
            throw new Horde_Db_Exception('Empty search terms');
        }
        $lhs = self::_parseKeywords2($column, $tokens);
        if (count($tokens) == 0 || $tokens[0] != '!OR') {
            return $lhs;
        }
        array_shift($tokens);
        $rhs = self::_parseKeywords1($column, $tokens);
        return "($lhs OR $rhs)";
    }

    static protected function _parseKeywords2($column, &$tokens)
    {
        $lhs = self::_parseKeywords3($column, $tokens);
        if (sizeof($tokens) == 0 || $tokens[0] == '!)' || $tokens[0] == '!OR') {
            return $lhs;
        }
        $rhs = self::_parseKeywords2($column, $tokens);
        return "($lhs AND $rhs)";
    }

    static protected function _parseKeywords3($column, &$tokens)
    {
        if ($tokens[0] == '!NOT') {
            array_shift($tokens);
            $lhs = self::_parseKeywords4($column, $tokens);
            if (is_a($lhs, 'PEAR_Error')) {
                return $lhs;
            }
            return "(NOT $lhs)";
        }
        return self::_parseKeywords4($column, $tokens);
    }

    static protected function _parseKeywords4($column, &$tokens)
    {
        if ($tokens[0] == '!(') {
            array_shift($tokens);
            $lhs = self::_parseKeywords1($column, $tokens);
            if (sizeof($tokens) == 0 || $tokens[0] != '!)') {
                throw new Horde_Db_Exception('Expected ")"');
            }
            array_shift($tokens);
            return $lhs;
        }

        if (substr($tokens[0], 0, 1) != '=' &&
            substr($tokens[0], 0, 2) != '=.') {
            throw new Horde_Db_Exception('Expected bare word or quoted search term');
        }

        $val = strtolower(substr(array_shift($tokens), 1));
        $val = addslashes(ereg_replace("([\\%])", "\\\\1", $val));

        return "(LOWER($column) LIKE '%$val%')";
    }
}

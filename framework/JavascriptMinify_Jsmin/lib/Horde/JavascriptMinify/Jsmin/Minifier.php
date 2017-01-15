<?php
/**
 * (c) 2002 Douglas Crockford <douglas@crockford.com> (jsmin.c)
 * (c) 2008 Ryan Grove <ryan@wonko.com> (PHP port)
 * Copyright 2009-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (JSMin).
 *
 * @category  Horde
 * @copyright 2002 Douglas Crockford
 * @copyright 2008 Ryan Grove
 * @copyright 2009-2017 Horde LLC
 * @license   JSMin
 * @package   JavascriptMinify_Jsmin
 */

/**
 * PHP implementation of Douglas Crockford's JSMin.
 *
 * See LICENSE for original JSMin license.
 *
 * Original PHP implementation:
 * Version: 1.1.1 (2008-03-02)
 * (c) 2008 Ryan Grove <ryan@wonko.com> (PHP port)
 * URL: http://code.google.com/p/jsmin-php/
 *
 * Additional cleanups/code by the Horde Project.
 *
 * @author    Douglas Crockford <douglas@crockford.com>
 * @author    Ryan Grove <ryan@wonko.com>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002 Douglas Crockford
 * @copyright 2008 Ryan Grove
 * @copyright 2009-2017 Horde LLC
 * @license   JSMin
 * @package   JavascriptMinify_Jsmin
 */
class Horde_JavascriptMinify_Jsmin_Minifier
{
    /* Constants. */
    const ORD_LF = 10;
    const ORD_SPACE = 32;
    const ACTION_KEEP_A = 1;
    const ACTION_DELETE_A = 2;
    const ACTION_DELETE_A_B = 3;

    /* Member variables. */
    protected $_a = "\n";
    protected $_b = '';
    protected $_input;
    protected $_inputIndex = 0;
    protected $_inputLength;
    protected $_keywords = array(
        'case',
        'else',
        'in',
        'return',
        'typeof'
    );
    protected $_lookAhead = null;
    protected $_output = '';
    protected $_x = null;
    protected $_y = null;

    public function __construct($input)
    {
        $this->_input = str_replace("\r\n", "\n", $input);
        $this->_inputLength = strlen($this->_input);
    }

    public function minify()
    {
        if ($this->_peek() == 0xef) {
            $this->_get();
            $this->_get();
            $this->_get();
        }

        $this->_a = "\n";
        $this->_action(self::ACTION_DELETE_A_B);

        while (!is_null($this->_a)) {
            $cmd = self::ACTION_KEEP_A;
            switch ($this->_a) {
            case ' ':
                if (!$this->_isAlphaNum($this->_b)) {
                    $cmd = self::ACTION_DELETE_A;
                }
                break;

            case "\n":
                if ($this->_b === ' ') {
                    $cmd = self::ACTION_DELETE_A_B;
                } elseif (!strspn($this->_b, '{[(+-!~') &&
                          !$this->_isAlphaNum($this->_b)) {
                    $cmd = self::ACTION_DELETE_A;
                }
                break;

            default:
                if (!$this->_isAlphaNum($this->_a) &&
                    (($this->_b === ' ') ||
                     (($this->_b === "\n" && !strspn($this->_a, '}])+-"\'`'))))) {
                    $cmd = self::ACTION_DELETE_A_B;
                }
                break;
            }

            $this->_action($cmd);
        }

        return trim($this->_output);
    }

    protected function _action($d)
    {
        switch ($d) {
        case self::ACTION_KEEP_A:
            $this->_output .= $this->_a;
            if (strspn($this->_y, "\n ") &&
                strspn($this->_a, '+-*/') &&
                strspn($this->_b, '+-*/')) {
                $this->_output .= $this->_y;
            }

        case self::ACTION_DELETE_A:
            $this->_a = $this->_b;

            if (strspn($this->_a, '\'"`')) {
                while (true) {
                    $this->_output .= $this->_a;
                    $this->_a = $this->_get();

                    if ($this->_a === $this->_b) {
                        break;
                    }

                    if ($this->_a === '\\') {
                        $this->_output .= $this->_a;
                        $this->_a = $this->_get();
                    }

                    if (is_null($this->_a)) {
                        throw new Exception('Unterminated string literal.');
                    }
                }
            }

        case self::ACTION_DELETE_A_B:
            $oldindex = $this->_inputIndex;
            $this->_b = $this->_next();

            if (($this->_b === '/') && $this->_isRegexLiteral($oldindex)) {
                $this->_output .= $this->_a;
                if (strspn($this->_a, '/*')) {
                    $this->_output .= ' ';
                }
                $this->_output .= $this->_b;

                while (true) {
                    $this->_a = $this->_get();

                    switch ($this->_a) {
                    case '[':
                        /* Inside a regex [...] set, which MAY contain a
                         * '/' itself. */
                        while (true) {
                            $this->_output .= $this->_a;
                            $this->_a = $this->_get();

                            if ($this->_a === ']') {
                                break;
                            } elseif ($this->_a === '\\') {
                                $this->_output .= $this->_a;
                                $this->_a = $this->_get();
                            } elseif (is_null($this->_a)) {
                                throw new Exception('Unterminated regular expression set in regex literal.');
                            }
                        }
                        break;

                    case '/':
                        switch ($this->_peek()) {
                        case '/':
                        case '*':
                            throw new Exception('Unterminated set in regular Expression literal.');
                        }
                        break 2;

                    case '\\':
                        $this->_output .= $this->_a;
                        $this->_a = $this->_get();
                        break;
                    }

                    if (is_null($this->_a)) {
                        throw new Exception('Unterminated regular expression literal.');
                    }

                    $this->_output .= $this->_a;
                }

                $this->_b = $this->_next();
            }
        }
    }

    protected function _get()
    {
        $c = $this->_lookAhead;
        $this->_lookAhead = null;

        if (is_null($c) &&
            ($this->_inputIndex < $this->_inputLength)) {
            $c = $this->_input[$this->_inputIndex++];
        }

        if (is_null($c) || ($c === "\n") || (ord($c) >= self::ORD_SPACE)) {
            return $c;
        }

        if ($c === "\r") {
            return "\n";
        }

        return ' ';
    }

    protected function _isAlphaNum($c)
    {
        $c_ord = ord($c);

        return (($c_ord > 126) ||
                strspn($c, '_$\\') ||
                ($c_ord >= 97 && $c_ord <= 122) ||
                ($c_ord >= 48 && $c_ord <= 57) ||
                ($c_ord >= 65 && $c_ord <= 90));
    }

    protected function _isRegexLiteral($oldindex)
    {
        /* We aren't dividing. */
        if (strspn($this->_a, "(,=:[!&|?+-~*/{;")) {
            return true;
        }

        $curr = $oldindex;
        while (--$curr >= 0 && $this->_isAlphaNum($this->_input[$curr])) {}

        return in_array(
            substr($this->_input, $curr + 1, $oldindex - $curr - 1),
            $this->_keywords
        );
    }

    protected function _next()
    {
        $c = $this->_get();

        if ($c === '/') {
            switch ($this->_peek()) {
            case '/':
                while (true) {
                    $c = $this->_get();
                    if (ord($c) <= self::ORD_LF) {
                        break;
                    }
                }
                break;

            case '*':
                $this->_get();

                while ($c != ' ') {
                    switch ($this->_get()) {
                    case '*':
                        if ($this->_peek() === '/') {
                            $this->_get();
                            $c = ' ';
                        }
                        break;

                    case null:
                        throw new Exception('Unterminated comment.');
                    }
                }
                break;
            }
        }

        $this->_y = $this->_x;
        $this->_x = $c;

        return $c;
    }

    protected function _peek()
    {
        $this->_lookAhead = $this->_get();
        return $this->_lookAhead;
    }

}

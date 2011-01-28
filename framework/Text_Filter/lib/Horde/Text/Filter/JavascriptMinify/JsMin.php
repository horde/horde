<?php
/**
 * PHP implementation of Douglas Crockford's JSMin.
 *
 * License/copyright from original jsmin.php library:
 *
 * --
 * Copyright (c) 2002 Douglas Crockford  (www.crockford.com)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all
 * copies or substantial portions of the Software.
 *
 * The Software shall be used for Good, not Evil.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE
 * SOFTWARE.
 *
 * Author: Ryan Grove <ryan@wonko.com>
 * (c) 2002 Douglas Crockford <douglas@crockford.com> (jsmin.c)
 * (c) 2008 Ryan Grove <ryan@wonko.com> (PHP port)
 * Version: 1.1.1 (2008-03-02)
 * URL: http://code.google.com/p/jsmin-php/
 * --
 *
 * Additional cleanups/code by the Horde Project.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Text_Filter
 */
class Horde_Text_Filter_JavascriptMinify_JsMin
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
    protected $_lookAhead = null;
    protected $_output = '';

    public function __construct($input)
    {
        $this->_input = str_replace("\r\n", "\n", $input);
        $this->_inputLength = strlen($this->_input);
    }

    public function minify()
    {
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
                } elseif (!strspn($this->_b, '{[(+-') &&
                          !$this->_isAlphaNum($this->_b)) {
                    $cmd = self::ACTION_DELETE_A;
                }
                break;

            default:
                if (!$this->_isAlphaNum($this->_a) &&
                    (($this->_b === ' ') ||
                     (($this->_b === "\n" && !strspn($this->_a, '}])+-"\''))))) {
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
        switch($d) {
        case self::ACTION_KEEP_A:
            $this->_output .= $this->_a;

        case self::ACTION_DELETE_A:
            $this->_a = $this->_b;

            if ($this->_a === '\'' || $this->_a === '"') {
                while (true) {
                    $this->_output .= $this->_a;
                    $this->_a = $this->_get();

                    if ($this->_a === $this->_b) {
                        break;
                    }

                    if (ord($this->_a) <= self::ORD_LF) {
                        throw new Exception('Unterminated string literal.');
                    }

                    if ($this->_a === '\\') {
                        $this->_output .= $this->_a;
                        $this->_a = $this->_get();
                    }
                }
            }

        case self::ACTION_DELETE_A_B:
            $this->_b = $this->_next();

            if ($this->_b === '/' && strspn($this->_a, '(,=:[!&|?')) {
                $this->_output .= $this->_a . $this->_b;

                while (true) {
                    $this->_a = $this->_get();

                    if ($this->_a === '/') {
                        break;
                    }

                    if ($this->_a === '\\') {
                        $this->_output .= $this->_a;
                        $this->_a = $this->_get();
                    } elseif (ord($this->_a) <= self::ORD_LF) {
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
            $c = $this->_input[$this->_inputIndex];
            $this->_inputIndex += 1;
        }

        if ($c === "\r") {
            return "\n";
        }

        if (is_null($c) || ($c === "\n") || (ord($c) >= self::ORD_SPACE)) {
            return $c;
        }

        return ' ';
    }

    protected function _isAlphaNum($c)
    {
        return (ord($c) > 126 || preg_match('/^[0-9a-zA-Z_\\$\\\\]$/', $c));
    }

    protected function _next()
    {

        $c = $this->_get();

        if ($c !== '/') {
            return $c;
        }

        switch ($this->_peek()) {
        case '/':
            $comment = '';

            while (true) {
                $c = $this->_get();
                $comment .= $c;
                if (ord($c) <= self::ORD_LF) {
                    // IE conditional comment
                    if (preg_match('/^\\/@(?:cc_on|if|elif|else|end)\\b/', $comment)) {
                        return '/' . $comment;
                    }

                    return $c;
                }
            }

        case '*':
            $comment = '';
            $this->_get();

            while (true) {
                $get = $this->_get();
                switch ($get) {
                case '*':
                    if ($this->_peek() === '/') {
                        $this->_get();

                        // IE conditional comment
                        if (preg_match('/^@(?:cc_on|if|elif|else|end)\\b/', $comment)) {
                            return '/*' . $comment . '*/';
                        }

                        return ' ';
                    }
                    break;

                case null:
                    throw new Exception('Unterminated comment.');
                }

                $comment .= $get;
            }
        }

        return $c;
    }

    protected function _peek()
    {
        $this->_lookAhead = $this->_get();
        return $this->_lookAhead;
    }

}

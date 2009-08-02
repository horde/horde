<?php
/**
 * PHP implementation of Douglas Crockford's JSMin.
 *
 * See Horde_Text_Filter_JavscriptMinify for license information.
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
                } elseif (!$this->_isAlphaNum($this->_b)) {
                    $cmd = self::ACTION_DELETE_A;
                }
                break;

            default:
                if (!$this->_isAlphaNum($this->_a) &&
                    (($this->_b === ' ') ||
                     (($this->_b === "\n" && !strspn($this->_b, '}])+-"\''))))) {
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
            while (true) {
                $c = $this->_get();
                if (ord($c) <= self::ORD_LF) {
                    return $c;
                }
            }

        case '*':
            $this->_get();

            while (true) {
                switch($this->_get()) {
                case '*':
                    if ($this->_peek() === '/') {
                        $this->_get();
                        return ' ';
                    }
                    break;

                case null:
                    throw new Exception('Unterminated comment.');
                }
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

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

    /* Member variables. */
    protected $_a = '';
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
        $this->_a = "\n";
        $this->_action(3);

        while (!is_null($this->_a)) {
            switch ($this->_a) {
            case ' ':
                $this->_action($this->_isAlphaNum($this->_b) ? 1 : 2);
                break;

            case "\n":
                switch ($this->_b) {
                case '{':
                case '[':
                case '(':
                case '+':
                case '-':
                    $this->_action(1);
                    break;

                case ' ':
                    $this->_action(3);
                    break;

                default:
                    $this->_action($this->_isAlphaNum($this->_b) ? 1 : 2);
                    break;
                }
                break;

            default:
                switch ($this->_b) {
                case ' ':
                    $this->_action($this->_isAlphaNum($this->_a) ? 1 : 3);
                    break;

                case "\n":
                    switch ($this->_a) {
                    case '}':
                    case ']':
                    case ')':
                    case '+':
                    case '-':
                    case '"':
                    case "'":
                        $this->_action(1);
                        break;

                    default:
                        $this->_action($this->_isAlphaNum($this->_a) ? 1 : 3);
                        break;
                    }
                    break;

                default:
                    $this->_action(1);
                    break;
                }
            }
        }

        return $this->_output;
    }

    protected function _action($d)
    {
        switch($d) {
        case 1:
            $this->_output .= $this->_a;

        case 2:
            $this->_a = $this->_b;

            if ($this->_a === '\'' || $this->_a === '"') {
                for (;;) {
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

        case 3:
            $this->_b = $this->_next();

            if ($this->_b === '/' && strspn($this->_a, '(,=:[!&|?')) {
                $this->_output .= $this->_a . $this->_b;

                for (;;) {
                    $this->_a = $this->_get();

                    if ($this->_a === '/') {
                        break;
                    } elseif ($this->_a === '\\') {
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
        return (ord($c) > 126 || ($c === '\\') || (preg_match('/^[\w\$]$/', $c) === 1));
    }

    protected function _next()
    {
        $c = $this->_get();

        if ($c !== '/') {
            return $c;
        }

        switch ($this->_peek()) {
        case '/':
            for (;;) {
                $c = $this->_get();
                if (ord($c) <= self::ORD_LF) {
                    return $c;
                }
            }

        case '*':
            $this->_get();

            for (;;) {
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

<?php
/**
 * This package is heavily inspired by the Spyc PHP YAML implementation
 * (http://spyc.sourceforge.net/), and portions are copyright 2005-2006 Chris
 * Wanstrath.
 *
 * @author   Chris Wanstrath <chris@ozmm.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Mike Naberezny <mike@maintainable.com>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Yaml
 */

/**
 * Dump PHP data structures to YAML.
 *
 * @author   Chris Wanstrath <chris@ozmm.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Mike Naberezny <mike@maintainable.com>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Yaml
 */
class Horde_Yaml_Dumper
{
    protected $_options = array();

    /**
     * Dumps PHP array to YAML.
     *
     * The dump method, when supplied with an array, will do its best
     * to convert the array into valid YAML.
     *
     * Options:
     *    `indent`:
     *       number of spaces to indent children (default 2)
     *    `wordwrap`:
     *       wordwrap column number (default 40)
     *
     * @param array|Traversable $array  PHP array or traversable object.
     * @param integer $options          Options for dumping.
     *
     * @return string  YAML representation of $value.
     */
    public function dump($value, $options = array())
    {
        // validate & merge default options
        if (!is_array($options)) {
            throw new InvalidArgumentException('Options must be an array');
        }

        $this->_options = array_merge(
            array('indent' => 2, 'wordwrap' => 40),
            $options
        );

        if (!is_int($this->_options['indent'])) {
            throw new InvalidArgumentException('Indent must be an integer');
        }

        if (!is_int($this->_options['wordwrap'])) {
            throw new InvalidArgumentException('Wordwrap column must be an integer');
        }

        // new YAML document
        $dump = "---\n";

        // iterate through array and yamlize it
        $dump .= $this->_yamlizeArray($value, 0);

        return $dump;
    }

    /**
     * Attempts to convert a key/value array item to YAML.
     *
     * @param string $key          The name of the key.
     * @param string|array $value  The value of the item.
     * @param integer $indent      The indent of the current node.
     * @param boolean $sequence    Is this an entry of a sequence?
     *
     * @return string
     */
    protected function _yamlize($key, $value, $indent, $sequence = false)
    {
        if ($value instanceof Serializable) {
            // Dump serializable objects as !php/object::classname
            // serialize_data
            $data = '!php/object::' . get_class($value)
                . ' ' . $value->serialize();
            $string = $this->_dumpNode($key, $data, $indent, $sequence);
        } elseif (is_array($value) || $value instanceof Traversable) {
            // It has children.  Make it the right kind of item.
            $string = $this->_dumpNode($key, null, $indent, $sequence);

            // Add the indent.
            $indent += $this->_options['indent'];

            // Yamlize the array.
            $string .= $this->_yamlizeArray($value, $indent);
        } elseif (!is_array($value)) {
            // No children.
            $string = $this->_dumpNode($key, $value, $indent, $sequence);
        }

        return $string;
    }

    /**
     * Attempts to convert an array to YAML
     *
     * @param array $array     The array you want to convert.
     * @param integer $indent  The indent of the current level.
     *
     * @return string
     */
    protected function _yamlizeArray($array, $indent)
    {
        if ($array instanceof Traversable) {
            $array = iterator_to_array($array);
        } elseif (!is_array($array)) {
            return false;
        }

        $sequence = array_keys($array) === range(0, count($array) - 1);

        $string = '';
        foreach ($array as $key => $value) {
            $string .= $this->_yamlize($key, $value, $indent, $sequence);
        }
        return $string;
    }

    /**
     * Returns YAML from a key and a value.
     *
     * @param string $key        The name of the key.
     * @param string $value      The value of the item.
     * @param integer $indent    The indent of the current node.
     * @param boolean $sequence  Is this an entry of a sequence?
     *
     * @return string
     */
    protected function _dumpNode($key, $value, $indent, $sequence = false)
    {
        $literal = false;
        // Do some folding here, for blocks.
        if (strpos($value, "\n") !== false ||
            strpos($value, ': ') !== false ||
            strpos($value, '- ') !== false) {
            $value = $this->_doLiteralBlock($value, $indent);
            $literal = true;
        } else {
            $value = $this->_fold($value, $indent);
        }

        if (is_bool($value)) {
            $value = ($value) ? 'true' : 'false';
        } elseif (is_float($value)) {
            if (is_nan($value)) {
                $value = '.NAN';
            } elseif ($value === INF) {
                $value = '.INF';
            } elseif ($value === -INF) {
                $value = '-.INF';
            }
        }

        $spaces = str_repeat(' ', $indent);

        // Quote strings if necessary, and not folded
        if (!$literal &&
            strpos($value, "\n") === false &&
            strchr($value, '#')) {
            $value = "'{$value}'";
        }

        if ($sequence) {
            // It's a sequence.
            $string = $spaces . '- ' . $value . "\n";
        } else {
            // It's mapped.
            $string = $spaces . $key . ': ' . $value . "\n";
        }

        return $string;
    }

    /**
     * Creates a literal block for dumping.
     *
     * @param string $value
     * @param integer $indent  The value of the indent.
     *
     * @return string
     */
    protected function _doLiteralBlock($value, $indent)
    {
        $exploded = explode("\n", $value);
        $newValue = '|';
        $indent += $this->_options['indent'];
        $spaces = str_repeat(' ', $indent);
        foreach ($exploded as $line) {
            $newValue .= "\n" . $spaces . trim($line);
        }
        return $newValue;
    }

    /**
     * Folds a string of text, if necessary.
     *
     * @param $value The string you wish to fold.
     *
     * @return string
     */
    protected function _fold($value, $indent)
    {
        // Don't do anything if wordwrap is set to 0
        if (!$this->_options['wordwrap']) {
            return $value;
        }

        if (strlen($value) > $this->_options['wordwrap']) {
            $indent += $this->_options['indent'];
            $indent = str_repeat(' ', $indent);
            $wrapped = wordwrap($value, $this->_options['wordwrap'], "\n$indent");
            $value = ">\n" . $indent . $wrapped;
        }

        return $value;
    }
}

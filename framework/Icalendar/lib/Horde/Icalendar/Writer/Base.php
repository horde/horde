<?php
/**
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Icalendar
 */

/**
 * This is the base class for any writers that export Horde_Icalendar objects
 * into strings.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Icalendar
 */
abstract class Horde_Icalendar_Writer_Base
{
    /**
     * A hash that maps property names of Horde_Icalendar objects to property
     * names defined in RFCs.
     *
     * @var array
     */
    protected $_propertyMap = array();

    /**
     * A buffer for the generated output.
     *
     * @var string
     */
    protected $_output = '';

    /**
     * A buffer to collect content for a single propery line.
     *
     * @var string
     */
    protected $_lineBuffer = '';

    /**
     * Reference to the property currently being written.
     *
     * @var array
     */
    protected $_property;

    /**
     * Number of the property value currently being written, for properties
     * that allow multiple values.
     *
     * @var integer
     */
    protected $_num;

    /**
     * Exports a complete Horde_Icalendar object into a string.
     *
     * @param Horde_Icalendar_Base $object  An object to export.
     *
     * @return string  The string representation of the object.
     */
    public function export(Horde_Icalendar_Base $object)
    {
        $this->_output = '';
        $this->_exportComponent($object);
        return $this->_output;
    }

    /**
     * Converts an 8bit string to a quoted-printable string according to RFC
     * 2045 Section 6.7.
     *
     * imap_8bit() and Horde_Mime::quotedPrintableEncode don't apply all
     * necessary rules.
     *
     * @param string $input  The string to be encoded.
     *
     * @return string  The quoted-printable encoded string.
     */
    public function quotedPrintableEncode($input = '')
    {
        $output = $line = '';

        for ($i = 0, $len = strlen($input); $i < $len; ++$i) {
            $ord = ord($input[$i]);
            // Encode non-printable characters (rule 2).
            if ($ord == 9 ||
                ($ord >= 32 && $ord <= 60) ||
                ($ord >= 62 && $ord <= 126)) {
                $chunk = $input[$i];
            } else {
                // Quoted printable encoding (rule 1).
                $chunk = '=' . Horde_String::upper(sprintf('%02X', $ord));
            }
            $line .= $chunk;
            // Wrap long lines (rule 5)
            if (strlen($line) + 1 > 76) {
                $line = Horde_String::wordwrap($line, 75, "=\r\n", true, 'us-ascii', true);
                $newline = strrchr($line, "\r\n");
                if ($newline !== false) {
                    $output .= substr($line, 0, -strlen($newline) + 2);
                    $line = substr($newline, 2);
                } else {
                    $output .= $line;
                }
                continue;
            }
            // Wrap at line breaks for better readability (rule 4).
            if (substr($line, -3) == '=0A') {
                $output .= $line . "=\r\n";
                $line = '';
            }
        }
        $output .= $line;

        // Trailing whitespace must be encoded (rule 3).
        $lastpos = strlen($output) - 1;
        if ($output[$lastpos] == chr(9) ||
            $output[$lastpos] == chr(32)) {
            $output[$lastpos] = '=';
            $output .= Horde_String::upper(sprintf('%02X', ord($output[$lastpos])));
        }

        return $output;
    }

    /**
     * Exports an individual component into a string.
     *
     * @param Horde_Icalendar_Base $object  An component to export.
     */
    protected function _exportComponent(Horde_Icalendar_Base $object)
    {
        $basename = Horde_String::upper(str_replace('Horde_Icalendar_', '', get_class($object)));
        $this->_output .= 'BEGIN:' . $basename . "\r\n";
        foreach ($object as $name => $property) {
            $this->_exportProperty($name, $property);
        }
        foreach ($object->components as $component) {
            $this->_exportComponent($component);
        }
        $this->_output .= 'END:' . $basename . "\r\n";
    }

    /**
     * Exports an individual property into a string.
     *
     * @param string $name     A property name.
     * @param array $property  A property hash.
     */
    protected function _exportProperty($name, array $property)
    {
        if (!isset($property['values'])) {
            return;
        }
        if (isset($this->_propertyMap[$name])) {
            $name = $this->_propertyMap[$name];
            if (!strlen($name)) {
                return;
            }
        }

        $this->_property = $property;
        foreach ($this->_property['values'] as $num => $value) {
            $this->_num = $num;
            // Prepare property first, because preparation might change
            // property parameters.
            $value = $this->_prepareProperty($value);
            $this->_lineBuffer = Horde_String::upper($name);
            foreach ($this->_property['params'][$num] as $parameter => $pvalue) {
                $this->_exportParameter($parameter, $pvalue);
            }
            $this->_addToLineBuffer(':' . $value);
            $this->_output .= $this->_lineBuffer . "\r\n";
        }
    }

    /**
     * Exports an individual parameter into a string.
     *
     * @param string $name  A parameter name.
     * @param mixed $value  A parameter value.
     */
    protected function _exportParameter($name, $value)
    {
        $this->_addToLineBuffer(';' . Horde_String::upper($name));
        $value = $this->_prepareParameter($value);
        $this->_addToLineBuffer('=' . $value);
    }

    /**
     * Prepares a property value.
     *
     * Sub-classes can extend this the method to apply specific escaping.
     *
     * @param mixed $value     A property value.
     *
     * @return string  The prepared value.
     * @throws Horde_Icalendar_Exception if the value contains invalid
     *                                   characters.
     */
    protected function _prepareProperty($value)
    {
        return (string)$value;
    }

    /**
     * Prepares a parameter value.
     *
     * Sub-classes can extend this the method to apply specific escaping.
     *
     * @param string $value  A parameter value.
     *
     * @return string  The prepared value.
     * @throws Horde_Icalendar_Exception if the value contains invalid
     *                                   characters.
     */
    protected function _prepareParameter($value)
    {
        return $value;
    }

    /**
     * Adds some content to the internal line buffer.
     *
     * Sub-classes can extend this method to apply line-folding techniques.
     *
     * @param string $content  Content to add to the line buffer.
     */
    protected function _addToLineBuffer($content)
    {
        $this->_lineBuffer .= $content;
    }
}

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
 * A writer class for exporting iCalendar 2.0 (RFC 2445) data.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Icalendar
 */
class Horde_Icalendar_Writer_Vcalendar_20 extends Horde_Icalendar_Writer_Base
{
    /**
     * A hash that maps property names of Horde_Icalendar objects to property
     * names defined in RFCs.
     *
     * @var array
     */
    protected $_propertyMap = array('product' => 'PRODID',
                                    'start' => 'DTSTART',
                                    'stamp' => 'DTSTAMP');

    /**
     * Prepares a property value.
     *
     * @param mixed $value  A property value.
     * @param array $params    Property parameters.
     * @param array $property  A complete property hash.
     *
     * @return string  The prepared value.
     * @throws Horde_Icalendar_Exception if the value contains invalid
     *                                   characters.
     */
    protected function _prepareProperty($value, array $params, array $property)
    {
        if ($value instanceof Horde_Date) {
            if (isset($params['value']) && $params['value'] == 'date') {
                return $value->format('Ymd');
            }
            return $value->timezone == 'UTC'
                ? $value->format('Ymd\THis\Z')
                : $value->format('Ymd\THis');
        }
        if (isset($property['type']) && $property['type'] == 'string') {
            // TEXT value as of Section 4.3.11.
            return preg_replace('/(\r?\n|;|,|\\\\)/', '\\\\$1', $value);
        }
        return parent::_prepareProperty($value, $params);
    }

    /**
     * Escapes a parameter value according to Section 4.1.
     *
     * @param string $value  A parameter value.
     *
     * @return string  The escaped (if necessary) value.
     * @throws Horde_Icalendar_Exception if the value contains invalid
     *                                   characters.
     */
    protected function _prepareParameter($value)
    {
        if (preg_match('/[\x00-\x08\x0a-\x1f\x7f"]/', $value)) {
            // Not a QSAFE-CHAR.
            throw new Horde_Icalendar_Exception('Invalid parameter value');
        }
        if (preg_match('/[;:,]/', $value)) {
            // Not a SAFE-CHAR.
            $value = '"' . $value . '"';
        }
        return $value;
    }

    /**
     * Adds some content to the internal line buffer.
     *
     * Applies line-folding techniques as per RFC 2445 Section 4.1.
     *
     * @param string $content  Content to add to the line buffer.
     */
    protected function _addToLineBuffer($content)
    {
        $bufferLen = strlen($this->_lineBuffer);
        $contentLen = strlen($content);
        if ($bufferLen + $contentLen < 76) {
            $this->_lineBuffer .= $content;
            return;
        }
        while ($contentLen) {
            $char = Horde_String::substr($content, 0, 1);
            $charLen = strlen($char);
            if ($bufferLen + $charLen > 75) {
                // Wrap
                $this->_output .= $this->_lineBuffer . "\r\n ";
                $this->_lineBuffer = '';
                $bufferLen = 0;
            }
            $this->_lineBuffer .= $char;
            $bufferLen += $charLen;
            $contentLen -= $charLen;
            $content = substr($content, $charLen);
        }
    }
}

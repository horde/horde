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
     *
     * @return string  The prepared value.
     * @throws Horde_Icalendar_Exception if the value contains invalid
     *                                   characters.
     */
    protected function _prepareProperty($value)
    {
        if ($value instanceof Horde_Date) {
            $params = $this->_property['params'][$this->_num];
            if (isset($params['value']) && Horde_String::upper($params['value']) == 'DATE') {
                return $value->format('Ymd');
            }
            return $value->timezone == 'UTC'
                ? $value->format('Ymd\THis\Z')
                : $value->format('Ymd\THis');
        }
        if (isset($this->_property['type']) &&
            $this->_property['type'] == 'string') {
            // TEXT value as of Section 4.3.11.
            return preg_replace(array('/[;,\\\\]/', '/\r?\n/'),
                                array('\\\\$0', '\n'),
                                $value);
        }
        return parent::_prepareProperty($value);
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
        // Strip not QSAFE-CHARs.
        $value = preg_replace('/[\x00-\x08\x0a-\x1f\x7f"]/', '', $value);
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
            $char = Horde_String::substr($content, 0, 1, 'UTF-8');
            $charLen = strlen($char);
            if ($bufferLen + $charLen > 75) {
                // Try wrapping at last space.
                $pos = strrpos($this->_lineBuffer, ' ');
                if ($pos !== false) {
                    $this->_output .= substr($this->_lineBuffer, 0, $pos) . "\r\n ";
                    $this->_lineBuffer = substr($this->_lineBuffer, $pos);
                    $bufferLen = strlen($this->_lineBuffer);
                } else {
                    $this->_output .= $this->_lineBuffer . "\r\n ";
                    $this->_lineBuffer = '';
                    $bufferLen = 0;
                }
            }
            $this->_lineBuffer .= $char;
            $bufferLen += $charLen;
            $contentLen -= $charLen;
            $content = substr($content, $charLen);
        }
    }
}

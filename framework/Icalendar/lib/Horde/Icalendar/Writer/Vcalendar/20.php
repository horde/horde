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

    /**
     * Converts a property value of an object to a string
     *
     * @param object $property  A property value.
     * @param array $params     Property parameters.
     *
     * @return string  The string representation of the object.
     */
    protected function _objectToString($property, array $params)
    {
        if ($property instanceof Horde_Date) {
            if (isset($params['value']) && $params['value'] == 'date') {
                return $property->format('Ymd');
            }
            return $property->format('Ymd\THis\Z');
        }
        return parent::_objectToString($property, $params);
    }
}

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
 * A writer class for exporting vCalendar 1.0 data.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Icalendar
 */
class Horde_Icalendar_Writer_Vcalendar_10 extends Horde_Icalendar_Writer_Base
{
    /**
     * A hash that maps property names of Horde_Icalendar objects to property
     * names defined in RFCs.
     *
     * @var array
     */
    protected $_propertyMap = array('product' => 'PRODID',
                                    'start' => 'DTSTART',
                                    'stamp' => '');

    /**
     * Prepares a property value.
     *
     * @param mixed $value     A property value.
     *
     * @return string  The prepared value.
     * @throws Horde_Icalendar_Exception if the value contains invalid
     *                                   characters.
     */
    protected function _prepareProperty($value)
    {
        $params = &$this->_property['params'][$this->_num];
        if ($value instanceof Horde_Date) {
            if (isset($params['value']) &&
                Horde_String::upper($params['value']) == 'DATE') {
                return $value->format('Ymd');
            }
            return $value->timezone == 'UTC'
                ? $value->format('Ymd\THis\Z')
                : $value->format('Ymd\THis');
        }
        if (isset($this->_property['type']) &&
            $this->_property['type'] == 'string') {
            // Encode text with line breaks.
            if (strpos("\n", $value) !== false ||
                strpos("\r", $value) !== false ||
                Horde_Mime::is8bit($value)) {
                $params['encoding'] = 'QUOTED-PRINTABLE';
                if (!isset($params['charset'])) {
                    $params['charset'] = 'UTF-8';
                }
                $this->_property['nowrap'][$this->_num] = true;
                return $this->quotedPrintableEncode($value);
            }
        }
        return parent::_prepareProperty($value);
    }

    /**
     * Escapes a parameter value.
     *
     * @param string $value  A parameter value.
     *
     * @return string  The escaped (if necessary) value.
     * @throws Horde_Icalendar_Exception if the value contains invalid
     *                                   characters.
     */
    protected function _prepareParameter($value)
    {
        return str_replace(';', '\;', $value);
    }

    /**
     * Adds some content to the internal line buffer.
     *
     * Applies RFC 822 line-folding techniques.
     *
     * @param string $content  Content to add to the line buffer.
     */
    protected function _addToLineBuffer($content)
    {
        if (!empty($this->_property['nowrap'][$this->_num])) {
            $this->_output .= $this->_lineBuffer . $content;
            $this->_lineBuffer = '';
            return;
        }

        $this->_lineBuffer .= $content;
        $bufferLen = strlen($this->_lineBuffer);
        $contentLen = strlen($content);
        if ($bufferLen > 75) {
            $this->_lineBuffer = Horde_String::wordwrap($this->_lineBuffer, 75, "\r\n", false, 'utf-8', true);
            $pos = strrpos($this->_lineBuffer, "\n");
            if ($pos !== false) {
                $pos++;
                $this->_output .= substr($this->_lineBuffer, 0, $pos);
                $this->_lineBuffer = substr($this->_lineBuffer, $pos);
            }
        }
    }
}

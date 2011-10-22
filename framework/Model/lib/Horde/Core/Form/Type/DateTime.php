<?php
/**
 * Date and time selection
 */
class Horde_Form_Type_DateTime extends Horde_Form_Type {

    var $_date;
    var $_time;

    /**
     * Return the date supplied as a Horde_Date object.
     *
     * @param integer $start_year  The first available year for input.
     * @param integer $end_year    The last available year for input.
     * @param boolean $picker      Do we show the DHTML calendar?
     * @param integer $format_in   The format to use when sending the date
     *                             for storage. Defaults to Unix epoch.
     *                             Similar to the strftime() function.
     * @param integer $format_out  The format to use when displaying the
     *                             date. Similar to the strftime() function.
     * @param boolean $show_seconds Include a form input for seconds.
     */
    function init($start_year = '', $end_year = '', $picker = true,
                  $format_in = null, $format_out = '%x', $show_seconds = false)
    {
        $this->_date = new Horde_Form_Type_Date();
        $this->_date->init($start_year, $end_year, $picker, $format_in, $format_out);

        $this->_time = new Horde_Form_Type_Time();
        $this->_time->init($show_seconds);
    }

    function isValid($var, $vars, $value, &$message)
    {
        if ($var->required) {
            return $this->_date->isValid($var, $vars, $value, $message) &&
                $this->_time->isValid($var, $vars, $value, $message);
        }
        return true;
    }

    function getInfo(&$vars, &$var, &$info)
    {
        /* If any component is empty consider it a bad date and return the
         * default. */
        $value = $var->getValue($vars);
        if ($this->emptyDateArray($value) == 1 || $this->emptyTimeArray($value)) {
            $info = $var->getDefault();
            return;
        }

        $date = $this->getDateOb($value);
        $time = $this->getTimeOb($value);
        $date->hour = $time->hour;
        $date->min = $time->min;
        $date->sec = $time->sec;
        if (is_null($this->format_in)) {
            $info = $date->timestamp();
        } else {
            $info = $date->strftime($this->format_in);
        }
    }

    function __get($property)
    {
        if ($property == 'show_seconds') {
            return $this->_time->$property;
        } else {
            return $this->_date->$property;
        }
    }

    function __set($property, $value)
    {
        if ($property == 'show_seconds') {
            $this->_time->$property = $value;
        } else {
            $this->_date->$property = $value;
        }
    }

    function checktime($hour, $minute, $second)
    {
        return $this->_time->checktime($hour, $minute, $second);
    }

    function getTimeOb($time_in)
    {
        return $this->_time->getTimeOb($time_in);
    }

    function getTimeParts($time_in)
    {
        return $this->_time->getTimeParts($time_in);
    }

    function emptyTimeArray($time)
    {
        return $this->_time->emptyTimeArray($time);
    }

    function emptyDateArray($date)
    {
        return $this->_date->emptyDateArray($date);
    }

    function getDateParts($date_in)
    {
        return $this->_date->getDateParts($date_in);
    }

    function getDateOb($date_in)
    {
        return $this->_date->getDateOb($date_in);
    }

    function formatDate($date)
    {
        if ($date === null) {
            return '';
        }
        return $this->_date->formatDate($date);
    }

}

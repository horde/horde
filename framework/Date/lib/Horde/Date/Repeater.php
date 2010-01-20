<?php
/**
 */

/**
 */
abstract class Horde_Date_Repeater
{
    public $now;

    /**
     * returns the width (in seconds or months) of this repeatable.
     */
    abstract public function width();

    /**
     * returns the next occurance of this repeatable.
     */
    public function next($pointer = 'future')
    {
        if (is_null($this->now)) {
            throw new Horde_Date_Repeater_Exception('Start point must be set before calling next()');
        }

        if (!in_array($pointer, array('future', 'none', 'past'))) {
            throw new Horde_Date_Repeater_Exception("First argument 'pointer' must be one of 'past', 'future', 'none'");
        }
    }

    public function this($pointer = 'future')
    {
        if (is_null($this->now)) {
            throw new Horde_Date_Repeater_Exception('Start point must be set before calling this()');
        }

        if (!in_array($pointer, array('future', 'none', 'past'))) {
            throw new Horde_Date_Repeater_Exception("First argument 'pointer' must be one of 'past', 'future', 'none'");
        }
    }

    public function __toString()
    {
        return 'repeater';
    }

}

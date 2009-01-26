<?php
/**
  # A Span represents a range of time. Since this class extends
  # Range, you can use #begin and #end to get the beginning and
  # ending times of the span (they will be of class Time)
 * @TODO remove dependencies on timestamps
 */
class Horde_Date_Span
{
    public $begin;
    public $end;

    public function __construct($begin, $end)
    {
        $this->begin = $begin;
        $this->end = $end;
    }

    /**
     * Return the width of this span in seconds
     */
    public function width()
    {
        return $this->end - $this->begin;
    }

    /**
     * Add a number of seconds to this span, returning the new span
     */
    public function add($seconds)
    {
        return new Horde_Date_Span($this->begin + $seconds, $this->end + $seconds);
    }

    /**
     * Subtract a number of seconds from this span, returning the new span.
     */
    public function sub($seconds)
    {
        return $this->add(-$seconds);
    }

    public function __toString()
    {
        return '(' . $this->begin . '..' . $this->end . ')';
    }

}

<?php
/**
 * A Span represents a range of time.
 *
 * @package Horde_Date
 */

/**
 * @package Horde_Date
 */
class Horde_Date_Span
{
    /**
     * @var Horde_Date
     */
    public $begin;

    /**
     * @var Horde_Date
     */
    public $end;

    /**
     * Constructor
     *
     * @param mixed $begin  Horde_Date or other format accepted by the Horde_Date constructor
     * @param mixed $end    Horde_Date or other format accepted by the Horde_Date constructor
     */
    public function __construct($begin, $end)
    {
        if (! $begin instanceof Horde_Date) { $begin = new Horde_Date($begin); }
        if (! $end instanceof Horde_Date) { $end = new Horde_Date($end); }

        $this->begin = $begin;
        $this->end = $end;
    }

    /**
     * Return the width of this span in seconds
     */
    public function width()
    {
        return abs($this->end->timestamp() - $this->begin->timestamp());
    }

    /**
     * Is a Horde_Date within this span?
     *
     * @param Horde_Date $date
     */
    public function includes($date)
    {
        return ($this->begin->compareDateTime($date) <= 0) && ($this->end->compareDateTime($date) >= 0);
    }

    /**
     * Add a number of seconds to this span, returning the new span
     */
    public function add($factor)
    {
        return new Horde_Date_Span($this->begin->add($factor), $this->end->add($factor));
    }

    /**
     * Subtract a number of seconds from this span, returning the new span.
     */
    public function sub($factor)
    {
        return new Horde_Date_Span($this->begin->sub($factor), $this->end->sub($factor));
    }

    public function __toString()
    {
        return '(' . $this->begin . '..' . $this->end . ')';
    }

}

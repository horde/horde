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
        return $this->end->timestamp() - $this->begin->timestamp();
    }

    /**
     * Add a number of seconds to this span, returning the new span
     */
    public function add($factor)
    {
        $b = clone($this->begin);
        $e = clone($this->end);
        if (is_array($factor)) {
            foreach ($factor as $property => $value) {
                $b->$property += $value;
                $e->$property += $value;
            }
        } else {
            $b->sec += $factor;
            $e->sec += $factor;
        }
        return new Horde_Date_Span($b, $e);
    }

    /**
     * Subtract a number of seconds from this span, returning the new span.
     */
    public function sub($factor)
    {
        if (is_array($factor)) {
            foreach ($factor as &$value) {
                $value *= -1;
            }
        } else {
            $factor *= -1;
        }

        return $this->add($factor);
    }

    public function __toString()
    {
        return '(' . $this->begin . '..' . $this->end . ')';
    }

}

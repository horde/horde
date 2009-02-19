<?php
class Horde_Date_Repeater_Second extends Horde_Date_Repeater
{
    public $secondStart;

    public function next($pointer = 'future')
    {
        parent::next($pointer);

        $direction = ($pointer == 'future') ? 1 : -1;

        if (!$this->secondStart) {
            $this->secondStart = clone $this->now;
            $this->secondStart->sec += $direction;
        } else {
            $this->secondStart += $direction;
        }

        $end = clone $this->secondStart;
        $end->sec++;
        return new Horde_Date_Span($this->secondStart, $end);
    }

    public function this($pointer = 'future')
    {
        parent::this($pointer);

        $end = clone $this->now;
        $end->sec++;
        return new Horde_Date_Span($this->now, $end);
    }

    public function offset($span, $amount, $pointer)
    {
        $direction = ($pointer == 'future') ? 1 : -1;
        return $span->add($direction * $amount);
    }

    public function width()
    {
        return 1;
    }

    public function __toString()
    {
        return parent::__toString() . '-second';
    }

}

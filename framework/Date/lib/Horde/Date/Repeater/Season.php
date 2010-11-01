<?php
class Horde_Date_Repeater_Season extends Horde_Date_Repeater
{
    /**
     * 91 * 24 * 60 * 60
     */
    const SEASON_SECONDS = 7862400;

    public function next($pointer = 'future')
    {
        parent::next($pointer);
        throw new Horde_Date_Repeater_Exception('Not implemented');
    }

    public function this($pointer = 'future')
    {
        parent::this($pointer);
        throw new Horde_Date_Repeater_Exception('Not implemented');
    }

    public function width()
    {
        return self::SEASON_SECONDS;
    }

    public function __toString()
    {
        return parent::__toString() . '-season';
    }

}

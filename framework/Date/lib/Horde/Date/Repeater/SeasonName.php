<?php
/**
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Date
 */

/**
 * @category Horde
 * @package  Date
 */
class Horde_Date_Repeater_SeasonName extends Horde_Date_Repeater_Season
{
    /**
     * 91 * 24 * 60 * 60
     */
    const SEASON_SECONDS = 7862400;

    public $summer = array('jul 21', 'sep 22');
    public $autumn = array('sep 23', 'dec 21');
    public $winter = array('dec 22', 'mar 19');
    public $spring = array('mar 20', 'jul 20');
    public $type;

    public function __construct($type)
    {
        $this->type = $type;
    }

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
        return parent::__toString() . '-season-' . $this->type;
    }

}

<?php
/**
 * The Kronolith_View_WorkWeek:: class provides a shortcut for a week
 * view that is only Monday through Friday.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Kronolith
 */
class Kronolith_View_WorkWeek extends Kronolith_View_Week
{
    public $startDay = Horde_Date::DATE_MONDAY;
    public $endDay = Horde_Date::DATE_FRIDAY;
    protected $_controller = 'workweek.php';

    public function getName()
    {
        return 'WorkWeek';
    }

}

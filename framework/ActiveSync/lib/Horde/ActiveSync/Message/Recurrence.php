<?php
/**
 * Horde_ActiveSync_Message_Recurrence class represents a single ActiveSync
 * recurrence sub-object.
 *
 * @copyright 2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_ActiveSync
 */
class Horde_ActiveSync_Message_Recurrence extends Horde_ActiveSync_Message_Base
{
    public $type;
    public $until;
    public $occurrences;
    public $interval;
    public $dayofweek;
    public $dayofmonth;
    public $weekofmonth;
    public $monthofyear;

    /* MS AS Recurrence types */
    const TYPE_DAILY = 0;
    const TYPE_WEEKLY = 1;
    const TYPE_MONTHLY = 2;
    const TYPE_MONTHLY_NTH = 3;
    const TYPE_YEARLY = 5;
    const TYPE_YEARLYNTH = 6;



    function __construct($params = array())
    {
        $mapping = array (
            SYNC_POOMCAL_TYPE => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'type'),
            SYNC_POOMCAL_UNTIL => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'until', Horde_ActiveSync_Message_Base::KEY_TYPE => Horde_ActiveSync_Message_Base::TYPE_DATE),
            SYNC_POOMCAL_OCCURRENCES => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'occurrences'),
            SYNC_POOMCAL_INTERVAL => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'interval'),
            SYNC_POOMCAL_DAYOFWEEK => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'dayofweek'),
            SYNC_POOMCAL_DAYOFMONTH => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'dayofmonth'),
            SYNC_POOMCAL_WEEKOFMONTH => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'weekofmonth'),
            SYNC_POOMCAL_MONTHOFYEAR => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'monthofyear')
        );

        parent::__construct($mapping, $params);
    }

}
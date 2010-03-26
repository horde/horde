<?php
/**
 * Horde_ActiveSync_Message_Attendee class represents a single ActiveSync
 * Attendee sub-object.
 *
 * @copyright 2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_ActiveSync
 */
class Horde_ActiveSync_Message_Attendee extends Horde_ActiveSync_Message_Base
{
    /**
     * Const'r
     *
     * @param array $params
     */
    function __construct($params) {
        $mapping = array(
            SYNC_POOMCAL_EMAIL => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'email'),
            SYNC_POOMCAL_NAME => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'name' )
        );

        parent::__construct($mapping, $params);
    }

}
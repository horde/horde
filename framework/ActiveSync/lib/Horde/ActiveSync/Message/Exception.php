<?php
/**
 * Horde_ActiveSync_Message_Exception class represents a single exception to a
 * recurring event. This is basically a Appointment object with some tweaks.
 * 
 * @copyright 2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_ActiveSync
 */
class Horde_ActiveSync_Message_Exception extends Horde_ActiveSync_Message_Appointment
{
    /**
     * Constructor
     *
     * @param array $params
     *
     * @return Horde_ActiveSync_Message_Appointment
     */
    public function __construct($params = array())
    {   
        parent::__construct($params);

        /* Some additional properties for Exceptions */
        $this->_mapping[SYNC_POOMCAL_EXCEPTIONSTARTTIME] = array(
            Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'exceptionstarttime',
            Horde_ActiveSync_Message_Base::KEY_TYPE => Horde_ActiveSync_Message_Base::TYPE_DATE);

        $this->_mapping[SYNC_POOMCAL_DELETED] = array(Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'deleted');
    }

    /**
     * Sets the DELETED field on this exception
     *
     * @param boolean $flag
     */
    public function setDeletedFlag($flag)
    {
        $this->_properties['deleted'] = $flag;
    }

    /**
     * Exception start time
     *
     * @return Horde_Date  The exception's start time
     */
    public function getExceptionStartTime()
    {
        return $this->_getAttribute('exceptionstarttime');
    }

    public function setExceptionStartTime($date)
    {
        $this->exceptionstarttime = $date;
    }
}

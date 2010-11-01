<?php
/**
 * Ingo_Storage_Vacation is the object used to hold vacation rule
 * information.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Ingo
 */
class Ingo_Storage_Vacation extends Ingo_Storage_Rule
{

    /**
     */
    protected $_addr = array();

    /**
     */
    protected $_days = 7;

    /**
     */
    protected $_excludes = array();

    /**
     */
    protected $_ignorelist = true;

    /**
     */
    protected $_reason = '';

    /**
     */
    protected $_subject = '';

    /**
     */
    protected $_start;

    /**
     */
    protected $_end;

    /**
     */
    protected $_obtype = Ingo_Storage::ACTION_VACATION;

    /**
     */
    public function setVacationAddresses($data, $sort = true)
    {
        $this->_addr = $this->_addressList($data, $sort);
    }

    /**
     */
    public function setVacationDays($data)
    {
        $this->_days = $data;
    }

    /**
     */
    public function setVacationExcludes($data, $sort = true)
    {
        $this->_excludes = $this->_addressList($data, $sort);
    }

    /**
     */
    public function setVacationIgnorelist($data)
    {
        $this->_ignorelist = $data;
    }

    /**
     */
    public function setVacationReason($data)
    {
        $this->_reason = $data;
    }

    /**
     */
    public function setVacationSubject($data)
    {
        $this->_subject = $data;
    }

    /**
     */
    public function setVacationStart($data)
    {
        $this->_start = $data;
    }

    /**
     */
    public function setVacationEnd($data)
    {
        $this->_end = $data;
    }

    /**
     */
    public function getVacationAddresses()
    {
        try {
            return Horde::callHook('vacation_addresses', array(Ingo::getUser()), 'ingo');
        } catch (Horde_Exception_HookNotSet $e) {
            return $this->_addr;
        }
    }

    /**
     */
    public function getVacationDays()
    {
        return $this->_days;
    }

    /**
     */
    public function getVacationExcludes()
    {
        return $this->_excludes;
    }

    /**
     */
    public function getVacationIgnorelist()
    {
        return $this->_ignorelist;
    }

    /**
     */
    public function getVacationReason()
    {
        return $this->_reason;
    }

    /**
     */
    public function getVacationSubject()
    {
        return $this->_subject;
    }

    /**
     */
    public function getVacationStart()
    {
        return $this->_start;
    }

    /**
     */
    public function getVacationStartYear()
    {
        return date('Y', $this->_start);
    }

    /**
     */
    public function getVacationStartMonth()
    {
        return date('n', $this->_start);
    }

    /**
     */
    public function getVacationStartDay()
    {
        return date('j', $this->_start);
    }

    /**
     */
    public function getVacationEnd()
    {
        return $this->_end;
    }

    /**
     */
    public function getVacationEndYear()
    {
        return date('Y', $this->_end);
    }

    /**
     */
    public function getVacationEndMonth()
    {
        return date('n', $this->_end);
    }

    /**
     */
    public function getVacationEndDay()
    {
        return date('j', $this->_end);
    }

}

<?php
/**
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */
class Kronolith_Event_Ical extends Kronolith_Event
{
    /**
     * The type of the calender this event exists on.
     *
     * @var string
     */
    public $calendarType = 'remote';

    /**
     * Imports a backend specific event object.
     *
     * @param Horde_iCalendar_vEvent  Backend specific event object that this
     *                                object will represent.
     */
    public function fromDriver($vEvent)
    {
        $this->fromiCalendar($vEvent);
        $this->initialized = true;
        $this->stored = true;
    }

    /**
     * Prepares this event to be saved to the backend.
     */
    public function toDriver()
    {
        return $this->toiCalendar();
    }

    /**
     * Encapsulates permissions checking.
     *
     * @param integer $permission  The permission to check for.
     * @param string $user         The user to check permissions for.
     *
     * @return boolean
     */
    public function hasPermission($permission, $user = null)
    {
        switch ($permission) {
        case Horde_Perms::SHOW:
        case Horde_Perms::READ:
            return true;

        default:
            return false;
        }
    }

    /**
     * Returns the title of this event.
     *
     * @param string $user  The current user.
     *
     * @return string  The title of this event.
     */
    public function getTitle($user = null)
    {
        return !empty($this->title) ? $this->title : _("[Unnamed event]");
    }

    /**
     * @param array $params
     *
     * @return Horde_Url
     */
    public function getViewUrl($params = array(), $full = false, $encoded = true)
    {
        if ($this->url) {
            return new Horde_Url($this->url, !$encoded);
        }
        return parent::getViewUrl($params, $full, $encoded);
    }

    /**
     * Parses the various exception related fields. Only deal with the EXDATE
     * field here.
     *
     * @param Horde_iCalendar $vEvent  The vEvent part.
     */
    protected function _handlevEventRecurrence($vEvent)
    {
        // Recurrence.
        $rrule = $vEvent->getAttribute('RRULE');
        if (!is_array($rrule) && !($rrule instanceof PEAR_Error)) {
            $this->recurrence = new Horde_Date_Recurrence($this->start);
            if (strpos($rrule, '=') !== false) {
                $this->recurrence->fromRRule20($rrule);
            } else {
                $this->recurrence->fromRRule10($rrule);
            }

            // Exceptions. EXDATE represents deleted events, just add the
            // exception, no new event is needed.
            $exdates = $vEvent->getAttributeValues('EXDATE');
            if (is_array($exdates)) {
                foreach ($exdates as $exdate) {
                    if (is_array($exdate)) {
                        $this->recurrence->addException((int)$exdate['year'],
                                                        (int)$exdate['month'],
                                                        (int)$exdate['mday']);
                    }
                }
            }
        }
    }

}

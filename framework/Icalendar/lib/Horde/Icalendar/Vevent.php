<?php
/**
 * Class representing vEvents.
 *
 * Copyright 2003-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Icalendar
 */
class Horde_Icalendar_Vevent extends Horde_Icalendar
{
    /**
     * The component type of this class.
     *
     * @var string
     */
    public $type = 'vEvent';

    /**
     * TODO
     *
     * @return TODO
     */
    public function exportvCalendar()
    {
        // Default values.
        $requiredAttributes = array(
            'DTSTAMP' => time(),
            'UID' => strval(new Horde_Support_Uuid())
        );

        $method = !empty($this->_container)
            ? $this->_container->getAttribute('METHOD')
            : 'PUBLISH';

        switch ($method) {
        case 'PUBLISH':
            $requiredAttributes['DTSTART'] = time();
            $requiredAttributes['SUMMARY'] = '';
            break;

        case 'REQUEST':
            $requiredAttributes['ATTENDEE'] = '';
            $requiredAttributes['DTSTART'] = time();
            $requiredAttributes['SUMMARY'] = '';
            break;

        case 'REPLY':
            $requiredAttributes['ATTENDEE'] = '';
            break;

        case 'ADD':
            $requiredAttributes['DTSTART'] = time();
            $requiredAttributes['SEQUENCE'] = 1;
            $requiredAttributes['SUMMARY'] = '';
            break;

        case 'CANCEL':
            $requiredAttributes['ATTENDEE'] = '';
            $requiredAttributes['SEQUENCE'] = 1;
            break;

        case 'REFRESH':
            $requiredAttributes['ATTENDEE'] = '';
            break;
        }

        foreach ($requiredAttributes as $name => $default_value) {
            try {
                $this->getAttribute($name);
            } catch (Horde_Icalendar_Exception $e) {
                $this->setAttribute($name, $default_value);
            }
        }

        return $this->_exportvData('VEVENT');
    }

    /**
     * Update the status of an attendee of an event.
     *
     * @param $email    The email address of the attendee.
     * @param $status   The participant status to set.
     * @param $fullname The full name of the participant to set.
     */
    public function updateAttendee($email, $status, $fullname = '')
    {
        foreach ($this->_attributes as $key => $attribute) {
            if ($attribute['name'] == 'ATTENDEE' &&
                $attribute['value'] == 'mailto:' . $email) {
                $this->_attributes[$key]['params']['PARTSTAT'] = $status;
                if (!empty($fullname)) {
                    $this->_attributes[$key]['params']['CN'] = $fullname;
                }
                unset($this->_attributes[$key]['params']['RSVP']);
                return;
            }
        }
        $params = array('PARTSTAT' => $status);
        if (!empty($fullname)) {
            $params['CN'] = $fullname;
        }
        $this->setAttribute('ATTENDEE', 'mailto:' . $email, $params);
    }

    /**
     * Return the organizer display name or email.
     *
     * @return string  The organizer name to display for this event.
     */
    public function organizerName()
    {
        try {
            $organizer = $this->getAttribute('ORGANIZER', true);
        } catch (Horde_Icalendar_Exception $e) {
            return Horde_Icalendar_Translation::t("An unknown person");
        }

        if (isset($organizer[0]['CN'])) {
            return $organizer[0]['CN'];
        }

        $organizer = parse_url($this->getAttribute('ORGANIZER'));

        return $organizer['path'];
    }

    /**
     * Update this event with details from another event.
     *
     * @param Horde_Icalendar_Vevent $vevent  The vEvent with latest details.
     */
    public function updateFromvEvent($vevent)
    {
        $newAttributes = $vevent->getAllAttributes();
        foreach ($newAttributes as $newAttribute) {
            try {
                $currentValue = $this->getAttribute($newAttribute['name']);
            } catch (Horde_Icalendar_Exception $e) {
                // Already exists so just add it.
                $this->setAttribute($newAttribute['name'],
                                    $newAttribute['value'],
                                    $newAttribute['params']);
                continue;
            }

            // Already exists so locate and modify.
            $found = false;

            // Try matching the attribte name and value incase
            // only the params changed (eg attendee updating
            // status).
            foreach ($this->_attributes as $id => $attr) {
                if ($attr['name'] == $newAttribute['name'] &&
                    $attr['value'] == $newAttribute['value']) {
                    // merge the params
                    foreach ($newAttribute['params'] as $param_id => $param_name) {
                        $this->_attributes[$id]['params'][$param_id] = $param_name;
                    }
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                // Else match the first attribute with the same
                // name (eg changing start time).
                foreach ($this->_attributes as $id => $attr) {
                    if ($attr['name'] == $newAttribute['name']) {
                        $this->_attributes[$id]['value'] = $newAttribute['value'];
                        // Merge the params.
                        foreach ($newAttribute['params'] as $param_id => $param_name) {
                            $this->_attributes[$id]['params'][$param_id] = $param_name;
                        }
                        break;
                    }
                }
            }
        }
    }

    /**
     * Update just the attendess of event with details from another
     * event.
     *
     * @param Horde_Icalendar_Vevent $vevent  The vEvent with latest details
     */
    public function updateAttendeesFromvEvent($vevent)
    {
        $newAttributes = $vevent->getAllAttributes();
        foreach ($newAttributes as $newAttribute) {
            if ($newAttribute['name'] != 'ATTENDEE') {
                continue;
            }

            try {
                $currentValue = $this->getAttribute($newAttribute['name']);
            } catch (Horde_Icalendar_Exception $e) {
                // Already exists so just add it.
                $this->setAttribute($newAttribute['name'],
                                    $newAttribute['value'],
                                    $newAttribute['params']);
                continue;
            }

            // Already exists so locate and modify.
            $found = false;
            // Try matching the attribte name and value incase
            // only the params changed (eg attendee updating
            // status).
            foreach ($this->_attributes as $id => $attr) {
                if ($attr['name'] == $newAttribute['name'] &&
                    $attr['value'] == $newAttribute['value']) {
                    // Merge the params.
                    foreach ($newAttribute['params'] as $param_id => $param_name) {
                        $this->_attributes[$id]['params'][$param_id] = $param_name;
                    }
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                // Else match the first attribute with the same
                // name (eg changing start time).
                foreach ($this->_attributes as $id => $attr) {
                    if ($attr['name'] == $newAttribute['name']) {
                        $this->_attributes[$id]['value'] = $newAttribute['value'];
                        // Merge the params.
                        foreach ($newAttribute['params'] as $param_id => $param_name) {
                            $this->_attributes[$id]['params'][$param_id] = $param_name;
                        }
                        break;
                    }
                }
            }
        }
    }

}

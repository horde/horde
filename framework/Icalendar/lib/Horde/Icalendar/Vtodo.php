<?php
/**
 * Class representing vTodos.
 *
 * Copyright 2003-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Icalendar
 */
class Horde_Icalendar_Vtodo extends Horde_Icalendar
{
    /**
     * The component type of this class.
     *
     * @var string
     */
    public $type = 'vTodo';

    /**
     * TODO
     *
     * @return TODO
     */
    public function exportvCalendar()
    {
        return $this->_exportvData('VTODO');
    }

    /**
     * Convert this todo to an array of attributes.
     *
     * @return array  Array containing the details of the todo in a hash
     *                as used by Horde applications.
     */
    public function toArray()
    {
        $todo = array();

        try {
            $name = $this->getAttribute('SUMMARY');
            if (!is_array($name)) {
                $todo['name'] = $name;
            }
        } catch (Horde_Icalendar_Exception $e) {}

        try {
            $desc = $this->getAttribute('DESCRIPTION');
            if (!is_array($desc)) {
                $todo['desc'] = $desc;
            }
        } catch (Horde_Icalendar_Exception $e) {}

        try {
            $priority = $this->getAttribute('PRIORITY');
            if (!is_array($priority)) {
                $todo['priority'] = $priority;
            }
        } catch (Horde_Icalendar_Exception $e) {}

        try {
            $due = $this->getAttribute('DTSTAMP');
            if (!is_array($due)) {
                $todo['due'] = $due;
            }
        } catch (Horde_Icalendar_Exception $e) {}

        return $todo;
    }

    /**
     * Set the attributes for this todo item from an array.
     *
     * @param array $todo  Array containing the details of the todo in
     *                     the same format that toArray() exports.
     */
    public function fromArray($todo)
    {
        if (isset($todo['name'])) {
            $this->setAttribute('SUMMARY', $todo['name']);
        }
        if (isset($todo['desc'])) {
            $this->setAttribute('DESCRIPTION', $todo['desc']);
        }

        if (isset($todo['priority'])) {
            $this->setAttribute('PRIORITY', $todo['priority']);
        }

        if (isset($todo['due'])) {
            $this->setAttribute('DTSTAMP', $todo['due']);
        }
    }

}

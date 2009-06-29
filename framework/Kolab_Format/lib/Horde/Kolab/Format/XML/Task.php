<?php
/**
 * Implementation for tasks in the Kolab XML format.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Kolab XML handler for task groupware objects.
 *
 * Copyright 2007-2009 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 * @since    Horde 3.2
 */
class Horde_Kolab_Format_XML_Task extends Horde_Kolab_Format_XML
{
    /**
     * Specific data fields for the note object
     *
     * @var array
     */
    protected $_fields_specific;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_root_name = 'task';

        /** Specific task fields, in kolab format specification order
         */
        $this->_fields_specific = array(
            'summary' => array(
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_DEFAULT,
                'default' => '',
            ),
            'location' => array(
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_DEFAULT,
                'default' => '',
            ),
            'creator'   => $this->_fields_simple_person,
            'organizer' => $this->_fields_simple_person,
            'start-date' => array(
                'type'    => self::TYPE_DATE_OR_DATETIME,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'alarm' => array(
                'type'    => self::TYPE_INTEGER,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'recurrence' => array(
                'type'    => self::TYPE_COMPOSITE,
                'value'   => self::VALUE_CALCULATED,
                'load'    => 'Recurrence',
                'save'    => 'Recurrence',
            ),
            'attendee' => $this->_fields_attendee,
            'priority' => array(
                'type'    => self::TYPE_INTEGER,
                'value'   => self::VALUE_DEFAULT,
                'default' => 3,
            ),
            'completed' => array(
                'type'    => self::TYPE_INTEGER,
                'value'   => self::VALUE_DEFAULT,
                'default' => 0,
            ),
            'status' => array(
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_DEFAULT,
                'default' => 'not-started',
            ),
            'due-date' => array(
                'type'    => self::TYPE_DATE_OR_DATETIME,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'parent' => array(
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            // These are not part of the Kolab specification but it is
            // ok if the client supports additional entries
            'estimate' => array(
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'completed_date' => array(
                'type'    => self::TYPE_DATE_OR_DATETIME,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
        );

        parent::Horde_Kolab_Format_XML();
    }

    /**
     * Load the groupware object based on the specifc XML values.
     *
     * @param array &$children An array of XML nodes.
     *
     * @return array Array with data.
     *
     * @throws Horde_Exception If parsing the XML data failed.
     */
    protected function _load(&$children)
    {
        $object = $this->_loadArray($children, $this->_fields_specific);
        if (is_a($object, 'PEAR_Error')) {
            return $object;
        }

        $object['name'] = $object['summary'];
        unset($object['summary']);

        if (empty($object['completed-date'])) {
            $object['completed-date'] = null;
        }

        if (empty($object['alarm'])) {
            $object['alarm'] = null;
        }

        if (isset($object['due-date'])) {
            $object['due'] = $object['due-date'];
            unset($object['due-date']);
        } else {
            $object['due'] = null;
        }

        if (isset($object['start-date'])) {
            $object['start'] = $object['start-date'];
            unset($object['start-date']);
        } else {
            $object['start'] = null;
        }

        if (!isset($object['estimate'])) {
            $object['estimate'] = null;
        } else {
            $object['estimate'] = (float) $object['estimate'];
        }

        if (!isset($object['parent'])) {
            $object['parent'] = null;
        }

        $object['completed'] = (bool) Kolab::percentageToBoolean($object['completed']);

        if (isset($object['organizer']) && isset($object['organizer']['smtp-address'])) {
            $object['assignee'] = $object['organizer']['smtp-address'];
        }

        return $object;
    }

    /**
     * Save the specific XML values.
     *
     * @param array $root   The XML document root.
     * @param array $object The resulting data array.
     *
     * @return boolean True on success.
     *
     * @throws Horde_Exception If converting the data to XML failed.
     */
    protected function _save($root, $object)
    {
        $object['summary'] = $object['name'];
        unset($object['name']);

        $object['due-date'] = $object['due'];
        unset($object['due']);

        $object['start-date'] = $object['start'];
        unset($object['start']);

        $object['estimate'] = number_format($object['estimate'], 2);

        $object['completed'] = Kolab::BooleanToPercentage($object['completed']);

        return $this->_saveArray($root, $object, $this->_fields_specific);
    }
}

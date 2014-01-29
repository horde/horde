<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Dav
 */

use Sabre\DAV;
use Sabre\CalDAV\Backend;

/**
 * The calendar and task list backend wrapper.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Dav
 */
class Horde_Dav_Calendar_Backend extends Backend\AbstractBackend
{
    /**
     * A registry object.
     *
     * @var Horde_Registry
     */
    protected $_registry;

    /**
     * A storage object.
     *
     * @var Horde_Dav_Storage_Base
     */
    protected $_storage;

    /**
     * List of available interfaces and the providing applications.
     *
     * @var array
     */
    protected $_interfaces = array();

    /**
     * Constructor.
     *
     * @param Horde_Registry $registry         A registry object.
     * @param Horde_Dav_Storage_Base $storage  A storage object.
     */
    public function __construct(Horde_Registry $registry, Horde_Dav_Storage_Base $storage)
    {
        $this->_registry = $registry;
        $this->_storage = $storage;
        foreach (array('calendar', 'tasks') as $interface) {
            try {
                $application = $this->_registry->hasInterface($interface);
                if ($application) {
                    $this->_interfaces[$interface] = $application;
                }
            } catch (Horde_Exception $e) {
            }
        }
    }

    /**
     * Returns a list of calendars for a principal.
     *
     * @param string $principalUri
     * @return array
     */
    public function getCalendarsForUser($principalUri)
    {
        list($prefix, $user) = DAV\URLUtil::splitPath($principalUri);
        if ($prefix != 'principals') {
            throw new DAV\Exception\NotFound('Invalid principal prefix path ' . $prefix);
        }

        $collections = array();
        foreach ($this->_interfaces as $interface) {
            try {
                $collections = array_merge(
                    $collections,
                    (array)$this->_registry->callAppMethod(
                        $interface,
                        'davGetCollections',
                        array('args' => array($user))
                    )
                );
            } catch (Horde_Exception $e) {
                throw new DAV\Exception($e->getMessage(), $e->getCode(), $e);
            }
        }
        return $collections;
    }

    /**
     * Creates a new calendar for a principal.
     *
     * If the creation was a success, an id must be returned that can be used to reference
     * this calendar in other methods, such as updateCalendar.
     *
     * @param string $principalUri
     * @param string $calendarUri
     * @param array $properties
     * @return void
     */
    public function createCalendar($principalUri, $calendarUri, array $properties)

    {
    }

    /**
     * Delete a calendar and all it's objects
     *
     * @param mixed $calendarId
     * @return void
     */
    public function deleteCalendar($calendarId)
    {
    }

    /**
     * Returns all calendar objects within a calendar.
     *
     * @param mixed $calendarId
     * @return array
     */
    public function getCalendarObjects($calendarId)
    {
        try {
            return $this->_registry->callAppMethod(
                $this->_interface($calendarId),
                'davGetObjects',
                array('args' => array($calendarId))
            );
        } catch (Horde_Exception $e) {
            throw new DAV\Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Returns information from a single calendar object, based on it's object
     * uri.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @return array
     */
    public function getCalendarObject($calendarId, $objectUri)
    {
        try {
            return $this->_registry->callAppMethod(
                $this->_interface($calendarId),
                'davGetObject',
                array('args' => array($calendarId, $objectUri))
            );
        } catch (Horde_Exception_NotFound $e) {
            return null;
        } catch (Horde_Exception $e) {
            throw new DAV\Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Creates a new calendar object.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @param string $calendarData
     * @return string|null
     */
    public function createCalendarObject($calendarId, $objectUri, $calendarData)
    {
        $this->updateCalendarObject($calendarId, $objectUri, $calendarData);
    }

    /**
     * Updates an existing calendarobject, based on it's uri.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @param string $calendarData
     * @return string|null
     */
    public function updateCalendarObject($calendarId, $objectUri, $calendarData)
    {
        try {
            return $this->_registry->callAppMethod(
                $this->_interface($calendarId),
                'davPutObject',
                array('args' => array($calendarId, $objectUri, $calendarData))
            );
        } catch (Horde_Exception $e) {
            throw new DAV\Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Deletes an existing calendar object.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @return void
     */
    public function deleteCalendarObject($calendarId, $objectUri)
    {
        try {
            return $this->_registry->callAppMethod(
                $this->_interface($calendarId),
                'davDeleteObject',
                array('args' => array($calendarId, $objectUri))
            );
        } catch (Horde_Exception $e) {
            throw new DAV\Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Returns the application that owns a certain calendar or task list.
     *
     * @param string $calendarId  An external calendar or task list id.
     *
     * @return string  The application that owns the calendar or task list.
     * @throws Sabre\DAV\Exception if the application cannot be found.
     */
    protected function _interface($calendarId)
    {
        $interface = $this->_storage->getCollectionInterface($calendarId);
        if (!$interface || !isset($this->_interfaces[$interface])) {
            throw new DAV\Exception(sprintf('No interface found for calendar %s', $calendarId));
        }
        return $this->_interfaces[$interface];
    }
}

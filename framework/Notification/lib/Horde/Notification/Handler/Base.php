<?php
/**
 * The Horde_Notification:: package provides a subject-observer pattern for
 * raising and showing messages of different types and to different
 * listeners.
 *
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_Notification
 */
class Horde_Notification_Handler_Base
implements Horde_Notification_Handler_Interface
{
    /**
     * Hash containing all attached listener objects.
     *
     * @var array
     */
    protected $_listeners = array();

    /**
     * The storage location where we store the messages.
     *
     * @var Horde_Notification_Storage
     */
    protected $_storage;

    /**
     * A Horde_Alarm instance.
     *
     * @var Horde_Alarm
     */
    protected $_alarm;

    /**
     * Initialize the notification system, set up any needed session
     * variables, etc.
     *
     * @param Horde_Notification_Storage $storage The storage location to use.
     */
    public function __construct(
        Horde_Notification_Storage_Interface $storage
    ) {
        $this->_storage = $storage;
    }

    /**
     * Registers a listener with the notification object and includes
     * the necessary library file dynamically.
     *
     * @param string $listener  The name of the listener to attach. These
     *                          names must be unique; further listeners with
     *                          the same name will be ignored.
     * @param array $params     A hash containing any additional configuration
     *                          or connection parameters a listener driver
     *                          might need.
     * @param string $class     The class name from which the driver was
     *                          instantiated if not the default one. If given
     *                          you have to include the library file
     *                          containing this class yourself. This is useful
     *                          if you want the listener driver to be
     *                          overriden by an application's implementation.
     *
     * @return Horde_Notification_Listener  The listener object.
     * @throws Horde_Exception
     */
    public function attach($listener, $params = null, $class = null)
    {
        $listener = Horde_String::lower(basename($listener));
        if (!empty($this->_listeners[$listener])) {
            return $this->_listeners[$listener];
        }

        if (is_null($class)) {
            $class = 'Horde_Notification_Listener_' . Horde_String::ucfirst($listener);
        }

        if (class_exists($class)) {
            $this->_listeners[$listener] = new $class($params);
            if (!$this->_storage->exists($listener)) {
                $this->_storage->set($listener, array());
            }
            return $this->_listeners[$listener];
        }

        throw new Horde_Exception(sprintf('Notification listener %s not found.', $class));
    }

    /**
     * Remove a listener from the notification list. This will discard any
     * notifications in this listeners stack.
     *
     * @param string $listner  The name of the listener to detach.
     *
     * @throws Horde_Exception
     */
    public function detach($listener)
    {
        $listener = Horde_String::lower(basename($listener));
        if (!isset($this->_listeners[$listener])) {
            throw new Horde_Exception(sprintf('Notification listener %s not found.', $listener));
        }

        $listener_instance = $this->_listeners[$listener];
        unset($this->_listeners[$listener]);
        $this->_storage->clear($listener_instance->getName());
    }

    /**
     * Replaces a listener in the notification list. This preserves all
     * notifications in this listeners stack. If the listener does not exist,
     * the new listener will be added automatically.
     *
     * @param string $listener  See attach().
     * @param array $params     See attach().
     * @param string $class     See attach().
     *
     * @return Horde_Notification_Listener  See attach()
     * @throws Horde_Exception
     */
    public function replace($listener, array $params = array(), $class = null)
    {
        $listener = Horde_String::lower(basename($listener));
        unset($this->_listeners[$listener]);
        return $this->attach($listener, $params, $class);
    }

    /**
     * Add an event to the Horde message stack.
     *
     * The event type parameter should begin with 'horde.' unless the
     * application defines its own Horde_Notification_Listener subclass that
     * handles additional codes.
     *
     * @param mixed $event   Horde_Notification_Event object or message string.
     * @param integer $type  The type of message: 'horde.error',
     *                       'horde.warning', 'horde.success', or
     *                       'horde.message'.
     * @param array $flags   Array of optional flags that will be passed to the
     *                       registered listeners.
     */
    public function push($event, $type = null, array $flags = array())
    {
        if (!($event instanceof Horde_Notification_Event) &&
            !($event instanceof PEAR_Error) &&
            !($event instanceof Exception)) {
            /* Transparently create a Horde_Notification_Event object and
             * set the message attribute. */
            $event = new Horde_Notification_Event($event);
        }

        if (is_null($type)) {
            if ($event instanceof PEAR_Error || $event instanceof Exception) {
                $type = 'horde.error';
            } else {
                $type = 'horde.message';
            }
        }

        foreach ($this->_listeners as $listener) {
            if ($listener->handles($type)) {
                $this->_storage->push(
                    $listener->getName(),
                    array(
                        'class' => get_class($event),
                        'event' => serialize($event),
                        'flags' => serialize($flags),
                        'type' => $type
                    )
                );
            }
        }
    }

    /**
     * Passes the message stack to all listeners and asks them to
     * handle their messages.
     *
     * @param array $options  An array containing display options for the
     *                        listeners.
     */
    public function notify(array $options = array())
    {
        $options = $this->setNotificationListeners($options);
        $this->notifyListeners($options);
    }

    /**
     * Convert the 'listeners' option into the format expected by the
     * notification handler.
     *
     * @param array $options  An array containing display options for the
     *                        listeners.
     */
    public function setNotificationListeners(array $options)
    {
        if (!isset($options['listeners'])) {
            $options['listeners'] =  $this->getListeners();
        } elseif (!is_array($options['listeners'])) {
            $options['listeners'] = array($options['listeners']);
        }
        $options['listeners'] = array_map(array('Horde_String', 'lower'), $options['listeners']);
        return $options;
    }

    /**
     * Passes the message stack to all listeners and asks them to
     * handle their messages.
     *
     * @param array $options An array containing display options for the
     *                       listeners. This array is required to contain the
     *                       correct lowercased listener names as array in the
     *                       entry 'listeners'.
     */
    public function notifyListeners(array $options)
    {
        foreach ($options['listeners'] as $listener) {
            if (isset($this->_listeners[$listener])) {
                $this->_listeners[$listener]->notify(
                    $this->_storage->get(
                        $this->_listeners[$listener]->getName()
                    ),
                    $options
                );
            }
        }
    }

    /**
     * Return the number of notification messages in the stack.
     *
     * @author David Ulevitch <davidu@everydns.net>
     *
     * @param string $my_listener  The name of the listener.
     *
     * @return integer  The number of messages in the stack.
     */
    public function count($my_listener = null)
    {
        if (is_null($my_listener)) {
            $count = 0;
            foreach ($this->_listeners as $listener) {
                if ($this->_storage->exists($listener->getName())) {
                    $count += count($this->_storage->get($listener->getName()));
                }
            }
            return $count;
        } else {
            return @count($this->_storage->get($this->_listeners[Horde_String::lower($my_listener)]->getName()));
        }
    }

    protected function getListeners()
    {
        return array_keys($this->_listeners);
    }
}

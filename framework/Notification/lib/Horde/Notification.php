<?php
/**
 * The Horde_Notification:: class provides a subject-observer pattern for
 * raising and showing messages of different types and to different
 * listeners.
 *
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_Notification
 */
class Horde_Notification
{
    /**
     * Horde_Notification instances.
     *
     * @var Horde_Notification
     */
    static protected $_instances = array();

    /**
     * Hash containing all attached listener objects.
     *
     * @var array
     */
    protected $_listeners = array();

    /**
     * The name of the session variable where we store the messages.
     *
     * @var string
     */
    protected $_stack;

    /**
     * A Horde_Alarm instance.
     *
     * @var Horde_Alarm
     */
    protected $_alarm;

    /**
     * Returns a reference to the global Notification object, only
     * creating it if it doesn't already exist.
     *
     * This method must be invoked as:
     *   $notification = Horde_Notification::singleton()
     *
     * @param string $stack  The name of the message stack to use.
     *
     * return Notification  The Horde Notification instance.
     */
    static public function singleton($stack = 'horde_notification_stacks')
    {
        if (!isset(self::$_instances[$stack])) {
            self::$_instances[$stack] = new Horde_Notification($stack);
        }

        return self::$_instances[$stack];
    }

    /**
     * Initialize the notification system, set up any needed session
     * variables, etc.
     *
     * @param string $stack  The name of the message stack to use.
     */
    protected function __construct($stack)
    {
        $this->_stack = $stack;

        /* Make sure the message stack is registered in the session,
         * and obtain a global-scope reference to it. */
        if (!isset($_SESSION[$this->_stack])) {
            $_SESSION[$this->_stack] = array();
        }

        if (!empty($GLOBALS['conf']['alarms']['driver'])) {
            $this->_alarm = Horde_Alarm::factory();
        }
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
    public function attach($listener, $params = array(), $class = null)
    {
        $listener = strtolower(basename($listener));
        if (!empty($this->_listeners[$listener])) {
            return $this->_listeners[$listener];
        }

        if (is_null($class)) {
            $class = 'Horde_Notification_Listener_' . ucfirst($listener);
        }

        if (class_exists($class)) {
            $this->_listeners[$listener] = new $class($params);
            if (!isset($_SESSION[$this->_stack][$listener])) {
                $_SESSION[$this->_stack][$listener] = array();
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
        $listener = strtolower(basename($listener));
        if (!isset($this->_listeners[$listener])) {
            throw new Horde_Exception(sprintf('Notification listener %s not found.', $listener));
        }

        $list = $this->_listeners[$listener];
        unset($this->_listeners[$listener], $_SESSION[$this->_stack][$list->getName()]);
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
    public function replace($listener, $params = array(), $class = null)
    {
        $listener = strtolower(basename($listener));
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
    public function push($event, $type = null, $flags = array())
    {
        if (!($event instanceof Horde_Notification_Event) &&
            !($event instanceof PEAR_Error) &&
            !($event instanceof Exception)) {
            /* Transparently create a Horde_Notification_Event object and
             * set the message attribute. */
            $event = new Horde_Notification_Event($event);
        }

        if ($event instanceof PEAR_Error || $event instanceof Exception) {
            if (is_null($type)) {
                $type = 'horde.error';
            }
            Horde::logMessage($event, __FILE__, __LINE__, PEAR_LOG_DEBUG);
        } elseif (is_null($type)) {
            $type = 'horde.message';
        }

        foreach ($this->_listeners as $listener) {
            if ($listener->handles($type)) {
                $_SESSION[$this->_stack][$listener->getName()][] = array(
                    'class' => get_class($event),
                    'event' => serialize($event),
                    'flags' => serialize($flags),
                    'type' => $type
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
    public function notify($options = array())
    {
        if (!isset($options['listeners'])) {
            $options['listeners'] = array_keys($this->_listeners);
        } elseif (!is_array($options['listeners'])) {
            $options['listeners'] = array($options['listeners']);
        }

        $options['listeners'] = array_map('strtolower', $options['listeners']);

        if ($this->_alarm && in_array('status', $options['listeners'])) {
            $this->_alarm->notify(Horde_Auth::getAuth());
        }

        foreach ($options['listeners'] as $listener) {
            if (isset($this->_listeners[$listener])) {
                $this->_listeners[$listener]->notify($_SESSION[$this->_stack][$this->_listeners[$listener]->getName()], $options);
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
                if (isset($_SESSION[$this->_stack][$listener->getName()])) {
                    $count += count($_SESSION[$this->_stack][$listener->getName()]);
                }
            }
            return $count;
        } else {
            return @count($_SESSION[$this->_stack][$this->_listeners[strtolower($my_listener)]->getName()]);
        }
    }

}

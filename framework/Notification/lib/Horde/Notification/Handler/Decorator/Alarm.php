<?php
/**
 * The Horde_Notification_Handler_Alarm notifies the alarm system if required.
 *
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_Notification
 */
class Horde_Notification_Handler_Decorator_Alarm
implements Horde_Notification_Handler_Interface
{
    /**
     * The notification handler decorated by this instance.
     *
     * @var Horde_Notification_Handler
     */
    private $_handler;

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
     * @param Horde_Notification_Handler $handler The handler this instance
     *                                            provides with logging.
     * @param Horde_Alarm                $alarm   The alarm system to notify.
     */
    public function __construct(
        Horde_Notification_Handler_Interface $handler,
        Horde_Alarm $alarm
    ) {
        $this->_handler = $handler;
        $this->_alarm   = $alarm;
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
        return $this->_handler->attach($listener, $params, $class);
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
        $this->_handler->detach($listener);
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
        return $this->_handler->replace($listener, $params, $class);
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
        $this->_handler->push($event, $type, $flags);
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
        $options = $this->_handler->setNotificationListeners($options);

        if (in_array('status', $options['listeners'])) {
            $this->_alarm->notify(Horde_Auth::getAuth());
        }

        $this->_handler->notifyListeners($options);
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
        return $this->_handler->setNotificationListeners($options);
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
        $this->_handler->notifyListeners($options);
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
        return $this->_handler->count($my_listener);
    }

}

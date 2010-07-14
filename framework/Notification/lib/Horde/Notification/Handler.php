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
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Notification
 */
class Horde_Notification_Handler
{
    /**
     * Hash containing all attached listener objects.
     *
     * @var array
     */
    protected $_listeners = array();

    /**
     * Decorators.
     *
     * @var array
     */
    protected $_decorators = array();

    /**
     * Additional handle definitions.
     *
     * @var array
     */
    protected $_handles = array(
        'default' => array(
            '*' => 'Horde_Notification_Event'
        )
    );

    /**
     * The storage location where we store the messages.
     *
     * @var Horde_Notification_Storage
     */
    protected $_storage;

    /**
     * Initialize the notification system.
     *
     * @param Horde_Notification_Storage $storage  The storage object to use.
     */
    public function __construct(Horde_Notification_Storage_Interface $storage)
    {
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
     *                          overriden by an application's implementation
     *
     * @return Horde_Notification_Listener  The listener object.
     * @throws Horde_Exception
     */
    public function attach($listener, $params = null, $class = null)
    {
        if ($ob = $this->getListener($listener)) {
            return $ob;
        }

        if (is_null($class)) {
            $class = 'Horde_Notification_Listener_' . Horde_String::ucfirst(Horde_String::lower($listener));
        }

        if (class_exists($class)) {
            $this->_listeners[$listener] = new $class($params);
            if (!$this->_storage->exists($listener)) {
                $this->_storage->set($listener, array());
            }
            $this->_addTypes($listener);
            return $this->_listeners[$listener];
        }

        throw new Horde_Exception(sprintf('Notification listener %s not found.', $class));
    }

    /**
     * Remove a listener from the notification list.
     *
     * @param string $listner  The name of the listener to detach.
     *
     * @throws Horde_Exception
     */
    public function detach($listener)
    {
        if ($ob = $this->getListener($listener)) {
            unset($this->_listeners[$ob->getName()]);
            $this->_storage->clear($ob->getName());
        } else {
            throw new Horde_Exception(sprintf('Notification listener %s not found.', $listener));
        }
    }

    /**
     * Clear any notification events that may exist in a listener.
     *
     * @param string $listener  The name of the listener to flush. If null,
     *                          clears all unattached events.
     */
    public function clear($listener = null)
    {
        if (is_null($listener)) {
            $this->_storage->clear('_unattached');
        } elseif ($ob = $this->getListener($listener)) {
            $this->_storage->clear($ob->getName());
        }
    }

    /**
     * Returns the current Listener object for a given listener type.
     *
     * @param string $type  The listener type.
     *
     * @return mixed  A Horde_Notification_Listener object, or null if
     *                $type listener is not attached.
     */
    public function get($type)
    {
        foreach ($this->_listeners as $listener) {
            if ($listener->handles($type)) {
                return $listener;
            }
        }

        return null;
    }

    /**
     * Returns a listener object given a listener name.
     *
     * @param string $listener  The listener name.
     *
     * @return mixed  Either a Horde_Notification_Listener or null.
     */
    public function getListener($listener)
    {
        $listener = Horde_String::lower(basename($listener));
        return empty($this->_listeners[$listener])
            ? null
            : $this->_listeners[$listener];
    }

    /**
     * Adds a type handler to a given Listener.
     * To change the default listener, use the following:
     * <pre>
     *   $ob->addType('default', '*', $classname);
     * </pre>
     *
     * @param string $listener  The listener name.
     * @param string $type      The listener type.
     * @param string $class     The Event class to use.
     */
    public function addType($listener, $type, $class)
    {
        $this->_handles[$listener][$type] = $class;

        if (isset($this->_listeners[$listener])) {
            $this->_addTypes($listener);
        }
    }

    /**
     * Adds any additional listener types to a given Listener.
     *
     * @param string $listener  The listener name.
     */
    protected function _addTypes($listener)
    {
        if (isset($this->_handles[$listener])) {
            foreach ($this->_handles[$listener] as $type => $class) {
                $this->_listeners[$listener]->addType($type, $class);
            }
        }
    }

    /**
     * Add a decorator.
     *
     * @param Horde_Notification_Handler_Decorator_Base $decorator  The
     *                                                              Decorator
     *                                                              object.
     */
    public function addDecorator(Horde_Notification_Handler_Decorator_Base $decorator)
    {
        $this->_decorators[] = $decorator;
    }

    /**
     * Add an event to the Horde message stack.
     *
     * @param mixed $event    Horde_Notification_Event object or message
     *                        string.
     * @param integer $type   The type of message.
     * @param array $flags    Array of optional flags that will be passed to
     *                        the registered listeners.
     * @param array $options  Additional options:
     * <pre>
     * 'immediate' - (boolean) If true, immediately tries to attach to a
     *               listener. If no listener exists for this type, the
     *               message will be dropped.
     *               DEFAULT: false (message will be attached to available
     *               handler at the time notify() is called).
     * </pre>
     */
    public function push($event, $type = null, array $flags = array(),
                         $options = array())
    {
        if ($event instanceof Horde_Notification_Event) {
            $event->flags = $flags;
            $event->type = $type;
        } else {
            $class = (!is_null($type) && ($listener = $this->get($type)))
                ? $listener->handles($type)
                : $this->_handles['default']['*'];

            /* Transparently create a Horde_Notification_Event object. */
            $event = new $class($event, $type, $flags);
        }

        foreach ($this->_decorators as $decorator) {
            $decorator->push($event, $options);
        }

        if (empty($options['immediate'])) {
            $this->_storage->push('_unattached', $event);
        } else {
            if ($listener = $this->get($event->type)) {
                $this->_storage->push($listener->getName(), $event);
            }
        }
    }

    /**
     * Passes the message stack to all listeners and asks them to
     * handle their messages.
     *
     * @param array $options  An array containing display options for the
     *                        listeners. Any options not contained in this
     *                        list will be passed to the listeners.
     * <pre>
     * 'listeners' - (array) The list of listeners to notify.
     * 'raw' - (boolean) If true, does not call the listener's notify()
     *         function.
     * </pre>
     */
    public function notify(array $options = array())
    {
        /* Convert the 'listeners' option into the format expected by the
         * notification handler. */
        if (!isset($options['listeners'])) {
            $options['listeners'] = array_keys($this->_listeners);
        } elseif (!is_array($options['listeners'])) {
            $options['listeners'] = array($options['listeners']);
        }

        $options['listeners'] = array_map(array('Horde_String', 'lower'), $options['listeners']);

        foreach ($this->_decorators as $decorator) {
            $decorator->notify($options);
        }

        /* Pass the message stack to all listeners and asks them to handle
         * their messages. */
        $unattached = $this->_storage->exists('_unattached')
            ? $this->_storage->get('_unattached')
            : array();

        $events = array();

        foreach ($options['listeners'] as $listener) {
            if (isset($this->_listeners[$listener])) {
                $instance = $this->_listeners[$listener];
                $name = $instance->getName();

                foreach (array_keys($unattached) as $val) {
                    if ($instance->handles($unattached[$val]->type)) {
                        $this->_storage->push($name, $unattached[$val]);
                        unset($unattached[$val]);
                    }
                }

                if (!$this->_storage->exists($name)) {
                    continue;
                }

                $tmp = $this->_storage->get($name);
                if (empty($options['raw'])) {
                    $instance->notify($tmp, $options);
                }
                $this->_storage->clear($name);

                $events = array_merge($events, $tmp);
            }
        }

        if (empty($unattached)) {
            $this->_storage->clear('_unattached');
        } else {
            $this->_storage->set('_unattached', $unattached);
        }

        return $events;
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
        $count = 0;

        if (!is_null($my_listener)) {
            if ($ob = $this->get($my_listener)) {
                $count = count($this->_storage->get($ob->getName()));

                if ($this->_storage->exists('_unattached')) {
                    foreach ($this->_storage->get('_unattached') as $val) {
                        if ($ob->handles($val->type)) {
                            ++$count;
                        }
                    }
                }
            }
        } else {
            if ($this->_storage->exists('_unattached')) {
                $count = count($this->_storage->get('_unattached'));
            }

            foreach ($this->_listeners as $val) {
                if ($this->_storage->exists($val->getName())) {
                    $count += count($this->_storage->get($val->getName()));
                }
            }
        }

        return $count;
    }

}

<?php
/**
 * The Horde_Notification_Listener:: class provides functionality for
 * displaying messages from the message stack as a status line.
 *
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Notification
 */
abstract class Horde_Notification_Listener
{
    /**
     * The base type of this listener.
     *
     * @var string
     */
    protected $_name;

    /**
     * Array of message types that this listener handles.
     *
     * @var array
     */
    protected $_handles = array();

    /**
     * Does this listener handle a certain type of message?
     *
     * @param string $type  The message type in question.
     *
     * @return boolean  Whether this listener handles the type.
     */
    public function handles($type)
    {
        return isset($this->_handles[$type]);
    }

    /**
     * Return a unique identifier for this listener.
     *
     * @return string  Unique id.
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Outputs the status line, sends emails, pages, etc., if there
     * are any messages on this listener's message stack.
     *
     * @param array &$messageStack  The stack of messages.
     * @param array $options        An array of options.
     */
    abstract public function notify(&$messageStacks, $options = array());

    /**
     * Processes one message from the message stack.
     *
     * @param array $message  One message hash from the stack.
     * @param array $options  An array of options.
     *
     * @return mixed  TODO
     */
    abstract public function getMessage($message, $options = array());

    /**
     * Unserialize an event from the message stack, checking to see if the
     * appropriate class exists and kludging it into a base Notification_Event
     * object if not.
     */
    public function getEvent($message)
    {
        $ob = @unserialize($message['event']);
        if (!is_callable(array($ob, 'getMessage'))) {
            if (isset($ob->_message)) {
                $ob = new Horde_Notification_Event($ob->_message);
            }
        }

        /* If we've failed to create a valid Notification_Event object
         * (or subclass object) so far, return a PEAR_Error. */
        if (!is_callable(array($ob, 'getMessage'))) {
            $ob = PEAR::raiseError('Unable to decode message event: ' . $message['event']);
        }

        /* Specially handle PEAR_Error objects and add userinfo if
         * it's there. */
        if (is_callable(array($ob, 'getUserInfo'))) {
            $userinfo = $ob->getUserInfo();
            if ($userinfo) {
                if (is_array($userinfo)) {
                    $userinfo_elts = array();
                    foreach ($userinfo as $userinfo_elt) {
                        if (is_scalar($userinfo_elt)) {
                            $userinfo_elts[] = $userinfo_elt;
                        } elseif (is_object($userinfo_elt)) {
                            if (is_callable(array($userinfo_elt, '__toString'))) {
                                $userinfo_elts[] = $userinfo_elt->__toString();
                            } elseif (is_callable(array($userinfo_elt, 'getMessage'))) {
                                $userinfo_elts[] = $userinfo_elt->getMessage();
                            }
                        }
                    }
                    $userinfo = implode(', ', $userinfo_elts);
                }

                $ob->_message = $ob->getMessage() . ' : ' . $userinfo;
            }
        }

        return $ob;
    }

    /**
     * Unserialize an array of event flags from the message stack.  If this
     * event has no flags, or the flags array could not be unserialized, an
     * empty array is returned.
     *
     * @return array  An array of flags.
     */
    public function getFlags($message)
    {
        /* If this message doesn't have any flags, return an empty
         * array. */
        if (empty($message['flags'])) {
            return array();
        }

        /* Unserialize the flags array from the message. */
        $flags = @unserialize($message['flags']);

        /* If we couldn't unserialize the flags array, return an empty
         * array. */
        if (!is_array($flags)) {
            return array();
        }

        return $flags;
    }

}

<?php
/**
 * Copyright 2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */

/**
 * This is a list of attendees.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */
class Kronolith_Attendee_List
implements ArrayAccess, Countable, IteratorAggregate, Serializable
{
    /**
     * A list of Kronolith_Attendee objects.
     *
     * @var array
     */
    protected $_list = array();

    /**
     * Constructor.
     *
     * @param array $attendees  A list of Kronolith_Attendee objects.
     */
    public function __construct(array $attendees = array())
    {
        $this->_list = $attendees;
    }

    /**
     * Parses a comma separated list of names and e-mail addresses into a list
     * of attendees.
     *
     * @param string $newAttendees                      A comma separated
     *                                                  attendee list.
     * @param Horde_Notification_Handler $notification  A notification handler.
     *
     * @return Kronolith_Attendee_List  The parsed attendee list.
     */
    public static function parse(
        $newAttendees, Horde_Notification_Handler $notification
    )
    {
        $attendees = new self();

        if (empty($newAttendees)) {
            return $attendees;
        }

        /* Parse the address without validation to see what we can get out of
         * it. We allow email addresses (john@example.com), email address with
         * user information (John Doe <john@example.com>), and plain names
         * (John Doe). */
        $parser = new Horde_Mail_Rfc822();
        $result = $parser->parseAddressList($newAttendees);
        $result->setIteratorFilter(Horde_Mail_Rfc822_List::HIDE_GROUPS);

        foreach ($result as $newAttendee) {
            if (!$newAttendee->valid) {
                // If we can't even get a mailbox out of the address, then it
                // is likely unuseable. Reject it entirely.
                $notification->push(
                    sprintf(
                        _("Unable to recognize \"%s\" as an email address."),
                        $newAttendee
                    ),
                    'horde.error'
                );
                continue;
            }

            // If there is only a mailbox part, then it is just a local name.
            if (is_null($newAttendee->host)) {
                $name = $newAttendee->bare_address;
            } else {
                // Build a full email address again and validate it.
                try {
                    $parser->parseAddressList($newAttendee->writeAddress(true));
                } catch (Horde_Mail_Exception $e) {
                    $notification->push($e, 'horde.error');
                    continue;
                }
                $name = $newAttendee->label != $newAttendee->bare_address
                    ? $newAttendee->label
                    : '';
            }

            $attendees->add(new Kronolith_Attendee(array(
                'email'    => $newAttendee->bare_address,
                'role'     => Kronolith::PART_REQUIRED,
                'response' => Kronolith::RESPONSE_NONE,
                'name'     => $name
            )));
        }

        return $attendees;
    }

    /**
     * Adds one or more attendees to this list.
     *
     * @param Kronolith_Attendee|Kronolith_Attendee_List $what  The attendee(s)
     *                                                          to add.
     *
     * @return Kronolith_Attendee_List  This list with the attendee(s) added.
     */
    public function add($what)
    {
        if ($what instanceof self) {
            foreach ($what as $attendee) {
                $this->add($attendee);
            }
            return $this;
        }
        $this->_list[] = $what;
        return $this;
    }

    /**
     * Checks to see whether an attendee exists in this list.
     *
     * This is a case-insensitive version of offsetExists().
     *
     * @param string|Kronolith_Attendee $email  An attendee or the email
     *                                          address of an attendee.
     *
     * @return boolean  True if the specified attendee is present in this list.
     */
    public function has($email)
    {
        if ($email instanceof Kronolith_Attendee) {
            $email = $email->email;
        }
        foreach ($this as $attendee) {
            if ($attendee->match($email, false)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns a copy this list with some attendees removed.
     *
     * @param array $excluded  List of email addresses to remove.
     *
     * @return Kronolith_Attendee_List  A reduced attendee list.
     */
    public function without(array $excluded)
    {
        $list = clone $this;
        foreach (array_flip($excluded) as $email) {
            unset($list[$email]);
        }
        return $list;
    }

    /**
     * Returns a list of email address objects.
     *
     * @return Horde_Mail_Rfc822_List  This list of attendees.
     */
    public function getEmailList()
    {
        $a_list = new Horde_Mail_Rfc822_List();
        foreach ($this as $attendee) {
            $a_list->add($attendee->addressObject);
        }
        return $a_list;
    }

    /**
     */
    public function __toString()
    {
        return implode(', ', iterator_to_array($this->getEmailList()));
    }

    /* Array methods. */

    /**
     */
    public function offsetExists($index)
    {
        return $this[$index] !== false;
    }

    /**
     */
    public function &offsetGet($index)
    {
        foreach ($this->_list as &$attendee) {
            if ($attendee->match($index, true)) {
                return $attendee;
            }
        }
        $attendee = false;
        return $attendee;
    }

    /**
     */
    public function offsetSet($index, $value)
    {
        foreach ($this->_list as $key => $attendee) {
            if ($attendee->match($index, true)) {
                $this->_list[$key] = $value;
                return;
            }
        }
        $this->_list[] = $value;
    }

    /**
     */
    public function offsetUnset($index)
    {
        foreach ($this->_list as $key => $attendee) {
            if (!is_null($attendee->email) && $attendee->email == $index) {
                unset($this->_list[$key]);
                return;
            }
        }
    }

    /* Countable method. */

    public function count()
    {
        return count($this->_list);
    }

    /* Iterator methods. */

    /**
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_list);
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        return serialize($this->_list);
    }

    /**
     */
    public function unserialize($data)
    {
        $this->_list = @unserialize($data);
    }
}

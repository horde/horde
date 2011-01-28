<?php
/**
 * The Horde_Notification_Event:: class defines a single notification event.
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Hans Lellelid <hans@velum.net>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Notification
 */
class Horde_Notification_Event
{
    /**
     * The message being passed.
     *
     * @var string
     */
    public $message = '';

    /**
     * The flags for this message.
     *
     * @var array
     */
    public $flags = array();

    /**
     * The message type.
     *
     * @var string
     */
    public $type;

    /**
     * Constructor.
     *
     * @param mixed $data   Message: either a string or an Exception or
     *                      PEAR_Error object.
     * @param string $type  The event type.
     * @param array $flags  The flag array.
     */
    public function __construct($data, $type = null, array $flags = array())
    {
        $this->flags = $flags;

        $this->type = empty($type)
            ? 'status'
            : $type;

        if ($data instanceof PEAR_Error) {
            // DEPRECATED
            if (($userinfo = $data->getUserInfo()) &&
                  is_array($userinfo)) {
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

                $this->message = $data->getMessage() . ' : ' . implode(', ', $userinfo_elts);
            } else {
                $this->message = $data->getMessage();
            }
        } elseif ($data instanceof Exception) {
            // Exception
            $this->message = $data->getMessage();
        } else {
            // String or object
            $this->message = strval($data);
        }
    }

    /**
     * String representation of this object.
     *
     * @return string  String representation.
     */
    public function __toString()
    {
        return $this->message;
    }

}

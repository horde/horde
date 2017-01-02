<?php
/**
 * Copyright 2016-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */

/**
 * Kronolith_Attendee represents an attendee.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 *
 * @property-read Horde_Mail_Rfc822_Address $addressObject An address object
 *                representation of this attendee.
 * @property-read string $displayName A simple label to identify the attendee.
 * @property-read string $id An ID for this attendee.
 */
class Kronolith_Attendee implements Serializable
{
    /**
     * The attendee's user name.
     *
     * @var string
     */
    public $user;

    /**
     * The attendee's email address.
     *
     * @var string
     */
    public $email;

    /**
     * The attendee's full name.
     *
     * @var string
     */
    public $name;

    /**
     * The attendee's role.
     *
     * One of the Kronolith::PART_* constants
     *
     * @var integer
     */
    public $role;

    /**
     * The attendee's response.
     *
     * One of the Kronolith::RESPONSE_* constants
     *
     * @var integer
     */
    public $response;

    /**
     * Constructor.
     *
     * @param array $params  Attendee properties:
     *  - 'user':       (string) A user name.
     *  - 'email':      (string) The email address of the attendee.
     *  - 'role':       (integer) The role code of the attendee. One of the
     *                  Kronolith::PART_* constants. Default:
     *                  Kronolith::PART_REQUIRED
     *  - 'response':   (integer) The response code of the attendee. One of the
     *                  Kronolith::RESPONSE_* constants. Default:
     *                  Kronolith::RESPONSE_NONE
     *  - 'name':       (string) The name of the attendee.
     *  - 'identities': (Horde_Core_Factory_Identity) An identity factory used
     *                  to complete 'email' and 'name' for 'user' if not
     *                  specified explicitly.
     */
    public function __construct($params)
    {
        $params = array_merge(
            array(
                'user'     => null,
                'email'    => null,
                'role'     => Kronolith::PART_REQUIRED,
                'response' => Kronolith::RESPONSE_NONE,
                'name'     => null
            ),
            $params
        );
        $this->user     = $params['user'];
        $this->email    = $params['email'];
        $this->name     = $params['name'];
        $this->role     = $params['role'];
        $this->response = $params['response'];

        if (isset($this->user) &&
            isset($params['identities']) &&
            !isset($this->name)) {
            $this->name = $params['identities']
                ->create($this->user)
                ->getValue('fullname');
        }
    }

    /**
     * Migrates data from an old attendee structure.
     *
     * @param string $email  The attendee's email address.
     * @param array $data    The attendee data from before Kronolith 5.
     */
    public static function migrate($email, $data)
    {
        if (strpos($email, '@') === false) {
            // "name == email" is how we stored non-email attendees already,
            // but re-assign anyway, to be sure.
            $data['name'] = $email;
            $email = null;
        }
        return new self(array(
            'email'    => $email,
            'name'     => isset($data['name']) ? $data['name'] : null,
            'role'     => $data['attendance'],
            'response' => $data['response']
        ));
    }

    /**
     */
    public function __get($what)
    {
        switch ($what) {
        case 'addressObject':
            $address = new Horde_Mail_Rfc822_Address($this->email);
            if (!empty($this->name)) {
                $address->personal = $this->name;
            }
            return $address;

        case 'displayName':
            if (strlen($this->name)) {
                return $this->name;
            }
            if (strlen($this->user)) {
                return $this->user;
            }
            return $this->email;

        case 'id':
            if (strlen($this->user)) {
                return 'user:' . $this->user;
            }
            if (strlen($this->email)) {
                return 'email:' . $this->email;
            }
            return 'name:' . $this->name;
        }
    }

    /**
     * Returns whether an email address matches this attendee.
     *
     * @param string $email           An email address.
     * @param boolean $caseSensitive  Whether to match case-sensitive.
     *
     * @return boolean  True if the email address matches this attendee.
     */
    public function matchesEmail($email, $caseSensitive)
    {
        $email = new Horde_Mail_Rfc822_Address($email);
        return ($caseSensitive && $email->match($this->email)) ||
            (!$caseSensitive && $email->matchInsensitive($this->email));
    }

    /**
     */
    public function __toString()
    {
        if (strlen($this->email)) {
            return strval($this->addressObject);
        }
        $result = $this->user;
        if (strlen($this->name)) {
            if (strlen($result)) {
                $result = ' (' . $result . ')';
            }
            return $this->name . $result;
        }
        return $result;
    }

    /**
     * Returns a simple object suitable for JSON transport representing this
     * event.
     *
     * @return object  An object respresenting this attendee.
     */
    public function toJson()
    {
        return (object)array(
            'a' => intval($this->role),
            'e' => $this->addressObject->bare_address,
            'i' => $this->id,
            'l' => strval($this->displayName),
            'r' => intval($this->response),
            'u' => $this->user,
        );
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        return serialize(array(
            'u' => $this->user,
            'e' => $this->email,
            'p' => $this->role,
            'r' => $this->response,
            'n' => $this->name,
        ));
    }

    /**
     */
    public function unserialize($data)
    {
        $data = @unserialize($data);
        $this->user     = $data['u'];
        $this->email    = $data['e'];
        $this->role     = $data['p'];
        $this->response = $data['r'];
        $this->name     = $data['n'];
    }
}

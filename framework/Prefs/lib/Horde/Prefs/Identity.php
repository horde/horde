<?php
/**
 * This class provides an interface to all identities a user might have. Its
 * methods take care of any site-specific restrictions configured in prefs.php
 * and conf.php.
 *
 * @todo Remove notification and gettext references.
 *
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_Prefs
 */
class Horde_Prefs_Identity
{
    /**
     * Singleton instances.
     *
     * @var array
     */
    static protected $_instances = array();

    /**
     * Array containing all the user's identities.
     *
     * @var array
     */
    protected $_identities = array();

    /**
     * A pointer to the user's standard identity.
     * This one is used by the methods returning values if no other one is
     * specified.
     *
     * @var integer
     */
    protected $_default = 0;

    /**
     * The user whose identities these are.
     *
     * @var string
     */
    protected $_user = null;

    /**
     * Array containing all of the properties in this identity.
     *
     * @var array
     */
    protected $_properties = array('id', 'fullname', 'from_addr');

    /**
     * The prefs object that this Identity points to.
     *
     * @var Horde_Prefs
     */
    protected $_prefs;

    /**
     * Constructor.
     *
     * Reads all the user's identities from the prefs object or builds a new
     * identity from the standard values given in prefs.php.
     *
     * @param string $user  If specified, we read another user's identities
     *                      instead of the current user.
     */
    public function __construct($user = null)
    {
        $this->_user = is_null($user)
            ? Horde_Auth::getAuth()
            : $user;

        if ((is_null($user) || $user == Horde_Auth::getAuth()) &&
            isset($GLOBALS['prefs'])) {
            $this->_prefs = $GLOBALS['prefs'];
        } else {
            $this->_prefs = Horde_Prefs::singleton($GLOBALS['conf']['prefs']['driver'], $GLOBALS['registry']->getApp(), $user, '', null, false);
            $this->_prefs->retrieve();
        }

        if (!($this->_identities = @unserialize($this->_prefs->getValue('identities', false)))) {
            /* Convert identities from the old format. */
            $this->_identities = @unserialize($this->_prefs->getValue('identities'));
        } elseif (is_array($this->_identities)) {
            $this->_identities = $this->_prefs->convertFromDriver($this->_identities, Horde_Nls::getCharset());
        }

        $this->setDefault($this->_prefs->getValue('default_identity'));
    }

    /**
     * Creates a default identity if none exists yet and sets the preferences
     * up if the identities are locked.
     */
    public function init()
    {
        if (!is_array($this->_identities) || (count($this->_identities) <= 0)) {
            foreach ($this->_properties as $key) {
                $identity[$key] = $this->_prefs->getValue($key);
            }
            if (empty($identity['id'])) {
                $identity['id'] = _("Default Identity");
            }

            $this->_identities = array($identity);
            $this->verify(0);
        }

        if ($this->_prefs->isLocked('default_identity')) {
            foreach ($this->_properties as $key) {
                $value = $this->getValue($key);
                if (is_array($value)) {
                    $value = implode("\n", $value);
                }
                $this->_prefs->setValue($key, $value);
                $this->_prefs->setDirty($key, false);
            }
        }
    }

    /**
     * Saves all identities in the prefs backend.
     */
    public function save()
    {
        $identities = $this->_identities;
        if (is_array($identities)) {
            $identities = $this->_prefs->convertToDriver($identities, Horde_Nls::getCharset());
        }

        $this->_prefs->setValue('identities', serialize($identities), false);
        $this->_prefs->setValue('default_identity', $this->_default);
    }

    /**
     * Adds a new identity to the array of identities.
     *
     * @param array $identity  An identity hash to add.
     *
     * @return integer  The pointer to the created identity
     */
    public function add($identity = array())
    {
        $this->_identities[] = $identity;
        return count($this->_identities) - 1;
    }

    /**
     * Returns a complete identity hash.
     *
     * @param integer $identity  The identity to retrieve.
     *
     * @return array  An identity hash.
     */
    public function get($identity = null)
    {
        if (is_null($identity) || !isset($this->_identities[$identity])) {
            $identity = $this->_default;
        }
        return $this->_identities[$identity];
    }

    /**
     * Removes an identity from the array of identities.
     *
     * @param integer $identity  The pointer to the identity to be removed
     *
     * @return array  The removed identity.
     */
    public function delete($identity)
    {
        $deleted = array_splice($this->_identities, $identity, 1);
        foreach (array_keys($this->_identities) as $id) {
            if ($this->setDefault($id)) {
                break;
            }
        }
        $this->save();

        return $deleted;
    }

    /**
     * Returns a pointer to the current default identity.
     *
     * @return integer  The pointer to the current default identity.
     */
    public function getDefault()
    {
        return $this->_default;
    }

    /**
     * Sets the current default identity.
     * If the identity doesn't exist, the old default identity stays the same.
     *
     * @param integer $identity  The pointer to the new default identity.
     *
     * @return boolean  True on success, false on failure.
     */
    public function setDefault($identity)
    {
        if (isset($this->_identities[$identity])) {
            $this->_default = $identity;
            return true;
        }

        return false;
    }

    /**
     * Returns a property from one of the identities. If this value doesn't
     * exist or is locked, the property is retrieved from the prefs backend.
     *
     * @param string $key        The property to retrieve.
     * @param integer $identity  The identity to retrieve the property from.
     *
     * @return mixed  The value of the property.
     */
    public function getValue($key, $identity = null)
    {
        if (is_null($identity) || !isset($this->_identities[$identity])) {
            $identity = $this->_default;
        }

        return (!isset($this->_identities[$identity][$key]) || $this->_prefs->isLocked($key))
            ? $this->_prefs->getValue($key)
            : $this->_identities[$identity][$key];
    }

    /**
     * Returns an array with the specified property from all existing
     * identities.
     *
     * @param string $key  The property to retrieve.
     *
     * @return array  The array with the values from all identities.
     */
    public function getAll($key)
    {
        $list = array();

        foreach (array_keys($this->_identities) as $identity) {
            $list[$identity] = $this->getValue($key, $identity);
        }

        return $list;
    }

    /**
     * Sets a property with a specified value.
     *
     * @param string $key        The property to set.
     * @param mixed $val         The value to which the property should be
     *                           set.
     * @param integer $identity  The identity to set the property in.
     *
     * @return boolean  True on success, false on failure (property was
     *                  locked).
     */
    public function setValue($key, $val, $identity = null)
    {
        if (is_null($identity)) {
            $identity = $this->_default;
        }

        if (!$this->_prefs->isLocked($key)) {
            $this->_identities[$identity][$key] = $val;
            return true;
        }

        return false;
    }

    /**
     * Returns true if all properties are locked and therefore nothing in the
     * identities can be changed.
     *
     * @return boolean  True if all properties are locked, false otherwise.
     */
    public function isLocked()
    {
        foreach ($this->_properties as $key) {
            if (!$this->_prefs->isLocked($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns true if the given address belongs to one of the identities.
     *
     * @param string $key    The identity key to search.
     * @param string $value  The value to search for in $key.
     *
     * @return boolean  True if the $value was found in $key.
     */
    public function hasValue($key, $valueA)
    {
        $list = $this->getAll($key);

        foreach ($list as $valueB) {
            if (!empty($valueB) &&
                strpos(Horde_String::lower($valueA), Horde_String::lower($valueB)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifies and sanitizes all identity properties.
     *
     * @param integer $identity  The identity to verify.
     *
     * @throws Horde_Exception
     */
    public function verify($identity = null)
    {
        if (is_null($identity)) {
            $identity = $this->_default;
        }

        if (!$this->getValue('id', $identity)) {
            $this->setValue('id', _("Unnamed"), $identity);
        }

        /* RFC 2822 [3.2.5] does not allow the '\' character to be used in the
         * personal portion of an e-mail string. */
        if (strpos($this->getValue('fullname', $identity), '\\') !== false) {
            throw new Horde_Exception('You cannot have the \ character in your full name.');
        }

        /* Prepare email validator */
        require_once 'Horde/Form.php';
        $email = new Horde_Form_Type_email();
        $vars = new Horde_Variables();
        $var = new Horde_Form_Variable('', 'replyto_addr', $email, false);

        /* Verify From address. */
        if (!$email->isValid($var, $vars, $this->getValue('from_addr', $identity), $error_message)) {
            throw new Horde_Exception($error_message);
        }
    }

    /**
     * Returns the user's full name.
     *
     * @param integer $ident  The identity to retrieve the name from.
     *
     * @return string  The user's full name, or the user name if it doesn't
     *                 exist.
     */
    public function getName($ident = null)
    {
        if (isset($this->_names[$ident])) {
            return $this->_names[$ident];
        }

        $this->_names[$ident] = $this->getValue('fullname', $ident);
        if (!strlen($this->_names[$ident])) {
            $this->_names[$ident] = $this->_user;
        }

        return $this->_names[$ident];
    }

    /**
     * Generates the from address to use for the default identity.
     *
     * @param boolean $fullname  Include the fullname information.
     *
     * @return string  The default from address.
     */
    public function getDefaultFromAddress($fullname = false)
    {
        $from_addr = '';

        if ($fullname) {
            $name = $this->getValue('fullname');
            if (!empty($name)) {
                $from_addr = $name . ' ';
            }
        }

        $addr = $this->getValue('from_addr');
        if (empty($addr)) {
            $addr = $this->_user;
            if (empty($from_addr)) {
                return $addr;
            }
        }

        return $from_addr . '<' . $addr . '>';
    }

    /**
     * Sends a message to an email address supposed to be added to the
     * identity.
     * A message is send to this address containing a link to confirm that the
     * address really belongs to that user.
     *
     * @param integer $id       The identity's ID.
     * @param string $old_addr  The old From: address.
     *
     * @return TODO
     * @throws Horde_Mime_Exception
     */
    public function verifyIdentity($id, $old_addr)
    {
        global $conf;

        $hash = base_convert(microtime() . mt_rand(), 10, 36);

        $pref = @unserialize($this->_prefs->getValue('confirm_email', false));
        $pref = $pref
            ? $this->_prefs->convertFromDriver($pref, Horde_Nls::getCharset())
            : array();
        $pref[$hash] = $this->get($id);
        $pref = $this->_prefs->convertToDriver($pref, Horde_Nls::getCharset());
        $this->_prefs->setValue('confirm_email', serialize($pref), false);

        $new_addr = $this->getValue('from_addr', $id);
        $confirm = Horde_Util::addParameter(Horde::url($GLOBALS['registry']->get('webroot', 'horde') . '/services/confirm.php', true, -1), 'h', $hash, false);
        $message = sprintf(_("You have requested to add the email address \"%s\" to the list of your personal email addresses.\n\nGo to the following link to confirm that this is really your address:\n%s\n\nIf you don't know what this message means, you can delete it."),
                           $new_addr,
                           $confirm);

        $msg_headers = new Horde_Mime_Headers();
        $msg_headers->addMessageIdHeader();
        $msg_headers->addUserAgentHeader();
        $msg_headers->addHeader('Date', date('r'));
        $msg_headers->addHeader('To', $new_addr);
        $msg_headers->addHeader('From', $old_addr);
        $msg_headers->addHeader('Subject', _("Confirm new email address"));

        $body = new Horde_Mime_Part();
        $body->setType('text/plain');
        $body->setContents(Horde_String::wrap($message, 76, "\n"));
        $body->setCharset(Horde_Nls::getCharset());

        $body->send($new_addr, $msg_headers, $GLOBALS['injector']->getInstance('Mail'));

        return new Horde_Notification_Event(sprintf(_("A message has been sent to \"%s\" to verify that this is really your address. The new email address is activated as soon as you confirm this message."), $new_addr));
    }

    /**
     * Checks whether an identity confirmation is valid, and adds the
     * validated identity.
     *
     * @param string $hash  The saved hash of the identity being validated.
     *
     * @return array  A message for the user, and the message level.
     */
    public function confirmIdentity($hash)
    {
        $confirm = $this->_prefs->getValue('confirm_email', false);
        if (empty($confirm)) {
            return array(_("There are no email addresses to confirm."), 'horde.message');
        }

        $confirm = @unserialize($confirm);
        if (empty($confirm)) {
            return array(_("There are no email addresses to confirm."), 'horde.message');
        } elseif (!isset($confirm[$hash])) {
            return array(_("Email addresses to confirm not found."), 'horde.message');
        }

        $identity = $this->_prefs->convertFromDriver($confirm[$hash], Horde_Nls::getCharset());
        $id = array_search($identity['id'], $this->getAll('id'));
        if ($id === false) {
            /* Adding a new identity. */
            $verified = array();
            foreach ($identity as $key => $value) {
                if (!$this->_prefs->isLocked($key)) {
                    $verified[$key] = $value;
                }
            }
            $this->add($verified);
        } else {
            /* Updating an existing identity. */
            foreach ($identity as $key => $value) {
                $this->setValue($key, $value, $id);
            }
        }
        $this->save();
        unset($confirm[$hash]);
        $this->_prefs->setValue('confirm_email', serialize($confirm), false);

        return array(sprintf(_("The email address %s has been added to your identities. You can close this window now."), $verified['from_addr']), 'horde.success');
    }

    /**
     * Attempts to return a concrete instance based on $type.
     *
     * @param mixed $driver  The type of concrete Identity subclass to return.
     *                       This is based on the storage driver. The code is
     *                       dynamically included. If $type is an array, then
     *                       we will look in $driver[0]/lib/Prefs/Identity/
     *                       for the subclass implementation named
     *                       $driver[1].php.
     * @param string $user   If specified, we read another user's identities
     *                       instead of the current user.
     *
     * @return Horde_Prefs_Identity  The newly created instance.
     * @throws Horde_Exception
     */
    static public function factory($driver = 'None', $user = null)
    {
        if (is_array($driver)) {
            list($app, $driv_name) = $driver;
            $driver = basename($driv_name);
        } else {
            $driver = basename($driver);
        }

        /* Return a base Identity object if no driver is specified. */
        if (empty($driver) || (strcasecmp($driver, 'none') == 0)) {
            $instance = new self($user);
            $instance->init();
            return $instance;
        }

        $class = (empty($app) ? 'Horde' : $app) . '_Prefs_Identity';

        if (class_exists($class)) {
            $instance = new $class($user);
            $instance->init();
            return $instance;
        }

        throw new Horde_Exception('Class definition of ' . $class . ' not found.');
    }

    /**
     * Attempts to return a reference to a concrete instance based on
     * $type. It will only create a new instance if no instance with
     * the same parameters currently exists.
     *
     * This should be used if multiple types of identities (and, thus,
     * multiple instances) are required.
     *
     * This method must be invoked as:
     *   $var = Horde_Prefs_Identity::singleton()
     *
     * @param mixed $type   The type of concrete subclass to return.
     *                      This is based on the storage driver ($type). The
     *                      code is dynamically included. If $type is an array,
     *                      then we will look in $type[0]/lib/Prefs/Identity/
     *                      for the subclass implementation named
     *                      $type[1].php.
     * @param string $user  If specified, we read another user's identities
     *                      instead of the current user.
     *
     * @return Horde_Prefs_Identity  The concrete reference.
     * @throws Horde_Exception
     */
    static public function singleton($type = 'None', $user = null)
    {
        $signature = hash('md5', serialize(array($type, $user)));
        if (!isset(self::$_instances[$signature])) {
            self::$_instances[$signature] = self::factory($type, $user);
        }

        return self::$_instances[$signature];
    }

}

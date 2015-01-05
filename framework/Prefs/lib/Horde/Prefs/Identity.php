<?php
/**
 * Copyright 2001-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2001-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Prefs
 */

/**
 * This class provides an interface to all identities a user might have.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2001-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Prefs
 */
class Horde_Prefs_Identity
implements ArrayAccess, Countable, IteratorAggregate
{
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
     * Preference names.
     *
     * @var array
     */
    protected $_prefnames = array(
        'default_identity' => 'default_identity',
        'from_addr' => 'from_addr',
        'fullname' => 'fullname',
        'id' => 'id',
        'identities' => 'identities',
        'properties' => array('id', 'fullname', 'from_addr')
    );

    /**
     * The prefs object that this Identity points to.
     *
     * @var Horde_Prefs
     */
    protected $_prefs;

    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     *   - default_identity: (string) The preference name for the default
     *                       identity.
     *                       DEFAULT: 'default_identity'
     *   - from_addr: (string) The preference name for the user's from e-mail
     *                address.
     *                DEFAULT: 'from_addr'
     *   - fullname: (string) The preference name for the user's full name.
     *               DEFAULT: 'fullname'
     *   - id: (string) The preference name for the identity name.
     *         DEFAULT: 'id'
     *   - identities: (string) The preference name for the identity store.
     *                 DEFAULT: 'identities'
     *   - prefs: (Horde_Prefs) [REQUIRED] The prefs object to use.
     *   - properties: (array) The list of properties for the identity.
     *                 DEFAULT: array('from_addr', 'fullname', 'id')
     *   - user: (string) [REQUIRED] The user whose prefs we are handling.
     */
    public function __construct($params = array())
    {
        foreach (array_keys($this->_prefnames) as $val) {
            if (isset($params[$val])) {
                $this->_prefnames[$val] = $params[$val];
            }
        }
        $this->_prefs = $params['prefs'];
        $this->_user = $params['user'];

        if (!($this->_identities = @unserialize($this->_prefs->getValue($this->_prefnames['identities'])))) {
            $this->_identities = $this->_prefs->getDefault($this->_prefnames['identities']);
        }

        $this->setDefault($this->_prefs->getValue($this->_prefnames['default_identity']));
    }

    /**
     * Creates a default identity if none exists yet and sets the preferences
     * up if the identities are locked.
     */
    public function init()
    {
        if (!is_array($this->_identities) || (count($this->_identities) <= 0)) {
            foreach (array_keys($this->_prefnames) as $key) {
                $identity[$key] = $this->_prefs->getValue($key);
            }
            if (empty($identity['id'])) {
                $identity['id'] = Horde_Prefs_Translation::t("Default Identity");
            }

            $this->_identities = array($identity);
            $this->verify(0);
        }
    }

    /**
     * Saves all identities in the prefs backend.
     */
    public function save()
    {
        $this->_prefs->setValue($this->_prefnames['identities'], serialize($this->_identities));
        $this->_prefs->setValue($this->_prefnames['default_identity'], $this->_default);
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
     * @return array  An identity hash. Returns null if the identity does not
     *                exist.
     */
    public function get($identity = null)
    {
        if (is_null($identity)) {
            $identity = $this->_default;
        }

        return isset($this->_identities[$identity])
            ? $this->_identities[$identity]
            : null;
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

        if (!empty($deleted)) {
            foreach (array_keys($this->_identities) as $id) {
                if ($this->setDefault($id)) {
                    break;
                }
            }
            $this->save();
        }

        return reset($deleted);
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
        foreach ($this->_prefnames['properties'] as $key) {
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
    public function hasValue($key, $value)
    {
        $list = $this->getAll($key);

        foreach ($list as $valueB) {
            if (!empty($valueB) &&
                strpos(Horde_String::lower($value), Horde_String::lower($valueB)) !== false) {
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
     * @throws Horde_Prefs_Exception
     */
    public function verify($identity = null)
    {
        if (is_null($identity)) {
            $identity = $this->_default;
        }

        if (!$this->getValue('id', $identity)) {
            $this->setValue('id', Horde_Prefs_Translation::t("Unnamed"), $identity);
        }

        // To verify e-mail, first parse input, than re-parse in verify mode.
        $ob = new Horde_Mail_Rfc822_Address($this->getValue($this->_prefnames['from_addr'], $identity));
        try {
            $rfc822 = new Horde_Mail_Rfc822();
            $rfc822->parseAddressList($ob, array(
                'validate' => true
            ));
        } catch (Horde_Mail_Exception $e) {
            throw new Horde_Prefs_Exception(sprintf(Horde_Prefs_Translation::t("\"%s\" is not a valid email address."), strval($ob)));
        }

        $this->setValue('from_addr', strval($ob), $identity);
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

        $this->_names[$ident] = $this->getValue($this->_prefnames['fullname'], $ident);
        if (!strlen($this->_names[$ident])) {
            $this->_names[$ident] = $this->_user;
        }

        return $this->_names[$ident];
    }

    /**
     * Returns the from address based on the chosen identity.
     *
     * If no address can be found it is built from the current user.
     *
     * @since Horde_Prefs 2.3.0
     *
     * @param integer $ident  The identity to retrieve the address from.
     *
     * @return Horde_Mail_Rfc822_Address  A valid from address.
     */
    public function getFromAddress($ident = null)
    {
        $val = $this->getValue($this->_prefnames['from_addr'], $ident);
        if (!strlen($val)) {
            $val = $this->_user;
        }
        return new Horde_Mail_Rfc822_Address($val);
    }

    /**
     * Generates the from address to use for the default identity.
     *
     * @param boolean $fullname  Include the fullname information.
     *
     * @return Horde_Mail_Rfc822_Address  The default from address (object
     *                                    returned since 2.2.0).
     */
    public function getDefaultFromAddress($fullname = false)
    {
        $ob = new Horde_Mail_Rfc822_Address($this->getFromAddress());
        $ob->personal = $fullname
            ? $this->getValue($this->_prefnames['fullname'])
            : null;

        return $ob;
    }

    /* ArrayAccess methods. */

    /**
     * @since 2.7.0
     */
    public function offsetExists($offset)
    {
        return isset($this->_identities[$offset]);
    }

    /**
     * @since 2.7.0
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @since 2.7.0
     */
    public function offsetSet($offset, $value)
    {
        // $value is ignored.
        $this->set($offset);
    }

    /**
     * @since 2.7.0
     */
    public function offsetUnset($offset)
    {
        $this->delete($offset);
    }

    /* Countable method. */

    /**
     * @since 2.7.0
     */
    public function count()
    {
        return count($this->_identities);
    }

    /* IteratorAggregate method. */

    /**
     * @since 2.7.0
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_identities);
    }

}

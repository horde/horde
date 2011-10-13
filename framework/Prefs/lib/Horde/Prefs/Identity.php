<?php
/**
 * This class provides an interface to all identities a user might have.
 *
 * Copyright 2001-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Prefs
 */
class Horde_Prefs_Identity
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
     * <pre>
     * default_identity: (string) The preference name for the default
     *                   identity.
     *                   DEFAULT: 'default_identity'
     * from_addr: (string) The preference name for the user's from e-mail
     *            address.
     *            DEFAULT: 'from_addr'
     * fullname: (string) The preference name for the user's full name.
     *           DEFAULT: 'fullname'
     * id: (string) The preference name for the identity name.
     *     DEFAULT: 'id'
     * identities: (string) The preference name for the identity store.
     *             DEFAULT: 'identities'
     * prefs: (Horde_Prefs) [REQUIRED] The prefs object to use.
     * properties: (array) The list of properties for the identity.
     *             DEFAULT: array('from_addr', 'fullname', 'id')
     * user: (string) [REQUIRED] The user whose prefs we are handling.
     * </pre>
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

        /* RFC 2822 [3.2.5] does not allow the '\' character to be used in the
         * personal portion of an e-mail string. */
        if (strpos($this->getValue($this->_prefnames['fullname'], $identity), '\\') !== false) {
            throw new Horde_Prefs_Exception('You cannot have the \ character in your full name.');
        }

        try {
            Horde_Mime_Address::parseAddressList($this->getValue($this->_prefnames['from_addr'], $identity), array('validate' => true));
        } catch (Horde_Mime_Exception $e) {
            throw new Horde_Prefs_Exception($e);
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

        $this->_names[$ident] = $this->getValue($this->_prefnames['fullname'], $ident);
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
            $name = $this->getValue($this->_prefnames['fullname']);
            if (!empty($name)) {
                $from_addr = $name . ' ';
            }
        }

        $addr = $this->getValue($this->_prefnames['from_addr']);
        if (empty($addr)) {
            $addr = $this->_user;
        }

        if (empty($from_addr)) {
            return $addr;
        }

        return $from_addr . '<' . $addr . '>';
    }

}

<?php
/**
 * Getting the rootDSE entry of a LDAP server.
 *
 * @category  Horde
 * @package   Ldap
 * @author    Jan Wagner <wagner@netsols.de>
 * @author    Jan Schneider <jan@horde.org>
 * @copyright 2009 Jan Wagner
 * @copyright 2010-2011 The Horde Project
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL
 */
class Horde_Ldap_RootDse
{
    /**
     * @var object Horde_Ldap_Entry
     */
    protected $_entry;

    /**
     * Constructor.
     *
     * Fetches a RootDSE object from an LDAP connection.
     *
     * @param Horde_Ldap $ldap  Directory from which the RootDSE should be
     *                          fetched.
     * @param array      $attrs Array of attributes to search for.
     *
     * @throws Horde_Ldap_Exception
     */
    public function __construct(Horde_Ldap $ldap, $attrs = null)
    {
        if (is_array($attrs) && count($attrs)) {
            $attributes = $attrs;
        } else {
            $attributes = array('vendorName',
                                'vendorVersion',
                                'namingContexts',
                                'altServer',
                                'supportedExtension',
                                'supportedControl',
                                'supportedSASLMechanisms',
                                'supportedLDAPVersion',
                                'subschemaSubentry');
        }
        $referral = $ldap->getOption('LDAP_OPT_REFERRALS');
        $ldap->setOption('LDAP_OPT_REFERRALS', false);
        try {
            $result = $ldap->search('', '(objectClass=*)',
                                    array('attributes' => $attributes,
                                          'scope' => 'base'));
        } catch (Horde_Ldap_Exception $e) {
            $ldap->setOption('LDAP_OPT_REFERRALS', $referral);
            throw $e;
        }
        $ldap->setOption('LDAP_OPT_REFERRALS', $referral);
        $entry = $result->shiftEntry();
        if (!$entry) {
            throw new Horde_Ldap_Exception('Could not fetch RootDSE entry');
        }
        $this->_entry = $entry;
    }

    /**
     * Returns the requested attribute value.
     *
     * @see Horde_Ldap_Entry::getValue()
     *
     * @param string $attr    Attribute name.
     * @param array  $options Array of options.
     *
     * @return string|array Attribute value(s).
     * @throws Horde_Ldap_Exception
     */
    public function getValue($attr, $options = '')
    {
        return $this->_entry->getValue($attr, $options);
    }

    /**
     * Determines if the extension is supported.
     *
     * @param array $oids Array of OIDs to check.
     *
     * @return boolean
     */
    public function supportedExtension($oids)
    {
        return $this->checkAttr($oids, 'supportedExtension');
    }

    /**
     * Determines if the version is supported.
     *
     * @param array $versions Versions to check.
     *
     * @return boolean
     */
    public function supportedVersion($versions)
    {
        return $this->checkAttr($versions, 'supportedLDAPVersion');
    }

    /**
     * Determines if the control is supported.
     *
     * @param array $oids Control OIDs to check.
     *
     * @return boolean
     */
    public function supportedControl($oids)
    {
        return $this->checkAttr($oids, 'supportedControl');
    }

    /**
     * Determines if the sasl mechanism is supported.
     *
     * @param array $mechlist SASL mechanisms to check.
     *
     * @return boolean
     */
    public function supportedSASLMechanism($mechlist)
    {
        return $this->checkAttr($mechlist, 'supportedSASLMechanisms');
    }

    /**
     * Checks for existance of value in attribute.
     *
     * @param array  $values Values to check.
     * @param string $attr   Attribute name.
     *
     * @return boolean
     */
    protected function checkAttr($values, $attr)
    {
        if (!is_array($values)) {
            $values = array($values);
        }

        foreach ($values as $value) {
            if (!in_array($value, $this->get_value($attr, 'all'))) {
                return false;
            }
        }

        return true;
    }
}

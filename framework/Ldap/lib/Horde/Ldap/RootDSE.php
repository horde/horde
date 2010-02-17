<?php
/**
 * File containing the Horde_Ldap_RootDSE interface class.
 *
 * PHP version 5
 *
 * @category  Net
 * @package   Horde_Ldap
 * @author    Jan Wagner <wagner@netsols.de>
 * @copyright 2009 Jan Wagner
 * @license   http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 * @version   SVN: $Id: RootDSE.php 286718 2009-08-03 07:30:49Z beni $
 * @link      http://pear.php.net/package/Horde_Ldap/
 */

/**
 * Includes
 */
#require_once 'PEAR.php';

/**
 * Getting the rootDSE entry of a LDAP server
 *
 * @category Net
 * @package  Horde_Ldap
 * @author   Jan Wagner <wagner@netsols.de>
 * @license  http://www.gnu.org/copyleft/lesser.html LGPL
 * @link     http://pear.php.net/package/Horde_Ldap2/
 */
class Horde_Ldap_RootDSE
{
    /**
     * @access protected
     * @var object Horde_Ldap_Entry
     **/
    protected $_entry;

    /**
     * Class constructor
     *
     * @param Horde_Ldap_Entry &$entry Horde_Ldap_Entry object of the RootDSE
     */
    protected function __construct(&$entry)
    {
        $this->_entry = $entry;
    }

    /**
     * Fetches a RootDSE object from an LDAP connection
     *
     * @param Horde_Ldap $ldap  Directory from which the RootDSE should be fetched
     * @param array     $attrs Array of attributes to search for
     *
     * @access static
     * @return Horde_Ldap_RootDSE
     *
     * @throws Horde_Ldap_Exception
     */
    public static function fetch(Horde_Ldap $ldap, $attrs = null)
    {
        if (is_array($attrs) && count($attrs) > 0 ) {
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
                                'subschemaSubentry' );
        }
        $result = $ldap->search('', '(objectClass=*)', array('attributes' => $attributes, 'scope' => 'base'));
        $entry = $result->shiftEntry();
        if (false === $entry) {
            throw new Horde_Ldap_Exception('Could not fetch RootDSE entry');
        }
        $ret = new Horde_Ldap_RootDSE($entry);
        return $ret;
    }

    /**
     * Gets the requested attribute value
     *
     * Same usuage as {@link Horde_Ldap_Entry::getValue()}
     *
     * @param string $attr    Attribute name
     * @param array  $options Array of options
     *
     * @access public
     * @return mixed Horde_Ldap_Error object or attribute values
     * @see Horde_Ldap_Entry::get_value()
     */
    public function getValue($attr = '', $options = '')
    {
        return $this->_entry->get_value($attr, $options);
    }

    /**
     * Alias function of getValue() for perl-ldap interface
     *
     * @see getValue()
     * @return mixed
     */
    public function get_value()
    {
        $args = func_get_args();
        return call_user_func_array(array( &$this, 'getValue' ), $args);
    }

    /**
     * Determines if the extension is supported
     *
     * @param array $oids Array of oids to check
     *
     * @access public
     * @return boolean
     */
    public function supportedExtension($oids)
    {
        return $this->checkAttr($oids, 'supportedExtension');
    }

    /**
     * Alias function of supportedExtension() for perl-ldap interface
     *
     * @see supportedExtension()
     * @return boolean
     */
    public function supported_extension()
    {
        $args = func_get_args();
        return call_user_func_array(array( &$this, 'supportedExtension'), $args);
    }

    /**
     * Determines if the version is supported
     *
     * @param array $versions Versions to check
     *
     * @access public
     * @return boolean
     */
    public function supportedVersion($versions)
    {
        return $this->checkAttr($versions, 'supportedLDAPVersion');
    }

    /**
     * Alias function of supportedVersion() for perl-ldap interface
     *
     * @see supportedVersion()
     * @return boolean
     */
    public function supported_version()
    {
        $args = func_get_args();
        return call_user_func_array(array(&$this, 'supportedVersion'), $args);
    }

    /**
     * Determines if the control is supported
     *
     * @param array $oids Control oids to check
     *
     * @access public
     * @return boolean
     */
    public function supportedControl($oids)
    {
        return $this->checkAttr($oids, 'supportedControl');
    }

    /**
     * Alias function of supportedControl() for perl-ldap interface
     *
     * @see supportedControl()
     * @return boolean
     */
    public function supported_control()
    {
        $args = func_get_args();
        return call_user_func_array(array(&$this, 'supportedControl' ), $args);
    }

    /**
     * Determines if the sasl mechanism is supported
     *
     * @param array $mechlist SASL mechanisms to check
     *
     * @access public
     * @return boolean
     */
    public function supportedSASLMechanism($mechlist)
    {
        return $this->checkAttr($mechlist, 'supportedSASLMechanisms');
    }

    /**
     * Alias function of supportedSASLMechanism() for perl-ldap interface
     *
     * @see supportedSASLMechanism()
     * @return boolean
     */
    public function supported_sasl_mechanism()
    {
        $args = func_get_args();
        return call_user_func_array(array(&$this, 'supportedSASLMechanism'), $args);
    }

    /**
     * Checks for existance of value in attribute
     *
     * @param array  $values values to check
     * @param string $attr   attribute name
     *
     * @access protected
     * @return boolean
     */
    protected function checkAttr($values, $attr)
    {
        if (!is_array($values)) $values = array($values);

        foreach ($values as $value) {
            if (!@in_array($value, $this->get_value($attr, 'all'))) {
                return false;
            }
        }
        return true;
    }
}

?>

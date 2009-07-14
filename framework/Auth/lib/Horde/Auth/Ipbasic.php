<?php
/**
 * The Horde_Auth_Ipbasic class provides access control based on CIDR masks
 * (client IP addresses). It is not meant for user-based systems, but
 * for times when you want a block of IPs to be able to access a site,
 * and that access is simply on/off - no preferences, etc.
 *
 * Optional Parameters:
 * <pre>
 * 'blocks' - (array) CIDR masks which are allowed access.
 * </pre>
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Auth
 */
class Horde_Auth_Ipbasic extends Horde_Auth_Base
{
    /**
     * An array of capabilities, so that the driver can report which
     * operations it supports and which it doesn't.
     *
     * @var array
     */
    protected $_capabilities = array(
        'transparent' => true
    );

    /**
     * Constructor.
     *
     * @param array $params  A hash containing parameters.
     */
    public function __construct($params = array())
    {
        if (empty($params['blocks'])) {
            $params['blocks'] = array();
        } elseif (!is_array($params['blocks'])) {
            $params['blocks'] = array($params['blocks']);
        }

        parent::__construct($params);
    }

    /**
     * Automatic authentication: Find out if the client matches an allowed IP
     * block.
     *
     * @return boolean  Whether or not the client is allowed.
     * @throws Horde_Exception
     */
    protected function _transparent()
    {
        if (!isset($_SERVER['REMOTE_ADDR'])) {
            throw new Horde_Exception(_("IP Address not avaialble."));
        }

        $client = $_SERVER['REMOTE_ADDR'];
        foreach ($this->_params['blocks'] as $cidr) {
            if ($this->_addressWithinCIDR($client, $cidr)) {
                return Horde_Auth::setAuth($cidr, array('transparent' => 1));
            }
        }

        throw new Horde_Exception(_("IP Address not within allowed CIDR block."));
    }

    /**
     * Determine if an IP address is within a CIDR block.
     *
     * @param string $address  The IP address to check.
     * @param string $cidr     The block (e.g. 192.168.0.0/16) to test against.
     *
     * @return boolean  Whether or not the address matches the mask.
     */
    protected function _addressWithinCIDR($address, $cidr)
    {
        $address = ip2long($address);
        list($quad, $bits) = explode('/', $cidr);
        $bits = intval($bits);
        $quad = ip2long($quad);

        return (($address >> (32 - $bits)) == ($quad >> (32 - $bits)));
    }

}

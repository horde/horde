<?php
/**
 * This class represents a Kolab resource owner.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * This class represents a Kolab resource owner.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Owner_Kolab
extends Horde_Kolab_FreeBusy_UserDb_User_Kolab
implements Horde_Kolab_FreeBusy_Owner
{
    /**
     * The owner information.
     *
     * @var string
     */
    private $_owner;

    /**
     * Additional parameters.
     *
     * @var params
     */
    private $_params;

    /**
     * The owner data retrieved from the user database.
     *
     * @var array
     */
    private $_owner_data;

    /**
     * Is the user locally known?
     *
     * @var boolean
     */
    private $_known;

    /**
     * Constructor.
     *
     * @param string                       $owner  The owner name.
     * @param Horde_Kolab_Server_Composite $db     The connection to the user
     *                                             database.
     * @param array                        $params Additional parameters.
     * <pre>
     *  - user (optional):   A Horde_Kolab_FreeBusy_User object, representing
     *                       the user currently accessing the system. Will
     *                       be used to determine the domain of domain-less
     *                       owners.
     *  - domain (optional): A domain that should be appended to domain-less
     *                       owners.
     * </pre>
     */
    public function __construct(
        $owner, Horde_Kolab_Server_Composite $db, $params = array()
    ) {
        $this->_owner  = $owner;
        $this->_params = $params;
        parent::__construct($db);
    }

    /**
     * Return the original owner parameter.
     *
     * @return string The original owner parameter.
     */
    public function getOwner()
    {
        return $this->_owner;
    }

    /**
     * Is the user known in the user database?
     *
     * @return boolean True if the user data is present.
     */
    public function isKnown()
    {
        if ($this->_known === null) {
            try {
                $this->getUserDbUser();
                $this->_known = true;
            } catch (Horde_Kolab_FreeBusy_Exception $e) {
                $this->_known = false;
            }
        }
        return $this->_known;
    }

    /**
     * Fetch the user data from the user db.
     *
     * @return NULL
     */
    protected function fetchUserDbUser()
    {
        try {
            return $this->fetchOwner($this->_owner);
        } catch (Horde_Kolab_FreeBusy_Exception $e) {
            if (isset($this->_params['user'])) {
                $domain = $this->_params['user']->getDomain();
            } else if (isset($this->_params['domain'])) {
                $domain = $this->_params['domain'];
            } else {
                $domain = false;
            }
            if (!empty($domain)) {
                try {
                    return $this->fetchUserByPrimaryId(
                        $this->_owner . '@' . $domain
                    );
                } catch (Horde_Kolab_FreeBusy_Exception $f) {
                }
            }
            throw $e;
        }
    }
}
<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Dav
 */

use Sabre\DAV;
use Sabre\DAVACL;

/**
 * Backend implementation for listing and managing principals (users and
 * groups).
 *
 * @todo Horde_Group support
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Dav
 */
class Horde_Dav_Principals extends DAVACL\PrincipalBackend\AbstractBackend
{
    /**
     * Authentication backend.
     *
     * @var Horde_Auth_Base
     */
    protected $_auth;

    /**
     * Identity factory.
     *
     * @var object
     */
    protected $_identities;

    /**
     * Constructor.
     *
     * @param Horde_Auth_Base $auth  Authentication backend.
     * @param object $identities     Identity factory.
     */
    public function __construct(Horde_Auth_Base $auth, $identities)
    {
        $this->_auth = $auth;
        $this->_identities = $identities;
    }

    /**
     * Returns a list of principals based on a prefix.
     *
     * @param string $prefixPath
     * @return array
     */
    public function getPrincipalsByPrefix($prefixPath)
    {
        if ($prefixPath != 'principals') {
            throw new DAV\Exception\NotFound('Invalid principal prefix path ' . $prefixPath);
        }
        $users = array($this->_getUserInfo('-system-'));
        if (!$this->_auth->hasCapability('list')) {
            return $users;
        }
        foreach ($this->_auth->listUsers() as $user) {
            $users[] = $this->_getUserInfo($user);
        }
        return $users;
    }

    /**
     * Returns a specific principal, specified by it's path.
     *
     * @param string $path
     * @return array
     */
    public function getPrincipalByPath($path)
    {
        list($prefix, $user) = DAV\URLUtil::splitPath($path);
        if ($prefix != 'principals') {
            throw new DAV\Exception\NotFound('Invalid principal prefix path ' . $prefix);
        }
        if ($this->_auth->hasCapability('list') &&
            !$this->_auth->exists($user) &&
            $user != '-system-') {
            throw new DAV\Exception\NotFound('User ' . $user . ' does not exist');
        }
        return $this->_getUserInfo($user);
    }

    /**
     * Returns principal details.
     *
     * @param string $user  A user name.
     *
     * @return array  A hash with user information.
     */
    protected function _getUserInfo($user)
    {
        if ($user == '-system-') {
            return array(
                'uri' => 'principals/-system-',
                '{DAV:}displayname' => Horde_Dav_Translation::t("System"),
            );
        }

        $identity = $this->_identities->create($user);
        return array(
            'uri' => 'principals/' . $user,
            '{DAV:}displayname' => $identity->getName(),
            '{http://sabredav.org/ns}email-address' => (string)$identity->getDefaultFromAddress()
        );
    }

    /**
     * Updates one ore more webdav properties on a principal.
     *
     * @param string $path
     * @param array $mutations
     * @return array|bool
     */
    public function updatePrincipal($path, $mutations)
    {
        return false;
    }

    /**
     * This method is used to search for principals matching a set of
     * properties.
     *
     * @param string $prefixPath
     * @param array $searchProperties
     * @return array
     */
    public function searchPrincipals($prefixPath, array $searchProperties)
    {
        return array();
    }

    /**
     * Returns the list of members for a group-principal
     *
     * @param string $principal
     * @return array
     */
    public function getGroupMemberSet($principal)
    {
        return array();
    }

    /**
     * Returns the list of groups a principal is a member of
     *
     * @param string $principal
     * @return array
     */
    public function getGroupMembership($principal)
    {
         // All users should have access to the -system- share
         // Which should return only calendars the user sees in Horde.
        return array('principals/-system-');
    }

    /**
     * Updates the list of group members for a group principal.
     *
     * The principals should be passed as a list of uri's.
     *
     * @param string $principal
     * @param array $members
     * @return void
     */
    public function setGroupMemberSet($principal, array $members)
    {
    }
}

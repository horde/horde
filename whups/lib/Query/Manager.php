<?php
/**
 * The whups query manager.
 *
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Robert E. Coyle <robertecoyle@hotmail.com>
 * @author  Jan Schneider <jan@horde.org>
 * @package Whups
 */

class Whups_Query_Manager
{
    /**
     * Horde_Share instance for managing shares.
     *
     * @var Horde_Share
     */
    protected $_shareManager;

    /**
     * Constructor.
     *
     * @TODO: inject the share driver
     */
    public function __construct()
    {
        $this->_shareManager =
            $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')->create();
    }

    /**
     * Returns a specific query identified by its id.
     *
     * @param integer $queryId  A query id.
     *
     * @return Whups_Query  The matching query or null if not found.
     * @throws Whups_Exception
     */
    public function getQuery($queryId)
    {
        try {
            $share = $this->_shareManager->getShareById($queryId);
        } catch (Horde_Exception_NotFound $e) {
            throw new Whups_Exception($e);
        }
        return $this->_getQuery($share);
    }

    /**
     * Returns a specific query identified by its slug name.
     *
     * @param string $slug  A query slug.
     *
     * @return Whups_Query  The matching query or null if not found.
     * @throws Whups_Exception
     */
    public function getQueryBySlug($slug)
    {
        try {
            $shares = $this->_shareManager->listShares(
                $GLOBALS['registry']->getAuth(),
                array('perm' => Horde_Perms::READ,
                      'attributes' => array('slug' => $slug)));
        } catch (Horde_Share_Exception $e) {
            throw new Whups_Exception($e);
        }
        if (!count($shares)) {
            return;
        }

        return $this->_getQuery(reset($shares));
    }

    /**
     * Builds a query object from a share object.
     *
     * @param Horde_Share_Object $share  A share object representing a query.
     *
     * @return Whups_Query  The query object built from the share.
     */
    protected function _getQuery(Horde_Share_Object $share)
    {
        $queryDetails = $GLOBALS['whups_driver']->getQuery($share->getId());
        $queryDetails['query_id'] = $share->getId();
        $queryDetails['query_name'] = $share->get('name');
        $queryDetails['query_slug'] = $share->get('slug');

        return new Whups_Query($this, $queryDetails);
    }

    /**
     * Checks to see if a user has a given permission to $queryId.
     *
     * @param integer $queryId     The query to check.
     * @param string $userid       The userid of the user.
     * @param integer $permission  A Horde_Perms::* constant to test for.
     * @param string $creator      The creator of the event.
     *
     * @return boolean  Whether or not $userid has $permission.
     */
    public function hasPermission($queryId, $userid, $permission, $creator = null)
    {
        try {
            $share = $this->_shareManager->getShareById($queryId);
        } catch (Horde_Exception_NotFound $e) {
            // If the share doesn't exist yet, then it has open perms.
            return true;
        }
        return $share->hasPermission($userid, $permission, $creator);
    }

    /**
     * List queries.
     */
    public function listQueries($user, $return_slugs = false)
    {
        try {
            $shares = $this->_shareManager->listShares($user);
        } catch (Horde_Share_Exception $e) {
            throw new Whups_Exception($e);
        }

        $queries = array();
        foreach ($shares as $share) {
            $queries[$share->getId()] = $return_slugs
                ? array('name' => $share->get('name'),
                        'slug' => $share->get('slug'))
                : $share->get('name');
        }

        return $queries;
    }

    /**
     */
    public function newQuery()
    {
        return new Whups_Query($this);
    }

    /**
     * @param Whups_Query $query The query to save.
     * @throws Whups_Exception
     */
    public function save(Whups_Query $query)
    {
        if ($query->id) {
            // Query already exists; get its share and update the name
            // if necessary.
            try {
                $share = $this->_shareManager->getShareById($query->id);
            } catch (Horde_Exception_NotFound $e) {
                // Share has an id but doesn't exist; just throw an
                // error.
                throw new Whups_Exception($e);
            }
            if ($share->get('name') != $query->name ||
                $share->get('slug') != $query->slug) {
                $share->set('name', $query->name);
                $share->set('slug', $query->slug);
                $share->save();
            }
        } else {
            // Create a new share for the query.
            $share = $this->_shareManager->newShare($GLOBALS['registry']->getAuth(), (string)new Horde_Support_Uuid(), $query->name);
            $share->set('slug', $query->slug);
            try {
                $this->_shareManager->addShare($share);
            } catch (Horde_Share_Exception $e) {
                throw new Whups_Exception($e);
            }
            $query->id = $share->getId();
        }

        // Update the queries table.
        $GLOBALS['whups_driver']->saveQuery($query);
    }

    /**
     * @param Whups_Query $query The query to delete.
     */
    public function delete(Whups_Query $query)
    {
        if (!$query->id) {
            // Queries that aren't saved yet shouldn't be able to be deleted.
            return;
        }

        try {
            $share = $this->_shareManager->getShareById($query->id);
            $this->_shareManager->removeShare($share);
        } catch (Exception $e) {
            throw new Whups_Exception($e);
        }
        $GLOBALS['whups_driver']->deleteQuery($query->id);
    }

}
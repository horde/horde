<?php
/**
 * Turba directory driver implementation for virtual address books.
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/asl.php ASL
 * @package  Turba
 */
class Turba_Driver_Vbook extends Turba_Driver
{
    /**
     * Search type for this virtual address book.
     *
     * @var string
     */
    public $searchType;

    /**
     * The search criteria that defines this virtual address book.
     *
     * @var array
     */
    public $searchCriteria;

    /**
     *
     * @see Turba_Driver::__construct
     * @throws Turba_Exception
     */
    public function __construct($name = '', array $params = array())
    {
        parent::__construct($name, $params);

        /* Grab a reference to the share for this vbook. */
        $this->_share = $this->_params['share'];

        /* Load the underlying driver. */
        $this->_driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($this->_params['source']);

        $this->searchCriteria = empty($this->_params['criteria'])
            ? array()
            : $this->_params['criteria'];
        $this->searchType = (count($this->searchCriteria) > 1)
            ? 'advanced'
            : 'basic';
    }

    /**
     * Return the owner to use when searching or creating contacts in
     * this address book.
     *
     * @return string
     */
    protected function _getContactOwner()
    {
        return $this->_driver->getContactOwner();
    }

    /**
     * Return all entries matching the combined searches represented by
     * $criteria and the vitural address book's search criteria.
     *
     * @param array $criteria  Array containing the search criteria.
     * @param array $fields    List of fields to return
     * @param array $blobFileds  Array of fields that contain
     *
     * @return array  Hash containing the search results.
     * @throws Turba_Exception
     */
    protected function _search(array $criteria, array $fields, array $blobFields = array())
    {
        /* Add the passed in search criteria to the vbook criteria
         * (which need to be mapped from turba fields to
         * driver-specific fields). */
        $criteria['AND'][] = $this->makeSearch($this->searchCriteria, 'AND', array());

        return $this->_driver->_search($criteria, $fields, $blobFields);
    }

    /**
     * Reads the requested entries from the underlying source.
     *
     * @param string $key    The primary key field to use.
     * @param mixed $ids     The ids of the contacts to load.
     * @param string $owner  Only return contacts owned by this user.
     * @param array $fields  List of fields to return.
     *
     * @return array  Hash containing the search results.
     */
    protected function _read($key, $ids, $owner, array $fields)
    {
        return $this->_driver->_read($key, $ids, $owner, $fields);
    }

    /**
     * Not supported for virtual address books.
     *
     * @see Turba_Driver::_add
     * @throws Turba_Exception
     */
    protected function _add(array $attributes)
    {
        throw new Turba_Exception(_("You cannot add new contacts to a virtual address book"));
    }

    /**
     * Not supported for virtual address books.
     *
     * @see Turba_Driver::_delete
     * @throws Turba_Exception
     */
    protected function _delete($object_key, $object_id)
    {
        throw new Turba_Exception(_("You cannot delete contacts from a virtual address book"));
    }

    /**
     * @see Turba_Driver::_save
     */
    protected function _save(Turba_Object $object)
    {
        return $this->_driver->save($object);
    }

    /**
     * Check to see if the currently logged in user has requested permissions.
     *
     * @param integer $perm  The permissions to check against.
     *
     * @return boolean  True or False.
     */
    public function hasPermission($perm)
    {
        return $this->_driver->hasPermission($perm);
    }

}

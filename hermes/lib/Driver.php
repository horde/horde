<?php
/**
 * Hermes_Driver:: defines an API for implementing storage backends
 * for Hermes.
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Hermes
 */
abstract class Hermes_Driver
{
    const SORT_ORDER_ASC = 'ASC';
    const SORT_ORDER_DESC = 'DESC';

    /**
     * Parameters
     *
     * @var array
     */
    protected $_params;

    /**
     * Constructor
     *
     */
    public function __construct($params = array())
    {
        $this->_params = $params;
    }

    /**
     * Retrieve a specific job type record.
     *
     * @param integer $jobTypeID  The ID of the job type.
     *
     * @return array Hash of job type properties.
     * @throws Horde_Exception_NotFound
     */
    public function getJobTypeByID($jobTypeID)
    {
        $jobtypes = $this->listJobTypes(array('id' => $jobTypeID));
        if (!isset($jobtypes[$jobTypeID])) {
            throw new Horde_Exception_NotFound(sprintf(_("No job type with ID \"%s\"."), $jobTypeID));
        }

        return $jobtypes[$jobTypeID];
    }

    /**
     * Retrieve a deliverable by ID.
     *
     * @param integer $deliverableID  The ID of the deliverable to retrieve.
     *
     * @return array Hash of deliverable's properties.
     * @throws Horde_Exception_NotFound
     */
    public function getDeliverableByID($deliverableID)
    {
        $deliverables = $this->listDeliverables(array('id' => $deliverableID));
        if (!isset($deliverables[$deliverableID])) {
            throw new Horde_Exception_NotFound(sprintf(_("Deliverable %d not found."), $deliverableID));
        }

        return $deliverables[$deliverableID];
    }

    /**
     * Add or update a job type record.
     *
     * @param array $jobtype        A hash of job type properties:
     *                  'id'        => The ID of the job, if updating.  If not
     *                                 present, a new job type is created.
     *                  'name'      => The job type's name.
     *                  'enabled'   => Whether the job type is enabled for new
     *                                 time entry.
     *
     * @return The job's ID.
     */
    abstract public function updateJobType($jobtype);

    /**
     * @TODO
     *
     */
    abstract public function deleteJobType($jobTypeID);

    /**
     * Retrieve list of job types.
     *
     * @param array $criteria  Hash of filter criteria:
     *
     *                      'enabled' => If present, only retrieve enabled
     *                                   or disabled job types.
     *
     * @return array Hash of job type.
     */
    abstract public function listJobTypes(array $criteria = array());


    /**
     * Add or update a deliverable.
     *
     * @param array $deliverable    A hash of deliverable properties:
     *                  'id'            => The ID of the deliverable, if
     *                                     updating.  If not present, a new
     *                                     ID is allocated.
     *                  'name'          => The deliverable's display name.
     *                  'client_id'     => The assigned client ID.
     *                  'parent'        => ID of the deliverables parent
     *                                     deliverable (if a child).
     *                  'estimate'      => Estimated number of hours for
     *                                     completion of the deliverable.
     *                  'active'        => Whether this deliverable is active.
     *                  'description'   => Text description (notes) for this
     *                                     deliverable.
     *
     * @return integer  ID of new or saved deliverable.
     */
    abstract public function updateDeliverable($deliverable);

    /**
     * Retrieve list of deliverables.
     *
     * @param array $criteria  A hash of search criteria:
     *              'id'        => If present, only deliverable with
     *                             specified ID is searched for.
     *              'client_id' => If present, list is filtered by
     *                             client ID.
     *
     * @return array Hash of job types.
     */
    abstract public function listDeliverables($criteria = array());

    /**
     * Delete a deliverable.
     *
     * @param integer $deliverableID  The ID of the deliverable.
     *
     * @return void
     */
    abstract public function deleteDeliverable($deliverableID);

    /**
     * @TODO:
     *
     */
    abstract public function markAs($field, $hours);

    /**
     * @TODO
     */
    abstract public function getClientSettings($clientID);

    /**
     * @TODO
     */
    abstract public function updateClientSettings($clientID, $description, $exportId);

    /**
     * @TODO
     */
    abstract public function purge();

    /**
     * Save a row of billing information.
     *
     * @param string $employee  The Horde ID of the person who worked the
     *                          hours.
     * @param array $entries    The billing information to enter. Each array
     *                          row must contain the following entries:
     *             'date'         The day the hours were worked (ISO format)
     *             'client'       The id of the client the work was done for.
     *             'type'         The type of work done.
     *             'hours'        The number of hours worked
     *             'rate'         The hourly rate the work was done at.
     *             'billable'     (optional) Whether or not the work is
     *                            billable hours.
     *             'description'  A short description of the work.
     *
     * @return integer  The new timeslice_id of the newly entered slice
     * @throws Hermes_Exception
     */
    abstract public function enterTime($employee, $info);

    /**
     * Update a set of billing information.
     *
     * @param array $entries  The billing information to enter. Each array row
     *                        must contain the following entries:
     *              'id'           The id of this time entry.
     *              'date'         The day the hours were worked (ISO format)
     *              'client'       The id of the client the work was done for.
     *              'type'         The type of work done.
     *              'hours'        The number of hours worked
     *              'rate'         The hourly rate the work was done at.
     *              'billable'     Whether or not the work is billable hours.
     *              'description'  A short description of the work.
     *
     *                        If any rows contain a 'delete' entry, those rows
     *                        will be deleted instead of updated.
     *
     * @return mixed  boolean
     * @throws Horde_Exception_PermissionDenied
     * @throws Hermes_Exception
     */
    abstract public function updateTime($entries);

    /**
     * Attempts to return a concrete Hermes_Driver instance based on $driver.
     *
     * @param string $driver  The type of concrete Hermes_Driver subclass to
     *                        return.
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return mixed  The newly created concrete Hermes_Driver instance, or
     *                false on error.
     */
    function &factory($driver = null, $params = null)
    {
        if (is_null($driver)) {
            $driver = $GLOBALS['conf']['storage']['driver'];
        }

        $driver = basename($driver);

        if (is_null($params)) {
            $params = Horde::getDriverConfig('storage', $driver);
        }

        $class = 'Hermes_Driver_' . $driver;
        if (class_exists($class)) {
            $hermes = new $class($params);
        } else {
            $hermes = false;
        }

        return $hermes;
    }

    /**
     * Attempts to return a reference to a concrete Hermes_Driver instance
     * based on $driver.
     *
     * It will only create a new instance if no Hermes_Driver instance with the
     * same parameters currently exists.
     *
     * This should be used if multiple storage sources are required.
     *
     * This method must be invoked as: $var = &Hermes_Driver::singleton()
     *
     * @param string $driver  The type of concrete Hermes_Driver subclass to
     *                        return.
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return mixed  The created concrete Hermes_Driver instance, or false on
     *                error.
     */
    function &singleton($driver = null, $params = null)
    {
        static $instances;

        if (is_null($driver)) {
            $driver = $GLOBALS['conf']['storage']['driver'];
        }

        if (is_null($params)) {
            $params = Horde::getDriverConfig('storage', $driver);
        }

        if (!isset($instances)) {
            $instances = array();
        }

        $signature = serialize(array($driver, $params));
        if (!isset($instances[$signature])) {
            $instances[$signature] = &Hermes_Driver::factory($driver, $params);
        }

        return $instances[$signature];
    }

}

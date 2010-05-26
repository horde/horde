<?php
/**
 * Hermes_Driver:: defines an API for implementing storage backends
 * for Hermes.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @since   Hermes 0.1
 * @package Hermes
 */
class Hermes_Driver {

    /**
     * Retrieve a specific job type record.
     *
     * @param integer $jobTypeID            The ID of the job type.
     *
     * @return mixed Hash of job type properties, or PEAR_Error on failure.
     */
    function getJobTypeByID($jobTypeID)
    {
        $jobtypes = $this->listJobTypes(array('id' => $jobTypeID));
        if (is_a($jobtypes, 'PEAR_Error')) {
            return $jobtypes;
        }
        if (!isset($jobtypes[$jobTypeID])) {
            return PEAR::raiseError(sprintf(_("No job type with ID \"%s\"."),
                                            $jobTypeID));
        }
        return $jobtypes[$jobTypeID];
    }

    /**
     * Add or update a job type record.
     *
     * @abstract
     * @param array $jobtype        A hash of job type properties:
     *                  'id'        => The ID of the job, if updating.  If not
     *                                 present, a new job type is created.
     *                  'name'      => The job type's name.
     *                  'enabled'   => Whether the job type is enabled for new
     *                                 time entry.
     *
     * @return mixed The job's ID, or PEAR_Error on failure.
     */
    function updateJobType($jobtype)
    {
        return PEAR::raiseError(_("Not implemented."));
    }

    /**
     * Retrieve list of job types.
     *
     * @abstract
     *
     * @param array $criteria  Hash of filter criteria:
     *
     *                      'enabled' => If present, only retrieve enabled
     *                                   or disabled job types.
     *
     * @return mixed Associative array of job types, or PEAR_Error on failure.
     */
    function listJobTypes($criteria = array())
    {
        return PEAR::raiseError(_("Not implemented."));
    }

    /**
     * Retrieve a deliverable by ID.
     *
     * @param integer $deliverableID        The ID of the deliverable to
     *                                      retrieve.
     * @return mixed Hash of deliverable's properties, or PEAR_Error on
     *               failure.
     */
    function getDeliverableByID($deliverableID)
    {
        $deliverables = $this->listDeliverables(array('id' => $deliverableID));
        if (is_a($deliverables, 'PEAR_Error')) {
            return $deliverables;
        }

        if (!isset($deliverables[$deliverableID])) {
            return PEAR::raiseError(sprintf(_("Deliverable %d not found."),
                                            $deliverableID));
        }

        return $deliverables[$deliverableID];
    }

    /**
     * Add or update a deliverable.
     *
     * @abstract
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
     * @return mixed Integer ID of new or saved deliverable, or PEAR_Error on
     *               failure.
     */
    function updateDeliverable($deliverable)
    {
        return PEAR::raiseError(_("Not implemented."));
    }

    /**
     * Retrieve list of deliverables.
     *
     * @abstract
     *
     * @param array $criteria  A hash of search criteria:
     *              'id'        => If present, only deliverable with
     *                             specified ID is searched for.
     *              'client_id' => If present, list is filtered by
     *                             client ID.
     *
     * @return mixed Associative array of job types, or PEAR_Error on failure.
     */
    function listDeliverables($criteria = array())
    {
        return PEAR::raiseError(_("Not implemented."));
    }

    /**
     * Delete a deliverable.
     *
     * @abstract
     * @param integer $deliverableID        The ID of the deliverable.
     *
     * @return mixed Null, or PEAR_Error on failure.
     */
    function deleteDeliverable($deliverableID)
    {
        return PEAR::raiseError(_("Not implemented."));
    }

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

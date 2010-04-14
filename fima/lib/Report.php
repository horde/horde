<?php
/**
 * Fima_Report:: defines an API for implementing reports for Fima.
 *
 * Copyright 2007-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Thomas Trethan <thomas@trethan.net>
 * @package Fima
 */
class Fima_Report {

    /**
     * Hash containing report parameters.
     *
     * @var array
     */
    var $_params = array();

    /**
     * Array containing the data after execution of the report.
     *
     * @var mixed
     */
    var $_data = array();

    /**
     * Bytes containing the report graph after execution of the report.
     *
     * @var bytes
     */
    var $_graph = null;

    /**
     * Constructor - just store the $params in our newly-created
     * object. All other work is done by initialize().
     *
     * @param array $params  Any parameters needed for this driver.
     */
    function Fima_Report($params = array(), $errormsg = null)
    {
        $this->_params = $params;
        if (is_null($errormsg)) {
            $this->_errormsg = _("The Finances reports are not currently available.");
        } else {
            $this->_errormsg = $errormsg;
        }
    }

    /**
     * Returns a specific report parameter.
     *
     * @param string $param   Paramter to retrieve.
     *
     * @return mixed   Report parameter
     */
    function getParam($param)
    {
        if (isset($this->_params[$param])) {
            return $this->_params[$param];
        } else {
            return null;
        }
    }

    /**
     * Set a specific report parameter.
     *
     * @param string $param   Paramter to set.
     * @param mixed $vale     Value to set paramter to.
     *
     * @return mixed   Report parameter on success, else null
     */
    function setParam($param, $value)
    {
        $this->_params[$param] = $value;
        return $value;
    }

    /*
     * Executes the report.
     *
     * @return mixed   True or PEAR Error
     */
    function execute()
    {
        $execute = $this->_execute();
        if (is_a($execute, 'PEAR_Error')) {
            return $execute;
        }

        /* Log the execution of the report in the history log. */
        $GLOBALS['injector']->getInstance('Horde_History')->log('fima:report:' . $this->_params['report_id'], array('action' => 'execute'), true);

        return true;
    }

    /**
     * Returns the data after report is executed.
     *
     * @return array  Data matrix
     */
    function getData()
    {
        return $this->_data;
    }

    /**
     * Output the graph of this report.
     *
     * @return mixed   True or PEAR Error
     */
    function getGraph()
    {
        $execute = $this->_getGraph();
        if (is_a($execute, 'PEAR_Error')) {
            return $execute;
        }

        require_once FIMA_BASE . '/lib/ReportGraph.php';
        $graph = &Fima_ReportGraph::factory($this->_params['graph'], $this->data, $this->_params);
        if (is_a($graph, 'PEAR_Error')) {
            $notification->push(sprintf(_("There was a problem creating the report graph: %s."), $report->getMessage()), 'horde.error');
            return $graph;
        }

        /* Execute report graph. */
        $graph->execute();
        if (is_a($status, 'PEAR_Error')) {
            $notification->push(sprintf(_("There was a problem executing the report graph: %s."), $status->getMessage()), 'horde.error');
            return $status;
        }

        $graph->getGraph();
        return true;
    }

    /**
     * Initialization of the report.
     *
     * @return boolean  True on success; PEAR_Error on failure.
     */
    function initialize()
    {
    }

    /**
     * Attempts to return a concrete Fima_Report instance based on $driver.
     *
     * @param string    $driver     The type of the concrete Fima_Report subclass
     *                              to return.  The class name is based on the
     *                              storage driver ($driver).  The code is
     *                              dynamically included.
     *
     * @param array     $params     (optional) A hash containing any additional
     *                              configuration or connection parameters a
     *                              subclass might need.
     *
     * @return mixed    The newly created concrete Fima_Driver instance, or
     *					false on an error.
     */
    function &factory($driver = null, $params = null)
    {
        if ($driver === null) {
            $report = new Fima_Report($params, _("No report driver loaded"));
            return $report;
        }

        require_once dirname(__FILE__) . '/Report/' . $driver . '.php';
        $class = 'Fima_Report_' . $driver;
        if (class_exists($class)) {
            $report = new $class($params);
            $result = $report->initialize();
            if (is_a($result, 'PEAR_Error')) {
                $report = new Fima_Report($params, sprintf(_("The Finances reports are not currently available: %s"), $result->getMessage()));
            }
        } else {
            $report = new Fima_Report($params, sprintf(_("Unable to load the definition of %s."), $class));
        }

        return $report;
    }

}

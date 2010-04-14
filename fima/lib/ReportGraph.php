<?php

/**
 * Fima_ReportGraph:: defines an API for implementing report graphs for Fima.
 *
 * Copyright 2007-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Fima
 */

/** PEAR Image Graph */
require_once 'Image/Graph.php';

/**
 * Fima_ReportGraph class.
 *
 * @author  Thomas Trethan <thomas@trethan.net>
 * @package Fima
 */
class Fima_ReportGraph {

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
    var $_plotarea = null;
    var $_legend = null;

    /**
     * Constructor - just store the $params in our newly-created
     * object. All other work is done by initialize().
     *
     * @param array $data    The dataset.
     * @param array $params  Any parameters needed for this driver.
     */
    function Fima_ReportGraph($data = array(), $params = array(), $errormsg = null)
    {
        $this->_data = $data;
        $this->_params = $params;
        if (is_null($errormsg)) {
            $this->_errormsg = _("The Finances report graphs are not currently available.");
        } else {
            $this->_errormsg = $errormsg;
        }
    }

    /*
     * Executes the report.
     *
     * @return mixed   True or PEAR Error
     */
    function execute()
    {
        /* Create graph. */
        $this->_graph =& Image_Graph::factory('graph', array($this->_style['width'], $this->_style['height']));
        $this->_graph->displayErrors();

        /* Add Font. */
        $font =& $this->_graph->addNew('font', $this->_style['font-family']);
        $font->setColor($this->_style['font-color']);
        $font->setSize($this->_style['font-size']);
        $this->_graph->setFont($font);

        /* Plot and Legend. */
        $title =& Image_Graph::factory('title', array(isset($this->_params['title']) ? $this->_params['title'] : _("Report"), $this->_style['header-size']));
        $title->setAlignment(IMAGE_GRAPH_ALIGN_BOTTOM | IMAGE_GRAPH_ALIGN_CENTER_X);
        $subtitle =& Image_Graph::factory('title', array(isset($this->_params['subtitle']) ? $this->_params['subtitle'] : '', $this->_style['subheader-size']));
        $this->_plotarea =& Image_Graph::factory('plotarea');
        $this->_legend =& Image_Graph::factory('legend');
        $this->_graph->add(
            Image_Graph::vertical(
                Image_Graph::vertical(
                    $title,
                    $subtitle,
                    60
                ),
                Image_Graph::vertical(
                    $this->_plotarea,
                    $this->_legend,
                    88
                ),
                10
            )
        );
        $this->_legend->setPlotarea($this->_plotarea);
        $this->_legend->setAlignment(IMAGE_GRAPH_ALIGN_CENTER);

        /* Execute. */
        $execute = $this->_execute();
        if (is_a($execute, 'PEAR_Error')) {
            return $execute;
        }

        /* Log the execution of the report in the history log. */
        $GLOBALS['injector']->getInstance('Horde_History')->log('fima:reportgraph', array('action' => 'execute'), true);

        return true;
    }

    /**
     * Returns the graph of this report (if any).
     *
     * @return bytes   Image data
     */
    function getGraph()
    {
        header('Expires: Mon, 01 Jan 1970 00:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Pragma: public');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: pre-check=0, post-check=0, max-age=0');
        header('Content-Type: image/jpeg');
        header('Accept-Ranges: bytes');
        #header('Content-Length: ' . );
        header('Content-Disposition: inline');

        $this->_graph->done();

        return true;
    }

    /**
     * Initialization of the report.
     *
     * @return boolean  True on success; PEAR_Error on failure.
     */
    function initialize()
    {
        /* Load styles. */
        include_once($GLOBALS['registry']->get('themesfs') . '/report.inc');
        $this->_style = $style;
    }

    /**
     * Attempts to return a concrete Fima_ReportGraph instance based on $driver.
     *
     * @param string    $driver     The type of the concrete Fima_ReportGraph subclass
     *                              to return.  The class name is based on the
     *                              storage driver ($driver).  The code is
     *                              dynamically included.
     *
     * @param array     $data       The dataset.
     * @param array     $params     (optional) A hash containing any additional
     *                              configuration or connection parameters a
     *                              subclass might need.
     *
     * @return mixed    The newly created concrete Fima_Driver instance, or
     *					false on an error.
     */
    function &factory($driver = null, $data = null, $params = null)
    {
        if ($driver === null) {
            $report = new Fima_ReportGraph($data, $params, _("No report driver loaded"));
            return $report;
        }

        require_once dirname(__FILE__) . '/ReportGraph/' . $driver . '.php';
        $class = 'Fima_ReportGraph_' . $driver;
        if (class_exists($class)) {
            $report = new $class($data, $params);
            $result = $report->initialize();
            if (is_a($result, 'PEAR_Error')) {
                $report = new Fima_ReportGraph($data, $params, sprintf(_("The Finances report graphs are not currently available: %s"), $result->getMessage()));
            }
        } else {
            $report = new Fima_ReportGraph($data, $params, sprintf(_("Unable to load the definition of %s."), $class));
        }

        return $report;
    }

}

<?php
/**
 * Fima_ReportGraph_Bar.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Fima
 */

/** Fima_Report */
require_once FIMA_BASE . '/lib/ReportGraph.php';

/*
 * Fima_ReportGraph_Bar class.
 *
 * @author  Thomas Trethan <thomas@trethan.net>
 * @package Fima
 */
class Fima_ReportGraph_Bar extends Fima_ReportGraph {

    /*
     * Constructs a new Bar ReportGraph.
     */
    function Fima_ReportGraph_Bar($data = array(), $params = array())
    {
        $this->_data = $data;
        $this->_params = $params;

        if (!isset($this->_params['invert'])) {
            $this->_params['invert'] = false;
        }
    }

    /*
     * Executes the report graph.
     *
     * @return mixed   True or PEAR Error
     */
    function _execute()
    {
        /* Grid. */
        $grid =& $this->_plotarea->addNew('line_grid');
        $gridfill =& Image_Graph::factory('Image_Graph_Fill_Array');
        $gridfill->addColor($this->_style['grid']);
        $grid->setFillStyle($gridfill);

        /* Datasets. */
        $datasets = array();
        $datasetindex = array();
        $ix = 0;
        foreach ($this->_data as $rowId => $row) {
            foreach ($row as $colId => $value) {
                $xd = $this->_params['invert'] ? $rowId : $colId;
                $xx = $this->_params['invert'] ? $colId : $rowId;
                if (!isset($datasetindex[$xd])) {
                    $datasetindex[$xd] = $ix++;
                    $datasets[$datasetindex[$xd]] =& Image_Graph::factory('dataset');
                    $datasets[$datasetindex[$xd]]->setName($this->_params['labels'][$xd]);
                }
                $datasets[$datasetindex[$xd]]->addPoint($this->_params['labels'][$xx], $value);
            }
        }

        $plot =& $this->_plotarea->addNew('bar', $params['stacked'] ? array($datasets, 'stacked') : array($datasets));
        $plot->setLineColor($this->_style['line']);

        /* Fill style. */
        $fill =& Image_Graph::factory('Image_Graph_Fill_Array');
        foreach ($datasetindex as $key => $value) {
            if (isset($this->_style[$key])) {
                $fill->addColor($this->_style[$key]);
            } else {
                $fill->addColor($this->_style['color' . $value]);
            }
        }
        $plot->setFillStyle($fill);

        /* Axis. */
        $axisx =& $this->_plotarea->getAxis(IMAGE_GRAPH_AXIS_X);
        $axisy =& $this->_plotarea->getAxis(IMAGE_GRAPH_AXIS_Y);
        $axisy->showLabel(IMAGE_GRAPH_LABEL_ZERO);
        $axisy->setDataPreprocessor(Image_Graph::factory('Image_Graph_DataPreprocessor_Function', create_function('$value', 'return Fima::convertValueToAmount($value);')));

        return true;
    }

}

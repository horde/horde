<?php
/**
 * Fima_ReportGraph_Line.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Fima
 */

/** Fima_Report */
require_once FIMA_BASE . '/lib/ReportGraph.php';

/*
 * Fima_ReportGraph_Line class.
 *
 * @author  Thomas Trethan <thomas@trethan.net>
 * @package Fima
 */
class Fima_ReportGraph_Line extends Fima_ReportGraph {

    /*
     * Constructs a new Line ReportGraph.
     */
    function Fima_ReportGraph_Line($data = array(), $params = array())
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

        /* Line style. */
        foreach ($datasetindex as $key => $value) {
          $plot =& $this->_plotarea->addNew('line', $datasets[$value]);
          $plot->setLineColor($this->_style['line']);

          $line =& Image_Graph::factory('Image_Graph_Line_Solid', isset($this->_style[$key]) ? $this->_style[$key] : $this->_style['color' . $value]);
          $line->setThickness(2);
          $plot->setLineStyle($line);
        }

        /* Axis. */
        $axisx =& $this->_plotarea->getAxis(IMAGE_GRAPH_AXIS_X);
        $axisx->setFontAngle('vertical');
        $axisx->setLabelOption('offset', -20);
        $axisy =& $this->_plotarea->getAxis(IMAGE_GRAPH_AXIS_Y);
        $axisy->showLabel(IMAGE_GRAPH_LABEL_ZERO);
        $axisy->setDataPreprocessor(Image_Graph::factory('Image_Graph_DataPreprocessor_Function', create_function('$value', 'return Fima::convertValueToAmount($value);')));

        return true;
    }

}

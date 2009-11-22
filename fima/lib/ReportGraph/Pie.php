<?php
/**
 * Fima_ReportGraph_Pie.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Fima
 */

/** Fima_Report */
require_once FIMA_BASE . '/lib/ReportGraph.php';

/*
 * Fima_ReportGraph_Pie class.
 *
 * @author  Thomas Trethan <thomas@trethan.net>
 * @package Fima
 */
class Fima_ReportGraph_Pie extends Fima_ReportGraph {

    /*
     * Constructs a new Pie ReportGraph.
     */
    function Fima_ReportGraph_Pie($data = array(), $params = array())
    {
        $this->_data = $data;
        $this->_params = $params;
    }

    /*
     * Executes the report graph.
     *
     * @return mixed   True or PEAR Error
     */
    function _execute()
    {
        /* Datasets. */
        $datasets = array();
        $datasetindex = array();
        $x = 0;
        foreach ($this->_data as $ix => $dataset) {
            $datasets[$ix] =& Image_Graph::factory('dataset');
            foreach ($dataset as $key => $value) {
                $datasetindex[$x++] = $key;
                $datasets[$ix]->addPoint($this->_params['labels'][$key], $value);
            }
        }
        $plot =& $this->_plotarea->addNew('pie', array($datasets));
        $plot->setLineColor($this->_style['line']);

        /* Fill style. */
        $fill =& Image_Graph::factory('Image_Graph_Fill_Array');
        foreach ($datasetindex as $key => $value) {
            if (isset($this->_style[$value])) {
                $fill->addColor($this->_style[$value]);
            } else {
                $fill->addColor($this->_style['color' . $key]);
            }
        }
        $plot->setFillStyle($fill); 

        /* Axis. */
        $this->_plotarea->hideAxis();  
        
        /* Explode. */
        if (isset($this->_params['explode'])) {
            if (is_int($this->_params['explode']) || is_array($this->_params['explode'])) {
                $plot->explode($this->_params['explode']);
            } else {
                $plot->explode(10, $this->_params['explode']);
            } 
        }
        
        /* Marker. */
        if ($this->_params['marker']) {
            $marker =& $plot->addNew('Image_Graph_Marker_Value', IMAGE_GRAPH_PCT_Y_TOTAL);
            $marker->setDataPreprocessor(Image_Graph::factory('Image_Graph_DataPreprocessor_Formatted', '%0.1f%%'));
            $marker->setFontSize($this->_style['font-size']);
            $pointingmarker =& $plot->addNew('Image_Graph_Marker_Pointing_Angular', array(30, &$marker));
            $plot->setMarker($pointingmarker);
        }

        return true;
    }
    
}

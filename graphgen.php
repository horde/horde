<?php
/**
 * $Horde: incubator/operator/graphgen.php,v 1.4 2008/07/05 17:19:59 bklang Exp $
 *
 * Copyright 2008 The Horde Project <http://www.horde.org>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Ben Klang <ben@alkaloid.net>
 */

@define('OPERATOR_BASE', dirname(__FILE__));
require_once OPERATOR_BASE . '/lib/base.php';

// Load PEAR's Image_Graph library
require_once 'Image/Graph.php';

$graphtype = Util::getFormData('graph');
$graphInfo = Operator::getGraphInfo($graphtype);
$cachekey = Util::getFormData('key');

$stats = unserialize($cache->get($cachekey, 0));






// Create the graph image base.
$graph =& Image_Graph::factory('graph', array(700, 500));

if (!empty($conf['ttf_font'])) {
    // add a TrueType font
    $Font =& $graph->addNew('ttf_font', $conf['ttf_font']);
    // set the font size to 11 pixels
    $Font->setSize(8);
    $graph->setFont($Font);
}
 
 
// create the plotarea layout
$graph->add(
    Image_Graph::vertical(
        Image_Graph::factory('title', array($graphInfo['title'], 11)),
        #Image_Graph::vertical(
            $plotarea = Image_Graph::factory('plotarea'),
        #    $legend = Image_Graph::factory('legend'),
        #    90
        #),
        5
    )
);         
 
// make the legend use the plotarea (or implicitly it's plots)
#$legend->setPlotarea($plotarea);   
 
// create a grid and assign it to the secondary Y axis
$gridY2 =& $plotarea->addNew('line_grid', IMAGE_GRAPH_AXIS_Y_SECONDARY);  
$gridY2->setLineColor('black');
#$gridY2->setFillStyle(
#    Image_Graph::factory(
#        'gradient', 
#        array(IMAGE_GRAPH_GRAD_HORIZONTAL, 'white', 'lightgrey')
#    )
#);    
 
$dataset1 = Image_Graph::factory('dataset');
foreach ($stats as $month => $stats) {
    $dataset1->addPoint($month, $stats[$graphtype]);
}
$plot1 =& $plotarea->addNew('bar', $dataset1);
$plot1->setLineColor('black@0.5');
$plot1->setFillColor('blue@0.2');
$plot1->setTitle('Primary Axis');
 
// create an area plot using a random dataset
#$dataset2 =& Image_Graph::factory('random', array(8, 1, 10, true)); 
#$plot2 =& $plotarea->addNew(
#    'Image_Graph_Plot_Area', 
#    $dataset2, 
#    IMAGE_GRAPH_AXIS_Y_SECONDARY
#);
 
#$plot2->setLineColor('gray');
#$plot2->setFillColor('blue@0.2');
#$plot2->setTitle('Secondary Axis');
 
$axisX =& $plotarea->getAxis(IMAGE_GRAPH_AXIS_X);
$axisX->setTitle($graphInfo['axisX']);
$axisY =& $plotarea->getAxis(IMAGE_GRAPH_AXIS_Y);
$axisY->setTitle($graphInfo['axisY'], 'vertical'); 
#$axisYsecondary =& $plotarea->getAxis(IMAGE_GRAPH_AXIS_Y_SECONDARY);
#$axisYsecondary->setTitle('Pears', 'vertical2'); 
 
// output the Graph
$graph->done();

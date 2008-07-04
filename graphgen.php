<?php
/**
 * $Horde: incubator/operator/graphgen.php,v 1.2 2008/07/04 04:23:16 bklang Exp $
 *
 * Copyright 2008 Alkaloid Networks LLC <http://projects.alkaloid.net>
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
$graphname = Operator::getGraphName(Util::getFormData('name'));
$cachekey = Util::getFormData('key');

$stats = unserialize($cache->get($cachekey, 0));
Horde::logMessage(print_r($stats, true), __FILE__, __LINE__, PEAR_LOG_ERR);
$graph = Image_Graph::factory('graph', array(600, 400));
$plotarea = $graph->addNew('plotarea');
#$graph->add(Image_Graph::vertical(
#    Image_Graph::factory('title', array($graphname, 11)),
#    Image_Graph::vertical(
#        $plotarea = $graph->addNew('plotarea'),
#        $legend = Image_Graph::factory('legend'),
#        90
#    ),
#    5
#));
#$legend->setPlotArea($plotarea);
$grid =& $plotarea->addNew('bar_grid', IMAGE_GRAPH_AXIS_Y_SECONDARY);
$grid->setFillStyle(Image_Graph::factory(
    'gradient',
    array(IMAGE_GRAPH_GRAD_VERTICAL, 'white', 'lightgrey')
));
$dataset = Image_Graph::factory('dataset');
foreach ($stats as $month => $stats) {
    $dataset->addPoint($month, $stats[$graphtype]);
}
$plot = $plotarea->addNew('bar', $dataset);
$graph->done();


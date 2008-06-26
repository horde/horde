<?php
/**
 * $Horde: incubator/operator/viewgraph.php,v 1.2 2008/06/26 18:30:03 bklang Exp $
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

// Form libraries.
require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';
require_once 'Horde/Variables.php';
require_once OPERATOR_BASE . '/lib/Form/SearchCDR.php';

// Load PEAR's Image_Graph library
require_once 'Image/Graph.php';

$renderer = new Horde_Form_Renderer();
$vars = Variables::getDefaultVariables();

$startdate = array('year' => 2007,
                   'month' => 1,
                   'mday' => 1);
$enddate = array('year' => date('Y'),
                 'month' => date('n'),
                 'mday' => date('j'));

$startdate = new Horde_Date($startdate);
$enddate = new Horde_Date($enddate);
$accountcode = null;
$dcontext = null;

$stats = $operator_driver->getCallStats($startdate, $enddate, $accountcode, $dcontext);

$graph = Image_Graph::factory('graph', array(600, 400));
$plotarea = $graph->addNew('plotarea');
$dataset = Image_Graph::factory('dataset');
foreach ($stats as $month => $stats) {
    $dataset->addPoint($month, $stats['numcalls']);
}
$plot = $plotarea->addNew('bar', $dataset);
$graph->done();


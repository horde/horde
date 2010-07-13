<?php
/**
 * Copyright 2008-2010 The Horde Project <http://www.horde.org>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Ben Klang <ben@alkaloid.net>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
$operator = Horde_Registry::appInit('operator');

$cache = &$GLOBALS['cache'];

// Work around warnings in Image_Graph
// Needed for Image_Graph <= 0.7.2 and Image_Canvas <= 0.3.2
//error_reporting(E_NONE);
//ini_set("display_errors", 0);

//setlocale(LC_ALL, $registry->preferredLang());
//setlocale(LC_ALL, 'en_US');

$graphtype = Horde_Util::getFormData('graph');
$graphinfo = Operator::getGraphInfo($graphtype);
$cachekey = Horde_Util::getFormData('key');

$stats = unserialize($cache->get($cachekey, 0));

// Create the graph image base.
if (empty($graphinfo['imageX'])) {
    $graphinfo['imageX'] = 700;
}
if (empty($graphinfo['imageY'])) {
    $graphinfo['imageY'] = 600;
}
if (!isset($graphinfo['charttype'])) {
    $graphinfo['charttype'] = 'bar';
}
if (!isset($graphinfo['markers'])) {
    $graphinfo['markers'] = true;
}
if (!isset($graphinfo['legendsplit'])) {
    $graphinfo['legendsplit'] = 90;
}

$canvas =& Image_Canvas::factory('png', array('width' => $graphinfo['imageX'],
                                              'height' => $graphinfo['imageY'],
                                              'antialias' => true));
$graph =& Image_Graph::factory('graph', $canvas);

if (isset($graphinfo['orientation']) &&
    $graphinfo['orientation'] == 'horizontal') {
    $graph->horizontal = true;
} else {
    $graph->horizontal = false;
}

if (!empty($conf['graph']['ttf_font'])) {
    // add a TrueType font
    $Font =& $graph->addNew('ttf_font', $conf['graph']['ttf_font']);
    // Set the font size to 11 pixels.  Yes, 8 really does mean 11
    $Font->setSize(8);
    $graph->setFont($Font);
}

// create the plotarea layout
if ($graph->horizontal) {
    $plotarea = Image_Graph::factory('plotarea',
                                      array('Image_Graph_Axis_Category',
                                            'Image_Graph_Axis', 'horizontal'));
} else {
    $plotarea = Image_Graph::factory('plotarea',
                                      array('Image_Graph_Axis_Category',
                                            'Image_Graph_Axis',
                                            'vertical'));
}

$graph->add(
    Image_Graph::vertical(
        Image_Graph::factory('title', array($graphinfo['title'], 11)),
        Image_Graph::vertical(
            $plotarea,
            $legend = Image_Graph::factory('legend'),
            $graphinfo['legendsplit']
        ),
        5
    )
);

$plotarea->setAxisPadding(array('top' => 20));

// make the legend use the plotarea (or implicitly its plots)
$legend->setPlotarea($plotarea);

// create a grid and assign it to the secondary Y axis
$gridY2 =& $plotarea->addNew('line_grid', IMAGE_GRAPH_AXIS_Y_SECONDARY);
#$gridY2->setLineColor('black');
#$gridY2->setFillStyle(
#    Image_Graph::factory(
#        'gradient',
#        array(IMAGE_GRAPH_GRAD_HORIZONTAL, 'white', 'lightgrey')
#    )
#);

$linecolor = 0x000042;
$increment = 0x173147;
foreach ($stats[$graphtype] as $title => $data) {
    $lcstring = sprintf('#%06x', $linecolor);
    $linecolor += $increment;
    if ($linecolor >= 0x5555555) {
        $linecolor = $linecolor & 0xFFFFFF;
        $linecolor += $increment;
    }

    if ($graph->horizontal) {
        // Horizontal graphs reverse the data points
        $data = array_reverse($data, true);
    }

    $dataset = Image_Graph::factory('dataset');
    foreach ($data as $expr => $value) {
        $dataset->addPoint($expr, $value);
    }
    $plot =& $plotarea->addNew($graphinfo['charttype'], $dataset);
    $plot->setLineColor($lcstring);
    $plot->setFillColor($lcstring . '@0.5');
    //$plot->setFillColor('blue@0.2');
    $plot->setTitle($title);

    if ($graphinfo['markers']) {
        $marker =& $plot->addNew('Image_Graph_Marker_Value', IMAGE_GRAPH_VALUE_Y);
        // create a pin-point marker type
        if ($graph->horizontal) {
            $PointingMarker =& $plot->addNew('Image_Graph_Marker_Pointing', array(-37, 0, $marker));
        } else {
            $PointingMarker =& $plot->addNew('Image_Graph_Marker_Pointing', array(0, -7, $marker));
        }
        $PointingMarker->setLineColor(false);
        $marker->setBorderColor(false);
        $marker->setFillColor(false);
        // and use the marker on the 1st plot
        $plot->setMarker($PointingMarker);

        #if (!empty($graphinfo['numberformat'])) {
        #    $marker->setDataPreprocessor(Image_Graph::factory('Image_Graph_DataPreprocessor_Formatted', $graphinfo['numberformat']));
        #}
        $marker->setDataPreprocessor(Image_Graph::factory('Image_Graph_DataPreprocessor_Function', '_format'));
        $marker->setFontSize(7.5);
    }
}

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
$axisY =& $plotarea->getAxis(IMAGE_GRAPH_AXIS_Y);
if ($graph->horizontal) {
    $axisX->setTitle($graphinfo['axisX'], 'vertical');
    $axisY->setTitle($graphinfo['axisY'], 'horizontal');
} else {
    $axisX->setTitle($graphinfo['axisX'], 'horizontal');
    $axisY->setTitle($graphinfo['axisY'], 'vertical');
}
$axisY->setDataPreprocessor(Image_Graph::factory('Image_Graph_DataPreprocessor_Function', 'axis2human'));
#$axisYsecondary =& $plotarea->getAxis(IMAGE_GRAPH_AXIS_Y_SECONDARY);
#$axisYsecondary->setTitle('Pears', 'vertical2');

// output the Graph
$graph->done();
exit;

function _format($number)
{
    // Only show the decimal if the value has digits after the decimal
    if (($number - (int)$number) == 0) {
        return money_format('%!.0n', $number);
    } else {
        return money_format('%!.2n', $number);
    }
}

function number2human($number, $showCurrency = true)
{
    $currency = '';
    $suffix = '';
    if ($showCurrency) {
        // FIXME: Make currency configurable
        //$currency = 'ISK ';
        $currency = '';
    }

    if (abs($number) >= 500000000) {
        $number = $number / 1000000000;
        $suffix = 'T';
    }
    if (abs($number) >= 500000) {
        $number = $number / 1000000;
        $suffix = 'M';
    }
    if (abs($number) >= 500) {
        $number = $number / 1000;
        $suffix = 'K';
    }
    return $currency . _format($number) . $suffix;
}

function axis2human($number)
{
    return number2human($number, false);
}

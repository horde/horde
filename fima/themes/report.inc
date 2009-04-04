<?php

$graphsize = explode('x', $GLOBALS['prefs']->getValue('report_graphsize'));

$style = array('width' => $graphsize[0],
               'height' => $graphsize[1],
               
               'font-family' => 'Verdana',
               'font-size' => '8',
               'font-color' => '#000000',
               'header-size' => '14',
               'subheader-size' => '11',
               
               'line' => '#888888',
               'grid' => '#eeeeee',
               
               'color0' => '#ffff99',
               'color1' => '#ccffcc',
               'color2' => '#99ccff',
               'color3' => '#ffcc99',
               'color4' => '#ff99cc',
               'color5' => '#ccffff',
               'color6' => '#cc99ff',
               'color7' => '#ffffcc',
               'color8' => '#00ff00',
               'color9' => '#3366ff',
               'color10' => '#ffcc00',
               'color11' => '#ff00ff',
               'color12' => '#00ffff',
               'color13' => '#993366',
               'color14' => '#99cc00',
               'color15' => '#339966',
               'color16' => '#333399',
               'color17' => '#ff6600',
               'color18' => '#ff0000',
               'color19' => '#33cccc',
               'color20' => '#800080',
               'color21' => '#808000',
               'color22' => '#008000',
               'color23' => '#000080',
               'color24' => '#993300',
               'color25' => '#800000',
               'color26' => '#008080',
               'color27' => '#666699',
               'color28' => '#c0c0c0',
               'color29' => '#808080',
               'color30' => '#333333',
               
               'asset' => '#f3f3f3',
               'income0' => '#ccffcc',
               'income1' => '#99ee99',
               'incometotal' => '#33cc33',
               'expense0' => '#ffcccc',
               'expense1' => '#ffaaaa',
               'expensetotal' => '#ff6666',
               
               'actual' => '#ffe448', // yellow
               'forecast' => '#4f8bc6', // blue
               'budget' => '#ee8800', // orange
               'reference' => '#db5ffd', // purple
               
               'difference' => '#cccccc', // grey
               '%' => '#999999', // light grey
               
               '__resulttotal__' => '#888888',
               '__rest__' => '#888888',
               '__rest1__' => '#888888',
               '__blank__' => '#ffffff',
               );

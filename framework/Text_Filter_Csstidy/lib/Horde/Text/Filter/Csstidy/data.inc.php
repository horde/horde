<?php
/**
 * Various CSS Data for CSSTidy
 *
 * This file is part of CSSTidy.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Florian Schmitz (floele at gmail dot com) 2005
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Text_Filter_Csstidy
 */

define('AT_START',    1);
define('AT_END',      2);
define('SEL_START',   3);
define('SEL_END',     4);
define('PROPERTY',    5);
define('VALUE',       6);
define('COMMENT',     7);
define('DEFAULT_AT', 41);

/**
 * All whitespace allowed in CSS
 *
 * @global array $GLOBALS['csstidy']['whitespace']
 * @version 1.0
 */
$GLOBALS['csstidy']['whitespace'] = array(' ',"\n","\t","\r","\x0B");

/**
 * All CSS tokens used by csstidy
 *
 * @global string $GLOBALS['csstidy']['tokens']
 * @version 1.0
 */
$GLOBALS['csstidy']['tokens'] = '/@}{;:=\'"(,\\!$%&)*+.<>?[]^`|~';

/**
 * All CSS units (CSS 3 units included)
 *
 * @see compress_numbers()
 * @global array $GLOBALS['csstidy']['units']
 * @version 1.0
 */
$GLOBALS['csstidy']['units'] = array('in','cm','mm','pt','pc','px','rem','em','%','ex','gd','vw','vh','vm','deg','grad','rad','ms','s','khz','hz');

/**
 * Available at-rules
 *
 * @global array $GLOBALS['csstidy']['at_rules']
 * @version 1.0
 */
$GLOBALS['csstidy']['at_rules'] = array(
    'page' => 'is',
    'font-face' => 'is',
    'charset' => 'iv',
    'import' => 'iv',
    'namespace' => 'iv',
    'media' => 'at'
);

 /**
 * Properties that need a value with unit
 *
 * @todo CSS3 properties
 * @see compress_numbers();
 * @global array $GLOBALS['csstidy']['unit_values']
 * @version 1.2
 */
$GLOBALS['csstidy']['unit_values'] = array(
    'background', 'background-position', 'border', 'border-top',
    'border-right', 'border-bottom', 'border-left', 'border-width',
    'border-top-width', 'border-right-width', 'border-left-width',
    'border-bottom-width', 'bottom', 'border-spacing', 'font-size', 'height',
    'left', 'margin', 'margin-top', 'margin-right', 'margin-bottom',
    'margin-left', 'max-height', 'max-width', 'min-height', 'min-width',
    'outline-width', 'padding', 'padding-top', 'padding-right',
    'padding-bottom', 'padding-left', 'position', 'right', 'top',
    'text-indent', 'letter-spacing', 'word-spacing', 'width'
);

/**
 * Properties that allow <color> as value
 *
 * @todo CSS3 properties
 * @see compress_numbers();
 * @global array $GLOBALS['csstidy']['color_values']
 * @version 1.0
 */
$GLOBALS['csstidy']['color_values'] = array(
    'background-color', 'border-color', 'border-top-color',
    'border-right-color', 'border-bottom-color', 'border-left-color', 'color',
    'outline-color'
);

/**
 * Default values for the background properties
 *
 * @todo Possibly property names will change during CSS3 development
 * @global array $GLOBALS['csstidy']['background_prop_default']
 * @see dissolve_short_bg()
 * @see merge_bg()
 * @version 1.0
 */
$GLOBALS['csstidy']['background_prop_default'] = array(
    'background-image' => 'none',
    'background-size' => 'auto',
    'background-repeat' => 'repeat',
    'background-position' => '0 0',
    'background-attachment' => 'scroll',
    'background-clip' => 'border',
    'background-origin' => 'padding',
    'background-color' => 'transparent'
);

/**
 * A list of non-W3C color names which get replaced by their hex-codes
 *
 * @global array $GLOBALS['csstidy']['replace_colors']
 * @see cut_color()
 * @version 1.0
 */
$GLOBALS['csstidy']['replace_colors'] = array(
    'aliceblue' => '#F0F8FF',
    'antiquewhite' => '#FAEBD7',
    'aquamarine' => '#7FFFD4',
    'azure' => '#F0FFFF',
    'beige' => '#F5F5DC',
    'bisque' => '#FFE4C4',
    'blanchedalmond' => '#FFEBCD',
    'blueviolet' => '#8A2BE2',
    'brown' => '#A52A2A',
    'burlywood' => '#DEB887',
    'cadetblue' => '#5F9EA0',
    'chartreuse' => '#7FFF00',
    'chocolate' => '#D2691E',
    'coral' => '#FF7F50',
    'cornflowerblue' => '#6495ED',
    'cornsilk' => '#FFF8DC',
    'crimson' => '#DC143C',
    'cyan' => '#00FFFF',
    'darkblue' => '#00008B',
    'darkcyan' => '#008B8B',
    'darkgoldenrod' => '#B8860B',
    'darkgray' => '#A9A9A9',
    'darkgreen' => '#006400',
    'darkkhaki' => '#BDB76B',
    'darkmagenta' => '#8B008B',
    'darkolivegreen' => '#556B2F',
    'darkorange' => '#FF8C00',
    'darkorchid' => '#9932CC',
    'darkred' => '#8B0000',
    'darksalmon' => '#E9967A',
    'darkseagreen' => '#8FBC8F',
    'darkslateblue' => '#483D8B',
    'darkslategray' => '#2F4F4F',
    'darkturquoise' => '#00CED1',
    'darkviolet' => '#9400D3',
    'deeppink' => '#FF1493',
    'deepskyblue' => '#00BFFF',
    'dimgray' => '#696969',
    'dodgerblue' => '#1E90FF',
    'feldspar' => '#D19275',
    'firebrick' => '#B22222',
    'floralwhite' => '#FFFAF0',
    'forestgreen' => '#228B22',
    'gainsboro' => '#DCDCDC',
    'ghostwhite' => '#F8F8FF',
    'gold' => '#FFD700',
    'goldenrod' => '#DAA520',
    'greenyellow' => '#ADFF2F',
    'honeydew' => '#F0FFF0',
    'hotpink' => '#FF69B4',
    'indianred' => '#CD5C5C',
    'indigo' => '#4B0082',
    'ivory' => '#FFFFF0',
    'khaki' => '#F0E68C',
    'lavender' => '#E6E6FA',
    'lavenderblush' => '#FFF0F5',
    'lawngreen' => '#7CFC00',
    'lemonchiffon' => '#FFFACD',
    'lightblue' => '#ADD8E6',
    'lightcoral' => '#F08080',
    'lightcyan' => '#E0FFFF',
    'lightgoldenrodyellow' => '#FAFAD2',
    'lightgrey' => '#D3D3D3',
    'lightgreen' => '#90EE90',
    'lightpink' => '#FFB6C1',
    'lightsalmon' => '#FFA07A',
    'lightseagreen' => '#20B2AA',
    'lightskyblue' => '#87CEFA',
    'lightslateblue' => '#8470FF',
    'lightslategray' => '#778899',
    'lightsteelblue' => '#B0C4DE',
    'lightyellow' => '#FFFFE0',
    'limegreen' => '#32CD32',
    'linen' => '#FAF0E6',
    'magenta' => '#FF00FF',
    'mediumaquamarine' => '#66CDAA',
    'mediumblue' => '#0000CD',
    'mediumorchid' => '#BA55D3',
    'mediumpurple' => '#9370D8',
    'mediumseagreen' => '#3CB371',
    'mediumslateblue' => '#7B68EE',
    'mediumspringgreen' => '#00FA9A',
    'mediumturquoise' => '#48D1CC',
    'mediumvioletred' => '#C71585',
    'midnightblue' => '#191970',
    'mintcream' => '#F5FFFA',
    'mistyrose' => '#FFE4E1',
    'moccasin' => '#FFE4B5',
    'navajowhite' => '#FFDEAD',
    'oldlace' => '#FDF5E6',
    'olivedrab' => '#6B8E23',
    'orangered' => '#FF4500',
    'orchid' => '#DA70D6',
    'palegoldenrod' => '#EEE8AA',
    'palegreen' => '#98FB98',
    'paleturquoise' => '#AFEEEE',
    'palevioletred' => '#D87093',
    'papayawhip' => '#FFEFD5',
    'peachpuff' => '#FFDAB9',
    'peru' => '#CD853F',
    'pink' => '#FFC0CB',
    'plum' => '#DDA0DD',
    'powderblue' => '#B0E0E6',
    'rosybrown' => '#BC8F8F',
    'royalblue' => '#4169E1',
    'saddlebrown' => '#8B4513',
    'salmon' => '#FA8072',
    'sandybrown' => '#F4A460',
    'seagreen' => '#2E8B57',
    'seashell' => '#FFF5EE',
    'sienna' => '#A0522D',
    'skyblue' => '#87CEEB',
    'slateblue' => '#6A5ACD',
    'slategray' => '#708090',
    'snow' => '#FFFAFA',
    'springgreen' => '#00FF7F',
    'steelblue' => '#4682B4',
    'tan' => '#D2B48C',
    'thistle' => '#D8BFD8',
    'tomato' => '#FF6347',
    'turquoise' => '#40E0D0',
    'violet' => '#EE82EE',
    'violetred' => '#D02090',
    'wheat' => '#F5DEB3',
    'whitesmoke' => '#F5F5F5',
    'yellowgreen' => '#9ACD32'
);

/**
 * A list of all shorthand properties that are devided into four properties and/or have four subvalues
 *
 * @global array $GLOBALS['csstidy']['shorthands']
 * @todo Are there new ones in CSS3?
 * @see dissolve_4value_shorthands()
 * @see merge_4value_shorthands()
 * @version 1.0
 */
$GLOBALS['csstidy']['shorthands'] = array(
    'border-color' => array(
        'border-top-color', 'border-right-color', 'border-bottom-color',
        'border-left-color'
    ),
    'border-style' => array(
        'border-top-style', 'border-right-style', 'border-bottom-style',
        'border-left-style'
    ),
    'border-width' => array(
        'border-top-width', 'border-right-width', 'border-bottom-width',
        'border-left-width'
    ),
    'margin' => array(
        'margin-top', 'margin-right', 'margin-bottom', 'margin-left'
    ),
    'padding' => array(
        'padding-top', 'padding-right', 'padding-bottom', 'padding-left'
    ),
    '-moz-border-radius' => array(
        '-moz-border-radius-topright', '-moz-border-radius-bottomright',
        '-moz-border-radius-bottomleft', '-moz-border-radius-topleft'
    ),
    '-webkit-border-radius' => array(
        '-webkit-border-top-right-radius', '-webkit-border-bottom-right-radius',
        '-webkit-border-bottom-left-radius', '-webkit-border-top-left-radius'
    ),
    // CSS3
    'border-radius' => array(
        'border-top-right-radius', 'border-bottom-right-radius',
        'border-bottom-left-radius', 'border-top-left-radius'
    ),
);

/**
 * All CSS Properties. Needed for csstidy::property_is_next()
 *
 * @global array $GLOBALS['csstidy']['all_properties']
 * @todo Add CSS3 properties
 * @version 1.0
 * @see csstidy::property_is_next()
 */
$GLOBALS['csstidy']['all_properties'] = array(
    'background' => 'CSS1.0,CSS2.0,CSS2.1',
    'background-color' => 'CSS1.0,CSS2.0,CSS2.1',
    'background-image' => 'CSS1.0,CSS2.0,CSS2.1',
    'background-repeat' => 'CSS1.0,CSS2.0,CSS2.1',
    'background-attachment' => 'CSS1.0,CSS2.0,CSS2.1',
    'background-position' => 'CSS1.0,CSS2.0,CSS2.1',
    'border' => 'CSS1.0,CSS2.0,CSS2.1',
    'border-top' => 'CSS1.0,CSS2.0,CSS2.1',
    'border-right' => 'CSS1.0,CSS2.0,CSS2.1',
    'border-bottom' => 'CSS1.0,CSS2.0,CSS2.1',
    'border-left' => 'CSS1.0,CSS2.0,CSS2.1',
    'border-color' => 'CSS1.0,CSS2.0,CSS2.1',
    'border-top-color' => 'CSS2.0,CSS2.1',
    'border-bottom-color' => 'CSS2.0,CSS2.1',
    'border-left-color' => 'CSS2.0,CSS2.1',
    'border-right-color' => 'CSS2.0,CSS2.1',
    'border-style' => 'CSS1.0,CSS2.0,CSS2.1',
    'border-top-style' => 'CSS2.0,CSS2.1',
    'border-right-style' => 'CSS2.0,CSS2.1',
    'border-left-style' => 'CSS2.0,CSS2.1',
    'border-bottom-style' => 'CSS2.0,CSS2.1',
    'border-width' => 'CSS1.0,CSS2.0,CSS2.1',
    'border-top-width' => 'CSS1.0,CSS2.0,CSS2.1',
    'border-right-width' => 'CSS1.0,CSS2.0,CSS2.1',
    'border-left-width' => 'CSS1.0,CSS2.0,CSS2.1',
    'border-bottom-width' => 'CSS1.0,CSS2.0,CSS2.1',
    'border-collapse' => 'CSS2.0,CSS2.1',
    'border-spacing' => 'CSS2.0,CSS2.1',
    'bottom' => 'CSS2.0,CSS2.1',
    'caption-side' => 'CSS2.0,CSS2.1',
    'content' => 'CSS2.0,CSS2.1',
    'clear' => 'CSS1.0,CSS2.0,CSS2.1',
    'clip' => 'CSS1.0,CSS2.0,CSS2.1',
    'color' => 'CSS1.0,CSS2.0,CSS2.1',
    'counter-reset' => 'CSS2.0,CSS2.1',
    'counter-increment' => 'CSS2.0,CSS2.1',
    'cursor' => 'CSS2.0,CSS2.1',
    'empty-cells' => 'CSS2.0,CSS2.1',
    'display' => 'CSS1.0,CSS2.0,CSS2.1',
    'direction' => 'CSS2.0,CSS2.1',
    'float' => 'CSS1.0,CSS2.0,CSS2.1',
    'font' => 'CSS1.0,CSS2.0,CSS2.1',
    'font-family' => 'CSS1.0,CSS2.0,CSS2.1',
    'font-style' => 'CSS1.0,CSS2.0,CSS2.1',
    'font-variant' => 'CSS1.0,CSS2.0,CSS2.1',
    'font-weight' => 'CSS1.0,CSS2.0,CSS2.1',
    'font-stretch' => 'CSS2.0',
    'font-size-adjust' => 'CSS2.0',
    'font-size' => 'CSS1.0,CSS2.0,CSS2.1',
    'height' => 'CSS1.0,CSS2.0,CSS2.1',
    'left' => 'CSS1.0,CSS2.0,CSS2.1',
    'line-height' => 'CSS1.0,CSS2.0,CSS2.1',
    'list-style' => 'CSS1.0,CSS2.0,CSS2.1',
    'list-style-type' => 'CSS1.0,CSS2.0,CSS2.1',
    'list-style-image' => 'CSS1.0,CSS2.0,CSS2.1',
    'list-style-position' => 'CSS1.0,CSS2.0,CSS2.1',
    'margin' => 'CSS1.0,CSS2.0,CSS2.1',
    'margin-top' => 'CSS1.0,CSS2.0,CSS2.1',
    'margin-right' => 'CSS1.0,CSS2.0,CSS2.1',
    'margin-bottom' => 'CSS1.0,CSS2.0,CSS2.1',
    'margin-left' => 'CSS1.0,CSS2.0,CSS2.1',
    'marks' => 'CSS1.0,CSS2.0',
    'marker-offset' => 'CSS2.0',
    'max-height' => 'CSS2.0,CSS2.1',
    'max-width' => 'CSS2.0,CSS2.1',
    'min-height' => 'CSS2.0,CSS2.1',
    'min-width' => 'CSS2.0,CSS2.1',
    'overflow' => 'CSS1.0,CSS2.0,CSS2.1',
    'orphans' => 'CSS2.0,CSS2.1',
    'outline' => 'CSS2.0,CSS2.1',
    'outline-width' => 'CSS2.0,CSS2.1',
    'outline-style' => 'CSS2.0,CSS2.1',
    'outline-color' => 'CSS2.0,CSS2.1',
    'padding' => 'CSS1.0,CSS2.0,CSS2.1',
    'padding-top' => 'CSS1.0,CSS2.0,CSS2.1',
    'padding-right' => 'CSS1.0,CSS2.0,CSS2.1',
    'padding-bottom' => 'CSS1.0,CSS2.0,CSS2.1',
    'padding-left' => 'CSS1.0,CSS2.0,CSS2.1',
    'page-break-before' => 'CSS1.0,CSS2.0,CSS2.1',
    'page-break-after' => 'CSS1.0,CSS2.0,CSS2.1',
    'page-break-inside' => 'CSS2.0,CSS2.1',
    'page' => 'CSS2.0',
    'position' => 'CSS1.0,CSS2.0,CSS2.1',
    'quotes' => 'CSS2.0,CSS2.1',
    'right' => 'CSS2.0,CSS2.1',
    'size' => 'CSS1.0,CSS2.0',
    'speak-header' => 'CSS2.0,CSS2.1',
    'table-layout' => 'CSS2.0,CSS2.1',
    'top' => 'CSS1.0,CSS2.0,CSS2.1',
    'text-indent' => 'CSS1.0,CSS2.0,CSS2.1',
    'text-align' => 'CSS1.0,CSS2.0,CSS2.1',
    'text-decoration' => 'CSS1.0,CSS2.0,CSS2.1',
    'text-shadow' => 'CSS2.0',
    'letter-spacing' => 'CSS1.0,CSS2.0,CSS2.1',
    'word-spacing' => 'CSS1.0,CSS2.0,CSS2.1',
    'text-transform' => 'CSS1.0,CSS2.0,CSS2.1',
    'white-space' => 'CSS1.0,CSS2.0,CSS2.1',
    'unicode-bidi' => 'CSS2.0,CSS2.1',
    'vertical-align' => 'CSS1.0,CSS2.0,CSS2.1',
    'visibility' => 'CSS1.0,CSS2.0,CSS2.1',
    'width' => 'CSS1.0,CSS2.0,CSS2.1',
    'widows' => 'CSS2.0,CSS2.1',
    'z-index' => 'CSS1.0,CSS2.0,CSS2.1',
    /* Speech */
    'volume' => 'CSS2.0,CSS2.1',
    'speak' => 'CSS2.0,CSS2.1',
    'pause' => 'CSS2.0,CSS2.1',
    'pause-before' => 'CSS2.0,CSS2.1',
    'pause-after' => 'CSS2.0,CSS2.1',
    'cue' => 'CSS2.0,CSS2.1',
    'cue-before' => 'CSS2.0,CSS2.1',
    'cue-after' => 'CSS2.0,CSS2.1',
    'play-during' => 'CSS2.0,CSS2.1',
    'azimuth' => 'CSS2.0,CSS2.1',
    'elevation' => 'CSS2.0,CSS2.1',
    'speech-rate' => 'CSS2.0,CSS2.1',
    'voice-family' => 'CSS2.0,CSS2.1',
    'pitch' => 'CSS2.0,CSS2.1',
    'pitch-range' => 'CSS2.0,CSS2.1',
    'stress' => 'CSS2.0,CSS2.1',
    'richness' => 'CSS2.0,CSS2.1',
    'speak-punctuation' => 'CSS2.0,CSS2.1',
    'speak-numeral' => 'CSS2.0,CSS2.1',
);

/**
 * An array containing all predefined templates.
 *
 * @global array $GLOBALS['csstidy']['predefined_templates']
 * @version 1.0
 * @see csstidy::load_template()
 */
$GLOBALS['csstidy']['predefined_templates'] = array(
    'default' => array(
        '<span class="at">', //string before @rule
        '</span> <span class="format">{</span>'."\n", //bracket after @-rule
        '<span class="selector">', //string before selector
        '</span> <span class="format">{</span>'."\n", //bracket after selector
        '<span class="property">', //string before property
        '</span><span class="value">', //string after property+before value
        '</span><span class="format">;</span>'."\n", //string after value
        '<span class="format">}</span>', //closing bracket - selector
        "\n\n", //space between blocks {...}
        "\n".'<span class="format">}</span>'. "\n\n", //closing bracket @-rule
        '', //indent in @-rule
        '<span class="comment">', // before comment
        '</span>'."\n", // after comment
        "\n", // after last line @-rule
    ),

    'high_compression' => array(
        '<span class="at">',
        '</span> <span class="format">{</span>'."\n",
        '<span class="selector">',
        '</span><span class="format">{</span>',
        '<span class="property">',
        '</span><span class="value">',
        '</span><span class="format">;</span>',
        '<span class="format">}</span>',
        "\n",
        "\n". '<span class="format">}'."\n".'</span>',
        '',
        '<span class="comment">', // before comment
        '</span>', // after comment
        "\n",
    ),

    'highest_compression' => array(
        '<span class="at">',
        '</span><span class="format">{</span>',
        '<span class="selector">',
        '</span><span class="format">{</span>',
        '<span class="property">',
        '</span><span class="value">',
        '</span><span class="format">;</span>',
        '<span class="format">}</span>',
        '',
        '<span class="format">}</span>',
        '',
        '<span class="comment">', // before comment
        '</span>', // after comment
        '',
    ),

    'low_compression' => array(
        '<span class="at">',
        '</span> <span class="format">{</span>'."\n",
        '<span class="selector">',
        '</span>'."\n".'<span class="format">{</span>'."\n",
        '	<span class="property">',
        '</span><span class="value">',
        '</span><span class="format">;</span>'."\n",
        '<span class="format">}</span>',
        "\n\n",
        "\n".'<span class="format">}</span>'."\n\n",
        '	',
        '<span class="comment">', // before comment
        '</span>'."\n", // after comment
        "\n",
    )
);

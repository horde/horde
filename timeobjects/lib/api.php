<?php
/**
 * API methods for exposing various bits of data via the listTimeObjects API
 */
$_services['listTimeObjectCategories'] = array(
    'type' => '{urn:horde}stringArray'
);

$_services['listTimeObjects'] = array(
    'args' => array('categories' => '{urn:horde}stringArray', 'start' => 'int', 'end' => 'int'),
    'type' => '{urn:horde}hashHash'
);

// @TODO: Probably implement a URL endpoint or something so we can link
//        to the correct external site depending on what time object category
//        we are referring to.
$_services['show'] = array(
    'link' => '#',
);

/**
 * Returns the available categories we provide.
 *
 * Right now, only providing weather data.
 *
 * @return array
 */
function _timeobjects_listTimeObjectCategories()
{
    return array('weather' => _("Weather"));
}

/**
 * Obtain the timeObjects for the requested category
 *
 * @param array $time_categories  An array of categories to list
 * @param mixed $start            The start of the time period to list for
 * @param mixed $end              The end of the time period to list for
 *
 * @return An array of timeobject arrays.
 */
function _timeobjects_listTimeObjects($time_categories, $start, $end)
{
    require_once dirname(__FILE__) . '/base.php';

    $return = array();
    foreach ($time_categories as $category) {
        $drv = TimeObjects_Driver::factory($category);
        $new = $drv->listTimeObjects($start, $end);
        $return = array_merge($return, $new);
    }

    return $return;
}
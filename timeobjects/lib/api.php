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
    require_once dirname(__FILE__) . '/base.php';

    // @TODO: Probably want to iterate the driver directory
    //        and dynamically build this list and/or maybe provide
    //        a $conf[] setting to explicitly disable certain drivers?
    $drv = TimeObjects_Driver::factory('Weatherdotcom');
    if ($drv->ensure()) {
        return array('Weatherdotcom' => _("Weather"));
    } else {
        return array();
    }
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

        try {
            $new = $drv->listTimeObjects($start, $end);
        } catch (TimeObjects_Exception $e) {
            //@TODO: Log the error,  but return an empty array.
            $new = array();
        }
        $return = array_merge($return, $new);
    }

    return $return;
}
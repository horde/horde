<?php
/**
 * API methods for exposing various bits of data via the listTimeObjects API
 */
class Timeobjects_Api extends Horde_Registry_Api
{
    /**
     * Links.
     *
     * @var array
     */
    public $links = array(
        // @TODO: Probably implement a URL endpoint or something so we can
        // link to the correct external site depending on what time object
        // category we are referring to.
        'show' => '#'
    );

    /**
     * Returns the available categories we provide.
     *
     * Right now, only providing weather data.
     *
     * @return array
     */
    public function listTimeObjectCategories()
    {
        // @TODO: Probably want to iterate the driver directory
        //        and dynamically build this list and/or maybe provide
        //        a $conf[] setting to explicitly disable certain drivers?
        $drivers = array();

        $drv = TimeObjects_Driver::factory('Weatherdotcom');
        if ($drv->ensure()) {
            $drivers['Weatherdotcom'] = _("Weather");
        }

        $drv = TimeObjects_Driver::factory('FacebookEvents');
        if ($drv->ensure()) {
            $drivers['FacebookEvents'] = _("Facebook Events");
        }

        return $drivers;
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
    public function listTimeObjects($time_categories, $start, $end)
    {
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

}

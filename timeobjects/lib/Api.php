<?php
/**
 * API methods for exposing various bits of data via the listTimeObjects API
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package TimeObjects
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
     * @return array  An array of available TimeObject categories.
     */
    public function listTimeObjectCategories()
    {
        // @TODO: Probably want to iterate the driver directory
        //        and dynamically build this list and/or maybe provide
        //        a $conf[] setting to explicitly disable certain drivers?
        $drivers = array();

        try {
            $drv = $GLOBALS['injector']->getInstance('TimeObjects_Factory_Driver')->create('Weatherdotcom');
            if ($drv->ensure()) {
               $drivers['Weatherdotcom'] = _("Weather");
            }
        } catch (Timeobjects_Exception $e) {
        }

        try {
            $drv = $GLOBALS['injector']->getInstance('TimeObjects_Factory_Driver')->create('FacebookEvents');
            if ($drv->ensure()) {
                $drivers['FacebookEvents'] = _("Facebook Events");
            }
        } catch (Timeobjects_Exception $e) {
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
            $drv = $GLOBALS['injector']->getInstance('TimeObjects_Factory_Driver')->create($category);
            try {
                $new = $drv->listTimeObjects($start, $end);
            } catch (TimeObjects_Exception $e) {
                $new = array();
            }
            $return = array_merge($return, $new);
        }

        return $return;
    }

}

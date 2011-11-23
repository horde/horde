<?php
/**
 * API methods for exposing various bits of data via the listTimeObjects API.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Timeobjects
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
     * Returns the available categories.
     *
     * @return array  An array of available time object categories.
     */
    public function listTimeObjectCategories()
    {
        $factory = $GLOBALS['injector']
            ->getInstance('TimeObjects_Factory_Driver');
        $tests = array('Weather' => _("Weather"),
                       'FacebookEvents' => _("Facebook Events"));
        $drivers = array();
        foreach ($tests as $driver => $description) {
            try {
                if ($factory->create($driver)->ensure()) {
                    $drivers[$driver] = $description;
                }
            } catch (Timeobjects_Exception $e) {
            }
        }
        return $drivers;
    }

    /**
     * Returns time objects for the requested category.
     *
     * @param array $time_categories  An array of categories to list.
     * @param mixed $start            The start of the time period to list for.
     * @param mixed $end              The end of the time period to list for.
     *
     * @return array  A list of time object hashes.
     */
    public function listTimeObjects($time_categories, $start, $end)
    {
        $return = array();
        foreach ($time_categories as $category) {
            try {
                $return = array_merge(
                    $return,
                    $GLOBALS['injector']
                        ->getInstance('TimeObjects_Factory_Driver')
                        ->create($category)
                        ->listTimeObjects($start, $end));
            } catch (TimeObjects_Exception $e) {
            }
        }
        return $return;
    }
}

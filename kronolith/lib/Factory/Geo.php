<?php
/**
 * Horde_Injector based factory for Kronolith_Geo drivers
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @package Kronolith
 */
class Kronolith_Factory_Geo extends Horde_Core_Factory_Injector
{
    /**
     * Return the driver instance.
     *
     * @return Kronolith_Storage
     * @throws Kronolith_Exception
     */
    public function create(Horde_Injector $injector)
    {
        if (empty($GLOBALS['conf']['maps']['geodriver'])) {
            throw new Kronolith_Exception('Geospatial support not configured.');
        }

        $class = 'Kronolith_Geo_' . $GLOBALS['conf']['maps']['geodriver'];
        $db = $injector->getInstance('Horde_Db_Adapter');

        return new $class($db);
    }

}

<?php
/**
 * Horde_Injector based factory for Kronolith_Geo drivers
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @package Kronolith
 */
class Kronolith_Injector_Factory_Geo
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * Return the driver instance.
     *
     * @return Kronolith_Storage
     * @throws Kronolith_Exception
     */
    public function create(Horde_Injector $injector)
    {
        if (empty($this->_instances[$GLOBALS['conf']['maps']['geodriver']])) {
            if (!empty($GLOBALS['conf']['maps']['geodriver'])) {
                $class = 'Kronolith_Geo_' . $GLOBALS['conf']['maps']['geodriver'];
                $db = $injector->getInstance('Horde_Db_Adapter');
                $this->_instances[$GLOBALS['conf']['maps']['geodriver']] = new $class($db);
            } else {
                throw new Kronolith_Exception(_("Geospatial support not configured."));
            }
        }

        return $this->_instances[$GLOBALS['conf']['maps']['geodriver']];
    }

}
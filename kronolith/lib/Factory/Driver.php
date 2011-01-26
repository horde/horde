<?php
/**
 * Horde_Injector based factory for Kronolith_Driver
 */
class Kronolith_Factory_Driver
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * The injector.
     *
     * @var Horde_Injector
     */
    private $_injector;

    /**
     * Constructor.
     *
     * @param Horde_Injector $injector  The injector to use.
     */
    public function __construct(Horde_Injector $injector)
    {
        $this->_injector = $injector;
    }

    /**
     * Return the driver instance.
     *
     * @param string $driver  The storage backend to use
     * @param array $params   Driver params
     *
     * @return Kronolith_Driver
     * @throws Kronolith_Exception
     */
    public function create($driver, $params = array())
    {
        $driver = basename($driver);
        if (!empty($this->_instances[$driver])) {
            return $this->_instances[$driver];
        }
        $key = $driver;
        $class = 'Kronolith_Driver_' . $driver;
        if (class_exists($class)) {
            $driver = new $class($params);
            try {
                $driver->initialize();
            } catch (Exception $e) {
                $driver = new Kronolith_Driver($params, sprintf(_("The Calendar backend is not currently available: %s"), $e->getMessage()));
            }
        } else {
            $driver = new Kronolith_Driver($params, sprintf(_("Unable to load the definition of %s."), $class));
        }
        $this->_instances[$key] = $driver;

        return $driver;
    }

}

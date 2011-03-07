<?php
/**
 * Horde_Scheduler
 *
 * @package Scheduler
 */
class Horde_Scheduler
{
    /**
     * Name of the sleep function.
     *
     * @var string
     */
    protected $_sleep;

    /**
     * Adjustment factor to sleep in microseconds.
     *
     * @var integer
     */
    protected $_sleep_adj;

    /**
     * Attempts to return a concrete Horde_Scheduler instance based on $driver.
     *
     * @param string $driver The type of concrete subclass to return.
     * @param array $params  A hash containing any additional configuration or
     *                       connection parameters a subclass might need.
     *
     * @return Horde_Scheduler  The newly created concrete instance.
     * @throws Horde_Scheduler_Exception
     */
    static public function factory($driver, $params = null)
    {
        $driver = basename($driver);
        $class = 'Horde_Scheduler_' . $driver;

        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Scheduler_Exception('Class definition of ' . $class . ' not found.');
    }

    /**
     * Constructor.
     *
     * Figures out how we can best sleep with microsecond precision
     * based on what platform we're running on.
     */
    public function __construct()
    {
        if (!strncasecmp(PHP_OS, 'WIN', 3)) {
            $this->_sleep = 'sleep';
            $this->_sleep_adj = 1000000;
        } else {
            $this->_sleep = 'usleep';
            $this->_sleep_adj = 1;
        }
    }

    /**
     * Main loop/action function.
     */
    public function run()
    {
    }

    /**
     * Preserve the internal state of the scheduler object that we are
     * passed, and save it to the Horde VFS backend. Horde_Scheduler
     * objects should define __sleep() and __wakeup() serialization
     * callbacks for anything that needs to be done at object
     * serialization or deserialization - handling database
     * connections, etc.
     *
     * @param string $id  An id to uniquely identify this scheduler from
     *                    others of the same class.
     *
     * @return boolean  Success result.
     */
    public function serialize($id = '')
    {
        try {
            $vfs = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->create();
            $vfs->writeData('.horde/scheduler', Horde_String::lower(get_class($this)) . $id, serialize($this), true);
            return true;
        } catch (VFS_Exception $e) {
            Horde::logMessage($e, 'ERR');
            return false;
        }
    }

    /**
     * Restore a Horde_Scheduler object from the cache.
     *
     * @param string $class      The name of the object to restore.
     * @param string $id         An id to uniquely identify this
     *                           scheduler from others of the same class.
     * @param boolean $autosave  Automatically store (serialize) the returned
     *                           object at script shutdown.
     *
     * @see Horde_Scheduler::serialize()
     */
    public function unserialize($class, $id = '', $autosave = true)
    {
        // Need a lowercase version of the classname, and a default
        // instance of the scheduler object in case we can't retrieve
        // one.
        $class = strtolower($class);
        $scheduler = new $class;

        try {
            $vfs = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->create();
            $data = $vfs->read('.horde/scheduler', $class . $id);
            if ($tmp = @unserialize($data)) {
                $scheduler = $tmp;
            }
        } catch (VFS_Exception $e) {
            Horde::logMessage($e, 'ERR');
        }

        if ($autosave) {
            register_shutdown_function(array($scheduler, 'serialize'));
        }

        return $scheduler;
    }

    /**
     * Platform-independant sleep $msec microseconds.
     *
     * @param integer $msec  Microseconds to sleep.
     */
    public function sleep($msec)
    {
        call_user_func($this->_sleep, $msec / $this->_sleep_adj);
    }

}

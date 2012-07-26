<?php
/**
 * Horde_Injector based factory for Nag_Driver.
 */
class Nag_Factory_Driver extends Horde_Core_Factory_Base
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
     * @param string    $tasklist   The name of the tasklist to load.
     *
     * @param string    $driver     The type of concrete Nag_Driver subclass
     *                              to return.  The is based on the storage
     *                              driver ($driver).  The code is dynamically
     *                              included.
     *
     * @param array     $params     (optional) A hash containing any additional
     *                              configuration or connection parameters a
     *                              subclass might need.
     *
     * @return Nag_Driver
     * @throws Nag_Exception
     */
    public function create($tasklist = '')
    {
        if (!empty($tasklist)) {
            $signature = $tasklist;
            $share = $GLOBALS['nag_shares']->getShare($tasklist);
            if ($share->get('issmart')) {
                $driver = 'Smartlist';
            }
        }
        if (empty($driver)) {
            $driver = $GLOBALS['conf']['storage']['driver'];
            $params = Horde::getDriverConfig('storage', $driver);
            $signature = serialize(array($tasklist, $driver, $params));
        }

        if (isset($this->_instances[$signature])) {
            return $this->_instances[$signature];
        }

        $driver = ucfirst(basename($driver));
        $class = 'Nag_Driver_' . $driver;
        switch ($driver) {
        case 'Sql':
            $params['db'] = $GLOBALS['injector']
                ->getInstance('Horde_Core_Factory_Db')
                ->create('nag', 'storage');
                break;
        case 'Kolab':
            $params['kolab'] = $GLOBALS['injector']->getInstance('Horde_Kolab_Storage');
            break;
        case 'Smartlist':
            $params['driver'] = $this->create();
        }

        if (class_exists($class)) {
            try {
                $nag = new $class($tasklist, $params);
            } catch (Nag_Exception $e) {
                $nag = new Nag_Driver($params, sprintf(_("The Tasks backend is not currently available: %s"), $e->getMessage()));
            }
        } else {
            $nag = new Nag_Driver($params, sprintf(_("Unable to load the definition of %s."), $class));
        }
        $this->_instances[$signature] = $nag;

        return $nag;
    }

}
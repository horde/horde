<?php
/**
 * A Horde_Injector:: based Turba_Driver:: factory.
 *
 * PHP version 5
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apl.html APL
 * @link     http://pear.horde.org/index.php?package=Turba
 * @package  Turba
 */

/**
 * A Horde_Injector:: based Turba_Driver:: factory.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (APL). If you
 * did not receive this file, see http://www.horde.org/licenses/apl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apl.html APL
 * @link     http://pear.horde.org/index.php?package=Turba
 * @package  Turba
 */
class Turba_Factory_Driver extends Horde_Core_Factory_Base
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * Return the Turba_Driver:: instance.
     *
     * @param mixed $name  Either a string containing the internal name of this
     *                     source, or a config array describing the source.
     *
     * @return Turba_Driver  The singleton instance.
     * @throws Turba_Exception
     */
    public function create($name)
    {
        if (is_array($name)) {
            $key = md5(serialize($name));
            $srcName = '';
            $srcConfig = $name;
        } else {
            $key = $name;
            $srcName = $name;
            if (empty($GLOBALS['cfgSources'][$name])) {
                throw new Turba_Exception(sprintf(_("The address book \"%s\" does not exist."), $name));
            }
            $srcConfig = $GLOBALS['cfgSources'][$name];
        }

        if (!isset($this->_instances[$key])) {
            $class = 'Turba_Driver_' . ucfirst(basename($srcConfig['type']));
            if (!class_exists($class)) {
                throw new Turba_Exception(sprintf(_("Unable to load the definition of %s."), $class));
            }

            if (empty($srcConfig['params'])) {
                $srcConfig['params'] = array();
            }

            switch ($class) {
            case 'Turba_Driver_Sql':
                try {
                    $srcConfig['params']['db'] = empty($srcConfig['params']['sql'])
                        ? $this->_injector->getInstance('Horde_Db_Adapter')
                        : $this->_injector->getInstance('Horde_Core_Factory_Db')->create('turba', $srcConfig['params']['sql']);
                    $srcConfig['params']['charset'] = isset($srcConfig['params']['sql']['charset'])
                        ? $srcConfig['params']['sql']['charset']
                        : 'UTF-8';
                } catch (Horde_Db_Exception $e) {
                    throw new Turba_Exception($e);
                }
                break;

            case 'Turba_Driver_Kolab':
                $srcConfig['params']['storage'] = $this->_injector->getInstance('Horde_Kolab_Storage');
                break;

            case 'Turba_Driver_Facebook':
                $srcConfig['params']['storage'] = $this->_injector->getInstance('Horde_Service_Facebook');
                break;
            }

            /* Make sure charset exists. */
            if (!isset($srcConfig['params']['charset'])) {
                $srcConfig['params']['charset'] = 'UTF-8';
            }

            $driver = new $class($srcName, $srcConfig['params']);

            // Title
            $driver->title = $srcConfig['title'];

            /* Store and translate the map at the Source level. */
            $driver->map = $srcConfig['map'];
            foreach ($driver->map as $key => $val) {
                if (!is_array($val)) {
                    $driver->fields[$key] = $val;
                }
            }

            /* Store tabs. */
            if (isset($srcConfig['tabs'])) {
                $driver->tabs = $srcConfig['tabs'];
            }

            /* Store remaining fields. */
            if (isset($srcConfig['strict'])) {
                $driver->strict = $srcConfig['strict'];
            }
            if (isset($srcConfig['approximate'])) {
                $driver->approximate = $srcConfig['approximate'];
            }
            if (isset($srcConfig['list_name_field'])) {
                $driver->listNameField = $srcConfig['list_name_field'];
            }
            if (isset($srcConfig['alternative_name'])) {
                $driver->alternativeName = $srcConfig['alternative_name'];
            }
            $this->_instances[$key] = $driver;
        }

        return $this->_instances[$key];
    }

}

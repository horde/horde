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
 * Copyright 2010 The Horde Project (http://www.horde.org/)
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
class Turba_Injector_Factory_Driver
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
            $this->_instances[$key] = Turba_Driver::factory($srcName, $srcConfig);
        }

        return $this->_instances[$key];
    }

}

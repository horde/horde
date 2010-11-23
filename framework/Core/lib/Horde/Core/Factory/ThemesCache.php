<?php
/**
 * A Horde_Injector:: based Horde_Themes_Cache:: factory.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 */

/**
 * A Horde_Injector:: based Horde_Themes_Cache:: factory.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 */
class Horde_Core_Factory_ThemesCache
{
    /**
     * The injector.
     *
     * @var Horde_Injector
     */
    private $_injector;

    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

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
     * Return the Horde_Themes_Cache:: instance.
     *
     * @param string $app    The application name.
     * @param string $theme  The theme name.
     *
     * @return Horde_Themes_Cache  The singleton instance.
     */
    public function create($app, $theme)
    {
        $sig = implode('|', array($app, $theme));

        if (!isset($this->_instances[$sig])) {
            if (!empty($GLOBALS['conf']['cachethemes'])) {
                $cache = $this->_injector->getInstance('Horde_Cache');
            }

            if (!$cache || ($cache instanceof Horde_Cache_Null)) {
                $instance = new Horde_Themes_Cache($app, $theme);
            } else {
                try {
                    $instance = @unserialize($cache->get($sig, 86400));
                } catch (Exception $e) {
                    $instance = null;
                }

                if (!($instance instanceof Horde_Themes_Cache)) {
                    $instance = new Horde_Themes_Cache($app, $theme);
                    $instance->build();
                }

                if (empty($this->_instances)) {
                    register_shutdown_function(array($this, 'shutdown'));
                }
            }

            $this->_instances[$sig] = $instance;
        }

        return $this->_instances[$sig];
    }

    /**
     * Store object in cache.
     */
    public function shutdown()
    {
        $cache = $this->_injector->getInstance('Horde_Cache');

        foreach ($this->_instances as $key => $val) {
            if ($val->changed) {
                $cache->set($key, serialize($val), 86400);
            }
        }
    }

}

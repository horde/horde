<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * Represents the merged configuration of an application with the base
 * Horde configuration.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 * @since     2.12.0
 */
class Horde_Registry_Hordeconfig_Merged extends Horde_Registry_Hordeconfig
{
    /**
     * Application configuration.
     *
     * @var Horde_Registry_Hordeconfig
     */
    protected $_aconfig;

    /**
     * Horde configuration.
     *
     * @var Horde_Registry_Hordeconfig
     */
    protected $_hconfig;

    /**
     * Indicates whether config is fully merged.
     *
     * @var boolean
     */
    protected $_merged = false;

    /**
     * Constructor.
     *
     * @param array $opts  Configuration options:
     * <pre>
     *   - aconfig: (Horde_Registry_Hordeconfig) Application config.
     *   - hconfig: (Horde_Registry_Hordeconfig) Horde config.
     * </pre>
     */
    public function __construct(array $opts)
    {
        $this->_aconfig = $opts['aconfig'];
        $this->_hconfig = $opts['hconfig'];

        $this->app = $this->_aconfig->app;
    }

    /**
     */
    public function toArray()
    {
        if (!$this->_merged) {
            $this->_config = $this->_merge(
                $this->_hconfig->toArray(),
                $this->_aconfig->toArray()
            );
            $this->_merged = true;
        }

        return $this->_config;
    }

    /**
     */
    protected function _load($offset)
    {
        if (!$this->_merged && !isset($this->_config[$offset])) {
            $h = $this->_hconfig[$offset];
            $a = $this->_aconfig[$offset];

            if (is_array($a)) {
                $this->_config[$offset] = is_array($h)
                    ? $this->_merge($h, $a)
                    : $a;
            } else {
                $this->_config[$offset] = is_null($a)
                    ? $h
                    : $a;
            }
        }
    }

    /**
     * Merge configurations between two applications.
     * See Bug #10381.
     *
     * @param array $a1  Horde configuration.
     * @param array $a2  App configuration.
     *
     * @return array  Merged configuration.
     */
    protected function _merge(array $a1, array $a2)
    {
        foreach ($a2 as $key => $val) {
            if (isset($a1[$key]) && is_array($a1[$key])) {
                reset($a1[$key]);
                if (!is_int(key($a1[$key]))) {
                    $val = $this->_merge($a1[$key], $val);
                }
            }
            $a1[$key] = $val;
        }

        return $a1;
    }

}

<?php
/**
 * Utility class for specifying scope-specific ansel configuration.
 *
 */
class Ansel_Config
{
    protected $_config = array();

    /**
     * Const'r - set the default scope to ansel.
     * 
     */
    public function __construct()
    {
        $this->_config['scope'] = 'ansel';
    }

    public function set($config, $value)
    {
        $this->_config[$config] = $value;
    }

    public function get($config)
    {
        if (!isset($this->_config[$config])) {
            throw InvalidArgumentException($config . ' not found');
        }
        return $this->_config[$config];
    }

}
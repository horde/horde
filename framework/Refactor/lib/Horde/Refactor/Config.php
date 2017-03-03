<?php
/**
 * Copyright 2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Refactor
 */

namespace Horde\Refactor;

use Horde\Refactor\Config;

/**
 * Refactoring configuration.
 *
 * <code>
 * use Horde\Refactor;
 * $config = new Refactor\Config('config.php');
 * $rule = new Refactor\Rule\SomeRule('ToRefactor.php', $config->SomeRule);
 * </code>
 *
 * or
 *
 * <code>
 * use Horde\Refactor;
 * $ruleConfig = new Refactor\Config\SomeRule(array('foo' => 'bar'));
 * $rule = new Refactor\Rule\SomeRule('ToRefactor.php', $ruleConfig);
 * </code>
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Refactor
 */
class Config
{
    /**
     * All loaded configuration objects.
     *
     * @var array
     */
    protected $_config = array();

    /**
     * Constructor.
     *
     * @param string $file  Configuration file location.
     */
    public function __construct($file = null)
    {
        if (!$file) {
            return;
        }
        if (!is_readable($file)) {
            throw InvalidArgumentException("Reading of $file not allowed");
        }
        include $file;
        if (!isset($config)) {
            return;
        }
        foreach ($config as $class => $classConfig) {
            $this->_loadClass($class, $classConfig);
        }
    }

    /**
     * Getter for the individual rule configurations.
     */
    public function __get($rule)
    {
        if (!isset($this->_config[$rule])) {
            $this->_loadClass($rule);
        }
        return $this->_config[$rule];
    }

    /**
     * Loads a configuration object.
     *
     * @param string $class  Class name to load.
     * @param array $config  Class configuration.
     */
    protected function _loadClass($class, array $config = array())
    {
        $className = '\\Horde\\Refactor\\Config\\' . $class;
        if (!class_exists($className)) {
            $className = '\\Horde\\Refactor\\Config\\Base';
        }
        $this->_config[$class] = new $className($config);
    }
}

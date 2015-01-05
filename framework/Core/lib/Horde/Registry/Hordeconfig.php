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
 * Represents the configuration for a Horde application.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 * @since     2.12.0
 */
class Horde_Registry_Hordeconfig
implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * The application.
     *
     * @var array
     */
    public $app;

    /**
     * The config data.
     *
     * @var array
     */
    protected $_config;

    /**
     * Constructor.
     *
     * @param array $opts  Configuration options:
     * <pre>
     *   - app: (string) Application.
     *   - config: (array) Use this as the configuration.
     * </pre>
     */
    public function __construct(array $opts)
    {
        $this->app = $opts['app'];
        if (isset($opts['config'])) {
            $this->_config = $opts['config'];
        }
    }

    /**
     * Return the array representation of the configuration.
     *
     * @return array  Configuration array.
     */
    public function toArray()
    {
        $this->_load(null);
        return $this->_config;
    }

    /**
     * Load configuration from config file.
     *
     * @param string $offset  Offset.
     */
    protected function _load($offset)
    {
        if (!$this->_config) {
            try {
                $c = new Horde_Registry_Loadconfig($this->app, 'conf.php', 'conf');
                $this->_config = $c->config['conf'];
            } catch (Horde_Exception $e) {
                $this->_config = array();
            }
        }
    }

    /* ArrayAccess methods. */

    /**
     */
    public function offsetExists($offset)
    {
        $this->_load($offset);
        return isset($this->_config[$offset]);
    }

    /**
     */
    public function offsetGet($offset)
    {
        $this->_load($offset);
        return isset($this->_config[$offset])
            ? $this->_config[$offset]
            : null;
    }

    public function offsetSet($offset, $value)
    {
        $this->_load($offset);
        $this->_config[$offset] = $value;
    }

    /**
     */
    public function offsetUnset($offset)
    {
        $this->_load($offset);
        unset($this->_config[$offset]);
    }

    /* Countable methods. */

    /**
     */
    public function count()
    {
        /* Return non-zero to ensure a count() calls returns true. */
        return 1;
    }

    /* IteratorAggregate methods. */

    /**
     */
    public function getIterator()
    {
        $this->toArray();
        return new ArrayIterator($this->_config);
    }

}

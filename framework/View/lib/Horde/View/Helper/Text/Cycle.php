<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2006-2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    View
 * @subpackage Helper
 */

/**
 * View helpers for URLs
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    View
 * @subpackage Helper
 */
class Horde_View_Helper_Text_Cycle
{
    /**
     * Array of values to cycle through
     * @var array
     */
    private $_values;

    /**
     * Construct a new cycler
     *
     * @param  array  $values  Values to cycle through
     */
    public function __construct($values)
    {
        if (func_num_args() != 1 || !is_array($values)) {
            throw new InvalidArgumentException();
        }

        if (count($values) < 2) {
            throw new InvalidArgumentException('must have at least two values');
        }

        $this->_values = $values;
        $this->reset();
    }

    /**
     * Returns the current element in the cycle
     * and advance the cycle
     *
     * @return  mixed  Current element
     */
    public function __toString()
    {
        $value = next($this->_values);
        return strval(($value !== false) ? $value : reset($this->_values));
    }

    /**
     * Reset the cycle
     */
    public function reset()
    {
        end($this->_values);
    }

    /**
     * Returns the values of this cycler.
     *
     * @return array
     */
    public function getValues()
    {
        return $this->_values;
    }

}

<?php
/**
 * A simple module for dependency injection.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Provider
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Provider
 */

/**
 * A trivial implementation of a provider.
 *
 * This class holds a set of elements that can be provided to an
 * application. Special placeholder values of elements that are not instantiated
 * yet implement the Horde_Provider_Binding interface. Such elements will get
 * instantiated during retrieval by calling getInstance() on the binding
 * object. The only argument to that call is the provider itself. The binding
 * object can use this reference to retrieve any other elements it needs to
 * correctly instantiate the desired object.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Horde
 * @package  Provider
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Provider
 */
class Horde_Provider_Base
{
    /**
     * The set of elements handled by this container.
     *
     * @var array
     */
    protected $elements;

    /**
     * Set an element for this container.
     *
     * @param string $k The key of the element to set.
     * @param mixed  $v The element to set.
     *
     * @return NULL
     */
    public function __set($k, $v)
    {
        $this->elements[$k] = $v;
    }

    /**
     * Get an element from this container.
     *
     * @param string $k The key of the element to retrieve.
     *
     * @return mixed The element.
     */
    public function __get($k)
    {
        if (isset($this->elements[$k])) {
            if ($this->elements[$k] instanceOf Horde_Provider_Injection) {
                $this->elements[$k] = $this->elements[$k]->getInstance($this);
            }
            return $this->elements[$k];
        } else {
            throw new Horde_Provider_Exception(sprintf("No such element: %s", $k));
        }
    }

    /**
     * Test if the element is available.
     *
     * @param string $k The key of the element to test for.
     *
     * @return boolean True if the element is sset, false otherwise.
     */
    public function __isset($k)
    {
        return isset($this->elements[$k]);
    }

    /**
     * Delete the element.
     *
     * @param string $k The key of the element to delete.
     *
     * @return NULL
     */
    public function __unset($k)
    {
        if (isset($this->elements[$k])) {
            unset($this->elements[$k]);
        }
    }
}
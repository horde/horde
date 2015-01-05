<?php
/**
 * Copyright 2009-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @category  Horde
 * @copyright 2009-2015 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Injector
 */

/**
 * @author    Bob Mckee <bmckee@bywires.com>
 * @author    James Pepin <james@jamespepin.com>
 * @category  Horde
 * @copyright 2009-2015 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Injector
 */
class Horde_Injector_Binder_Implementation implements Horde_Injector_Binder
{
    /**
     */
    private $_implementation;

    /**
     * @var Horde_Injector_DependencyFinder
     */
    private $_dependencyFinder;

    /**
     */
    public function __construct($implementation,
                                Horde_Injector_DependencyFinder $finder = null)
    {
        $this->_implementation = $implementation;
        $this->_dependencyFinder = is_null($finder)
            ? new Horde_Injector_DependencyFinder()
            : $finder;
    }

    /**
     */
    public function getImplementation()
    {
        return $this->_implementation;
    }

    /**
     * @return boolean  Equality.
     */
    public function equals(Horde_Injector_Binder $otherBinder)
    {
        return (($otherBinder instanceof Horde_Injector_Binder_Implementation) &&
                ($otherBinder->getImplementation() == $this->_implementation));
    }

    /**
     */
    public function create(Horde_Injector $injector)
    {
        try {
            $reflectionClass = new ReflectionClass($this->_implementation);
        } catch (ReflectionException $e) {
            throw new Horde_Injector_Exception($e);
        }
        $this->_validateImplementation($reflectionClass);
        return $this->_getInstance($injector, $reflectionClass);
    }

    /**
     */
    protected function _validateImplementation(ReflectionClass $reflectionClass)
    {
        if ($reflectionClass->isAbstract() || $reflectionClass->isInterface()) {
            throw new Horde_Injector_Exception('Cannot bind interface or abstract class "' . $this->_implementation . '" to an interface.');
        }
    }

    /**
     */
    protected function _getInstance(Horde_Injector $injector,
                                    ReflectionClass $class)
    {
        return $class->getConstructor()
            ? $class->newInstanceArgs($this->_dependencyFinder->getMethodDependencies($injector, $class->getConstructor()))
            : $class->newInstance();
    }

}

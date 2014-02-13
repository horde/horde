<?php
/**
 * Copyright 2009-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @category  Horde
 * @copyright 2009-2014 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Injector
 */

/**
 * This is a simple class that uses reflection to figure out the dependencies
 * of a method and attempts to return them using the Injector instance.
 *
 * @author    Bob Mckee <bmckee@bywires.com>
 * @author    James Pepin <james@jamespepin.com>
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2009-2014 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Injector
 */
class Horde_Injector_DependencyFinder
{
    /**
     * @param Horde_Injector $injector
     * @param ReflectionMethod $method
     *
     * @return array
     * @throws Horde_Injector_Exception
     */
    public function getMethodDependencies(Horde_Injector $injector,
                                          ReflectionMethod $method)
    {
        $dependencies = array();

        try {
            foreach ($method->getParameters() as $parameter) {
                $dependencies[] = $this->getParameterDependency($injector, $parameter);
            }
        } catch (Horde_Injector_Exception $e) {
            throw new Horde_Injector_Exception("$method has unfulfilled dependencies ($parameter)", 0, $e);
        }

        return $dependencies;
    }

    /**
     * @param Horde_Injector $injector
     * @param ReflectionParameter $method
     *
     * @return mixed
     * @throws Horde_Injector_Exception
     */
    public function getParameterDependency(Horde_Injector $injector,
                                           ReflectionParameter $parameter)
    {
        if ($parameter->getClass()) {
            return $injector->getInstance($parameter->getClass()->getName());
        } elseif ($parameter->isOptional()) {
            return $parameter->getDefaultValue();
        }

        throw new Horde_Injector_Exception("Untyped parameter \$" . $parameter->getName() . "can't be fulfilled");
    }

}

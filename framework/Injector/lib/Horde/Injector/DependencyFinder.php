<?php
/**
 * This is a simple class that uses reflection to figure out the dependencies of
 * a method and attempts to return them using the Injector instance.
 *
 * @author   Bob Mckee <bmckee@bywires.com>
 * @author   James Pepin <james@jamespepin.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Injector
 */
class Horde_Injector_DependencyFinder
{
    /**
     * TODO
     *
     * @param Horde_Injector $injector  TODO
     * @param ReflectionMethod $method  TODO
     *
     * @return array  TODO
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
            throw new Horde_Injector_Exception("$method has unfulfilled dependencies (parameter: $parameter, error: " . $e->getMessage() . ')', 0);
        }

        return $dependencies;
    }

    /**
     * TODO
     *
     * @param Horde_Injector $injector     TODO
     * @param ReflectionParameter $method  TODO
     *
     * @return mixed  TODO
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

<?php
/**
 * A test helper for generating complex test setups.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Test
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/components/Horde_Test
 */

/**
 * A test helper for generating complex test setups.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @since Horde_Test 1.2.0
 *
 * @category Horde
 * @package  Test
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/components/Horde_Test
 */
class Horde_Test_Setup
{
    /**
     * The Horde_Injector instance which serves as our service container.
     *
     * @var Horde_Injector
     */
    private $_injector;

    /**
     * In case the setup turns out to be unfullfillable this should contain an
     * appropriate message indicating the problem.
     *
     * @var string
     */
    private $_error;

    /**
     * Global parameters that apply to several factories.
     *
     * @var string
     */
    private $_params = array();

    /**
     * Constructor.
     */
    public function __construct()
    {
        if (class_exists('Horde_Injector')) {
            $this->_injector = new Horde_Injector(new Horde_Injector_TopLevel());
            $this->_injector->setInstance('Horde_Injector', $this->_injector);
        } else {
            $this->_error = 'The Horde_Injector class is unavailable!';
        }
    }

    /**
     * Add a new set of elements to the service container.
     *
     * @param array $params All parameters necessary for creating the services.
     *                      The keys of the array elements define the name that
     *                      will be used for registering the test service with
     *                      the injector. The element values are a
     *                      configuration array with the following elements:
     * <pre>
     * 'factory' - (string) Name of the factory. Can be a full class name or an
     *             abbreviated name that will get prepended with
     *             'Horde_Test_Factory_'
     * 'method' - (string) Method name that will be invoked on the above factory
     *            to generate the test service.
     * 'params' - (array) Any parameters the factory method might require for
     *            generating the test service. See the various factories/methods
     *            for details.
     * </pre>
     *
     * @return NULL
     */
    public function setup($params)
    {
        if (isset($params['_PARAMS'])) {
            $this->_params = $params['_PARAMS'];
            unset($params['_PARAMS']);
        }
        foreach ($params as $interface => $setup) {
            if (is_array($setup)) {
                $factory = $setup['factory'];
                $method = isset($setup['method']) ? $setup['method'] : 'create';
                $params = isset($setup['params']) ? $setup['params'] : array();
            } else {
                $factory = $setup;
                $method = 'create';
                $params = array();
            }
            if (!empty($this->_error)) {
                break;
            }
            $this->add($interface, $factory, $method, $params);
        }
    }

    /**
     * Add a new element to the service container.
     *
     * @oaram string $interface The interface name to register the service with.
     * @param string $factory   The (abbreviated) name of the factory.
     * @param string $method    The factory method that will generate the
     *                          service.
     * @param array  $params    All parameters necessary for creating the
     *                          service.
     *
     * @return NULL
     */
    public function add($interface, $factory, $method, $params)
    {
        if (!empty($this->_error)) {
            return;
        }
        if (!class_exists('Horde_Test_Factory_' . $factory) &&
            !class_exists($factory)) {
            $this->_error = "Neither the class \"Horde_Test_Factory_$factory\" nor \"$factory\" exist. \"$interface\" cannot be created!";
            return;
        }
        if (class_exists('Horde_Test_Factory_' . $factory)) {
            $f = $this->_injector->getInstance('Horde_Test_Factory_' . $factory);
        } else {
            $f = $this->_injector->getInstance($factory);
        }
        if (!method_exists($f, $method) &&
            !method_exists($f, 'create' . $method)) {
            $this->_error = "The factory lacks the specified method \"$method\"!";
            return;
        }
        if (method_exists($f, 'create' . $method)) {
            $method = 'create' . $method;
        }
        $params = array_merge($this->_params, $params);
        try {
            $this->_injector->setInstance($interface, $f->{$method}($params));
        } catch (Horde_Test_Exception $e) {
            $this->_error = $e->getMessage() . "\n\n" . $e->getFile() . ':' . $e->getLine();
        }
    }

    /**
     * Export elements from the injector into global scope.
     *
     * @param array $elements The elements to export.
     *
     * @return NULL
     */
    public function makeGlobal($elements)
    {
        if (!empty($this->_error)) {
            return;
        }
        foreach ($elements as $key => $interface) {
            $GLOBALS[$key] = $this->_injector->getInstance($interface);
        }
    }

    /**
     * Return any potential setup error.
     *
     * @return string The error.
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * Return the service container.
     *
     * @return Horde_Injector The injector.
     */
    public function getInjector()
    {
        return $this->_injector;
    }
}

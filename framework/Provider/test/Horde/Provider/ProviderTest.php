<?php
/**
 * All tests for the Provider:: package.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Provider
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Provider
 */

/**
 * Load the basic story testing class.
 */
require_once 'ProviderScenario.php';

/**
 * Test the Horde_Provider class.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Provider
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Provider
 */
class Horde_Provider_ProviderTest extends Horde_Provider_ProviderScenario
{
    /**
     * Test retrieving an unset element.
     *
     * @scenario
     *
     * @return NULL
     */
    public function retrievingAnUnsetElementYieldsAnError()
    {
        $this->given('a provider')
            ->when('retrieving the element', 'key')
            ->then('the result is an error with the message',
                   'No such element: key');
    }

    /**
     * Test retrieving a simple element.
     *
     * @scenario
     *
     * @return NULL
     */
    public function retrievingASimpleElement()
    {
        $this->given('a provider')
            ->given('a registered element', 'key', 'value')
            ->when('retrieving the element', 'key')
            ->then('the result is', 'value');
    }

    /**
     * Test retrieving a new element with invalid recursion.
     *
     * @scenario
     *
     * @return NULL
     */
    public function retrievingARecursingElement()
    {
        $injection = new Horde_Provider_Injection_Factory(array('Recursion',
                                                                'getValue'));

        $this->given('a provider')
            ->given('a registered element', 'recursion', $injection)
            ->when('retrieving the element', 'recursion')
            ->then('the result is an error with the message',
                   'Element already loading!');
    }

    /**
     * Test retrieving a new element via a factory function.
     *
     * @scenario
     *
     * @return NULL
     */
    public function retrievingANewElementWithAFactoryFunction()
    {
        $injection = new Horde_Provider_Injection_Factory('factory');

        $this->given('a provider')
            ->given('a registered element', 'key', $injection)
            ->when('retrieving the element', 'key')
            ->then('the result is', 'injected');
    }

    /**
     * Test retrieving a new element via a factory class method.
     *
     * @scenario
     *
     * @return NULL
     */
    public function retrievingANewElementWithAFactoryClassMethod()
    {
        $injection = new Horde_Provider_Injection_Factory(array('Dummy',
                                                                'getValue'));

        $this->given('a provider')
            ->given('a registered element', 'key', $injection)
            ->when('retrieving the element', 'key')
            ->then('the result is', 'constructed');
    }

}

/**
 * A dummy factory.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Provider
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Provider
 */
class Dummy
{
    /**
     * A factory method producing a constant string.
     *
     * @param Horde_Provider $provider The dependency provider.
     *
     * @return string Always returns 'constructed'
     */
    static public function getValue($provider)
    {
        return 'constructed';
    }
}

/**
 * A dummy factory used to demonstrate recursion.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Provider
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Provider
 */
class Recursion
{
    /**
     * A factory method that returns the provided 'recursion' element.
     *
     * @param Horde_Provider $provider The dependency provider.
     *
     * @return mixed The element 'recursion' from the provider.
     */
    static public function getValue($provider)
    {
        //@todo: Why does $provider->recursion yield no error/result?
        //return $provider->recursion;
        return $provider->__get('recursion');
    }
}

/**
 * A factory producing a constant string.
 *
 * @param Horde_Provider $provider The dependency provider.
 *
 * @return string Always returns 'injected'
 */
function factory($provider)
{
    return 'injected';
}

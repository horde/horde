<?php
/**
 * A test helper replacing real factories.
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
 * A test helper replacing real factories.
 *
 * The Horde_Injector is often queried for factories that allow to generate more
 * complex objects. The factories usually implement the create() method as
 * primary variant for generating the instance. This test replacement is meant
 * to be used as a prepared stub that can be provided to the injector and will
 * return the instance required for testing.
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
class Horde_Test_Stub_Factory
{
    /**
     * The instance to be returned.
     *
     * @var mixed
     */
    private $_instance;

    /**
     * Constructor.
     *
     * @param mixed $instance The instance that the factory should return.
     */
    public function __construct($instance)
    {
        $this->_instance = $instance;
    }

    /**
     * Create an instance.
     *
     * @return mixed The predefined instance.
     */
    public function create()
    {
        return $this->_instance;
    }
}
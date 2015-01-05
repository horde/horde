<?php
/**
 * Default class for application defined API calls.
 *
 * Copyright 2009-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 *
 * @property array $disabled  The list of disabled methods.
 * @property array $links  The list of available links.
 * @property array $methods  The list of available methods.
 * @property array $noPerms  The list of calls not requiring permission checks.
 */
class Horde_Registry_Api
{
    /**
     * The list of disabled API calls.
     *
     * @var array
     */
    protected $_disabled = array();

    /**
     * Links.
     *
     * @var array
     */
    protected $_links = array();

    /**
     * Cached list of methods.
     *
     * @var array
     */
    protected $_methods = array();

    /**
     * The listing of API calls that do not require permissions checking.
     *
     * @var array
     */
    protected $_noPerms = array();

    /**
     * List of disabled API methods.
     *
     * An application's implementation is responsible for changing/resetting
     * application scope if it is needed within the method (e.g. to use
     * application's prefs).
     *
     * @return array  List of disabled API methods.
     */
    public function disabled()
    {
        return $this->_disabled;
    }

    /**
     * List of application links.
     *
     * An application's implementation is responsible for changing/resetting
     * application scope if it is needed within the method (e.g. to use
     * application's prefs).
     *
     * @return array  List of application links.
     */
    public function links()
    {
        return $this->_links;
    }

    /**
     * Return the list of active API methods.
     *
     * @return array  List of active API methods.
     */
    final public function methods()
    {
        if (empty($this->_methods)) {
            $disabled = $this->disabled();
            $reflect = new ReflectionClass($this);
            foreach ($reflect->getMethods(ReflectionMethod::IS_PUBLIC) as $v) {
                if (($v->getDeclaringClass()->name != __CLASS__) &&
                    !in_array($v->name, $disabled)) {
                    $this->_methods[] = $v->name;
                }
            }
        }

        return $this->_methods;
    }

    /**
     * List of API methods that don't require permissions.
     *
     * An application's implementation is responsible for changing/resetting
     * application scope if it is needed within the method (e.g. to use
     * application's prefs).
     *
     * @return array  List of API methods that don't require permissions.
     */
    public function noPerms()
    {
        return $this->_noPerms;
    }


    /* API calls should be declared as public functions, with the function
     * name corresponding to the API name. Create any internal helper
     * functions as protected functions. NOTE: The constructor does NOT have
     * the full horde/application environment available, and the user may not
     * yet be authenticated. */

}

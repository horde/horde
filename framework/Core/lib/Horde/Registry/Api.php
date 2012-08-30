<?php
/**
 * Default class for application defined API calls.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
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
     * The listing of API calls that do not require permissions checking.
     *
     * @var array
     */
    protected $_noPerms = array();

    /**
     * Wrapper around protected variables to allow application's
     * implementation to modify static values. Application implementation is
     * responsible for changing/resetting application scope if it is needed
     * within the method (e.g. to use application's prefs).
     */
    public function __get($name)
    {
        switch ($name) {
        case 'disabled':
            return $this->_disabled;

        case 'links':
            return $this->_links;

        case 'methods':
            $disabled = $this->disabled;
            $methods = array();

            $reflect = new ReflectionClass($this);
            foreach ($reflect->getMethods(ReflectionMethod::IS_PUBLIC) as $val) {
                if (($val->getDeclaringClass()->name != __CLASS__) &&
                    !in_array($val->name, $disabled)) {
                    $methods[] = $val->name;
                }
            }

            return $methods;

        case 'noPerms':
            return $this->_noPerms;

        default:
            return null;
        }
    }


    /* API calls should be declared as public functions, with the function
     * name corresponding to the API name. Create any internal helper
     * functions as protected functions. NOTE: The constructor does NOT have
     * the full horde/application environment available, and the user may not
     * yet be authenticated. */

}

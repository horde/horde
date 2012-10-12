<?php
/**
 * Defines AJAX actions to be handled by an application's endpoint.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 *
 * @property Horde_Variables $vars  The Variables object.
 */
class Horde_Core_Ajax_Application_Handler
{
    /**
     * The base AJAX application object.
     *
     * @var Horde_Core_Ajax_Application
     */
    protected $_base;

    /**
     * A list of public method names to ignore as actions.
     *
     * @var array
     */
    protected $_ignored = array();

    /**
      * A list of actions that require read-only session access.
      *
      * @var array
      */
    protected $_readOnly = array();

    /**
     * Constructor.
     *
     * @param Horde_Core_Ajax_Application $base  The base AJAX application
     *                                           object.
     */
    public function __construct(Horde_Core_Ajax_Application $base)
    {
        $this->_base = $base;
    }

    /**
     */
    final public function __get($name)
    {
        switch ($name) {
        case 'vars':
            return $this->_base->vars;
        }
    }

    /**
     * Determines if the action exists in this handler.
     *
     * @param string $action  An AJAX action.
     *
     * @return boolean  True if the action exists.
     */
    final public function has($action)
    {
        try {
            $method = new ReflectionMethod($this, $action);
        } catch (ReflectionException $e) {
            return false;
        }

        return ($method->isPublic() &&
                !in_array($action, $this->_ignored) &&
                ($method->getDeclaringClass()->name != __CLASS__));
    }

    /**
     * Is the action marked read-only?
     *
     * @param string $action  An AJAX action.
     *
     * @return boolean  True if the action is read-only.
     */
    final public function readonly($action)
    {
        return in_array($action, $this->_readOnly);
    }

}

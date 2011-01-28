<?php
/**
 * The Horde_Core_Ui_Widget:: class provides base functionality for other
 * Horde UI elements.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jason M. Felice <jason.m.felice@gmail.com>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Core
 */
abstract class Horde_Core_Ui_Widget
{
    /**
     * Any variables that should be preserved in all of the widget's
     * links.
     *
     * @var array
     */
    protected $_preserve = array();

    /**
     * The name of this widget.  This is used as the basename for variables
     * we access and manipulate.
     *
     * @var string
     */
    protected $_name;

    /**
     * A reference to a Horde_Variables:: object this widget will use and
     * manipulate.
     *
     * @var Horde_Variables
     */
    protected $_vars;

    /**
     * An array of name => value pairs which configure how this widget
     * behaves.
     *
     * @var array
     */
    protected $_config;

    /**
     * Holds the name of a callback function to call on any URLS before they
     * are used/returned. If an array, it is taken as an object/method name, if
     * a string, it is taken as a php function.
     *
     * @var callable
     */
    protected $_url_callback = array('Horde', 'applicationUrl');

    /**
     * Construct a new UI Widget interface.
     *
     * @param string $name            The name of the variable which will
     *                                track this UI widget's state.
     * @param Horde_Variables &$vars  A Horde_Variables:: object.
     * @param array $config           The widget's configuration.
     */
    public function __construct($name, &$vars, $config = array())
    {
        $this->_name = $name;
        $this->_vars = &$vars;

        if (array_key_exists('url_callback', $config)) {
            $this->_url_callback = $config['url_callback'];
            unset($config['url_callback']);
        }
        $this->_config = $config;
    }

    /**
     * Instructs widget to preserve a variable or a set of variables.
     *
     * @param string|array $var  The name of the variable to preserve, or
     *                           an array of variables to preserve.
     * @param mixed $value       If preserving a single key, the value of the
     *                           variable to preserve.
     */
    public function preserve($var, $value = null)
    {
        if (!is_array($var)) {
            $var = array($var => $value);
        }

        foreach ($var as $key => $value) {
            $this->_preserve[$key] = $value;
        }
    }

    /**
     * TODO
     */
    protected function _addPreserved($link)
    {
        foreach ($this->_preserve as $varName => $varValue) {
            $link->add($varName, $varValue);
        }

        return $link;
    }

    /**
     * Render the widget.
     *
     * @param mixed $data  The widget's state data.
     */
    abstract public function render($data = null);

    /**
     * TODO
     */
    protected function _link($link)
    {
        if (is_callable($this->_url_callback)) {
            return call_user_func($this->_url_callback, $link);
        }

        return $link;
    }

}

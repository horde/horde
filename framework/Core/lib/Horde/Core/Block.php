<?php
/**
 * An abstract class representing a single block in the portal/block display.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Jan Schneider <jan@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Core
 */
abstract class Horde_Core_Block
{
    /**
     * Is this block enabled?
     *
     * @var boolean
     */
    public $enabled = true;

    /**
     * Whether this block has changing content.
     *
     * @var boolean
     */
    public $updateable = false;

    /**
     * Application that this block originated from.
     *
     * @var string
     */
    protected $_app;

    /**
     * Block name. Should be set in the constructor.
     *
     * @var string
     */
    protected $_name = '';

    /**
     * Block specific parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Constructor.
     *
     * @param string $app            The application name.
     * @param array|boolean $params  Any parameters the block needs. If false,
     *                               the default parameter will be used.
     */
    public function __construct($app, $params = array())
    {
        $this->_app = $app;

        // @todo: we can't simply merge the default values and stored values
        // because empty parameter values are not stored at all, so they would
        // always be overwritten by the defaults.
        if ($params === false) {
            foreach ($this->getParams() as $name => $param) {
                $this->_params[$name] = $param['default'];
            }
        } else {
            $this->_params = $params;
        }
    }

    /**
     * Returns the application that this block belongs to.
     *
     * @return string  The application name.
     */
    public function getApp()
    {
        return $this->_app;
    }

    /**
     * Return the block name.
     *
     * @return string  The block name.
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Returns any settable parameters for this block.
     * It does *not* reference $this->_params; that is for runtime
     * parameters (the choices made from these options).
     *
     * @return array  The block's configurable parameters.
     */
    public function getParams()
    {
        return $this->_call('_params', array());
    }

    /**
     * Returns the parameters needed by block.
     *
     * @return array  The block's parameters.
     */
    protected function _params()
    {
        return array();
    }

    /**
     * Returns a hash of block parameters and their configured values.
     *
     * @return array  Parameter values.
     */
    public function getParamValues()
    {
        return $this->_params;
    }

    /**
     * Returns the text to go in the title of this block.
     *
     * This function handles the changing of current application as
     * needed so code is executed in the scope of the application the
     * block originated from.
     *
     * @return string  The block's title.
     */
    public function getTitle()
    {
        return $this->_call('_title', '');
    }

    /**
     * Returns the title to go in this block.
     *
     * @return string  The block title.
     */
    protected function _title()
    {
        return $this->_name;
    }

    /**
     * Returns the content for this block.
     *
     * This function handles the changing of current application as
     * needed so code is executed in the scope of the application the
     * block originated from.
     *
     * @return string  The block's content.
     */
    public function getContent()
    {
        return $this->_call('_content', '');
    }

    /**
     * Returns this block's content.
     *
     * @return string  The block's content.
     */
    abstract protected function _content();

    /**
     * The data to send on an AJAX update request.
     *
     * @param Horde_Variables $vars  The form variables for the request.
     *
     * @return string  Update data.
     */
    public function getAjaxUpdate(Horde_Variables $vars)
    {
        return $this->_call('_ajaxUpdate', '', $vars);
    }

    /**
     * Returns this block's content for AJAX updates.
     *
     * @param Horde_Variables $vars  The form variables for the request.
     *
     * @return string  The update content.
     */
    protected function _ajaxUpdate(Horde_Variables $vars)
    {
        return '';
    }

    /**
     * Return the URL to use for AJAX update requests.
     *
     * @return Horde_Url  The update URL.
     */
    protected function _ajaxUpdateUrl()
    {
        $ajax_url = Horde::getServiceLink('ajax', 'horde');
        $ajax_url->pathInfo = 'blockUpdate';
        $ajax_url->add('blockid', get_class($this));

        return $ajax_url;
    }

    /**
     * Calls the application driver in the proper context.
     *
     * @param string $name string
     * @param mixed $default
     * @param mixed $args
     *
     * @return mixed
     */
    protected function _call($name, $default, $args = null)
    {
        try {
            $pushed = $GLOBALS['registry']->pushApp($this->getApp(), array(
                'check_perms' => true,
                'logintasks' => false
            ));
        } catch (Horde_Exception $e) {
            return $default;
        }

        try {
            $ret = is_null($args)
                ? $this->$name()
                : call_user_func($name, $args);
        } catch (Horde_Exception $e) {
            $ret = $default;
        }

        if ($pushed) {
            $GLOBALS['registry']->popApp();
        }

        return $ret;
    }

}

<?php
/**
 * The abstract Horde_Block:: class represents a single block within
 * the Blocks framework.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Horde_Block
 */
class Horde_Block
{
    /**
     * Whether this block has changing content.
     *
     * @var boolean
     */
    public $updateable = false;

    /**
     * Block specific parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * The Block row.
     *
     * @var integer
     */
    protected $_row;

    /**
     * The Block column.
     *
     * @var integer
     */
    protected $_col;

    /**
     * Application that this block originated from.
     *
     * @var string
     */
    protected $_app;

    /**
     * Constructor.
     *
     * @param array|boolean $params  Any parameters the block needs. If false,
     *                               the default parameter will be used.
     * @param integer $row           The block row.
     * @param integer $col           The block column.
     */
    public function __construct($params = array(), $row = null, $col = null)
    {
        // @todo: we can't simply merge the default values and stored values
        // because empty parameter values are not stored at all, so they would
        // always be overwritten by the defaults.
        if ($params === false) {
            $params = $this->getParams();
            foreach ($params as $name => $param) {
                $this->_params[$name] = $param['default'];
            }
        } else {
            $this->_params = $params;
        }
        $this->_row = $row;
        $this->_col = $col;
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
     * Returns any settable parameters for this block.
     * It does *not* reference $this->_params; that is for runtime
     * parameters (the choices made from these options).
     *
     * @return array  The block's configurable parameters.
     */
    public function getParams()
    {
        /* Switch application contexts, if necessary. Return an error
         * immediately if pushApp() fails. */
        try {
            $app_pushed = $GLOBALS['registry']->pushApp($this->_app, array('check_perms' => true, 'logintasks' => false));
        } catch (Horde_Exception $e) {
            return $e->getMessage();
        }

        $params = $this->_params();

        /* If we changed application context in the course of this
         * call, undo that change now. */
        if ($app_pushed) {
            $GLOBALS['registry']->popApp();
        }

        return $params;
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
        /* Switch application contexts, if necessary. Return an error
         * immediately if pushApp() fails. */
        try {
            $app_pushed = $GLOBALS['registry']->pushApp($this->_app, array('check_perms' => true, 'logintasks' => false));
        } catch (Horde_Exception $e) {
            return $e->getMessage();
        }

        try {
            $title = $this->_title();
        } catch (Horde_Block_Exception $e) {
            $title = $e->getMessage();
        }
        /* If we changed application context in the course of this
         * call, undo that change now. */
        if ($app_pushed) {
            $GLOBALS['registry']->popApp();
        }

        return $title;
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
        /* Switch application contexts, if necessary. Return an error
         * immediately if pushApp() fails. */
        try {
            $app_pushed = $GLOBALS['registry']->pushApp($this->_app, array('check_perms' => true, 'logintasks' => false));
        } catch (Horde_Exception $e) {
            return $e->getMessage();
        }

        try {
            $content = $this->_content();
        } catch (Horde_Block_Exception $e) {
            $content = $e->getMessage();
        }

        /* If we changed application context in the course of this
         * call, undo that change now. */
        if ($app_pushed) {
            $GLOBALS['registry']->popApp();
        }

        return $content;
    }

    /**
     * Returns the title to go in this block.
     *
     * @return string  The block title.
     */
    protected function _title()
    {
        return '';
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
     * Returns this block's content.
     *
     * @return string  The block's content.
     */
    protected function _content()
    {
        return '';
    }

}

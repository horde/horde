<?php
/**
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Whups
 */
abstract class Whups_View_Base
{
    protected $_params;

    public function __construct($params)
    {
        $this->_params = $params;
        if (!isset($this->_params['title'])) {
            $this->_params['title'] = '';
        }
    }

    abstract public function html();
}

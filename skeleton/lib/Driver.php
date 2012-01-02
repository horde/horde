<?php
/**
 * Skeleton_Driver defines an API for implementing storage backends for
 * Skeleton.
 *
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Your Name <you@example.com>
 * @package Skeleton
 */
class Skeleton_Driver
{
    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Array holding the current foo list. Each array entry is a hash
     * describing a foo. The array is indexed by the IDs.
     *
     * @var array
     */
    protected $_foos = array();

    /**
     * Constructor.
     *
     * @param array $params  A hash containing connection parameters.
     */
    public function __construct($params = array())
    {
        $this->_params = $params;
    }

    /**
     * Lists all foos.
     *
     * @return array  Returns a list of all foos.
     */
    public function listFoos()
    {
        return $this->_foos;
    }
}

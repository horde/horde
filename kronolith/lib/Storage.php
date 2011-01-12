<?php
/**
 * Kronolith_Storage defines an API for storing free/busy information.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Kronolith
 */
abstract class Kronolith_Storage
{
    /**
     * String containing the current username.
     *
     * @var string
     */
    protected $_user = '';

    /**
     * Stub to initiate a driver.
     * @throws Kronolith_Exception
     */
    function initialize()
    {
        return true;
    }

    /**
     * Stub to be overridden in the child class.
     */
    abstract public function search($email, $private_only = false);

    /**
     * Stub to be overridden in the child class.
     */
    abstract public function store($email, $vfb, $public = false);
}

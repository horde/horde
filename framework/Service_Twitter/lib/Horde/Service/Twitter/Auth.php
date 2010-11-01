<?php
/**
 * Horde_Service_Twitter_Auth_* classes to abstract all auth related tasks for
 * various auth mechanisms.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package Horde_Service_Twitter
 */
abstract class Horde_Service_Twitter_Auth
{
    /**
     *
     * @var Horde_Service_Twitter
     */
    protected $_twitter;

    /**
     * Configuration parameters
     *
     * @param array
     */
    protected $_config;


    public function setTwitter(Horde_Service_Twitter $twitter)
    {
        $this->_twitter = $twitter;
    }

    /**
     * Getter
     *
     * @param string $value
     *
     * @return mixed  The value of the requested property.
     */
    public function __get($value)
    {
        if (!empty($this->_config[$value])) {
            return $this->_config[$value];
        }

        throw new Horde_Service_Twitter_Exception(sprintf("The property %s does not exist", $value));
    }

}

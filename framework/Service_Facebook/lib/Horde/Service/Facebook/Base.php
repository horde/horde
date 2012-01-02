<?php
/**
 * Base class for all Horde_Service_Framework_* classes
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Service_Facebook
 */
class Horde_Service_Facebook_Base
{
    /**
     *
     * @var Horde_Service_Facebook
     */
    protected $_facebook;

    /**
     *
     * @var Horde_Http_Client
     */
    protected $_http;

    /**
     * Constructor
     *
     * @param Horde_Service_Facebook         $facebook
     */
    public function __construct(Horde_Service_Facebook $facebook)
    {
        $this->_facebook = $facebook;
        $this->_http = $facebook->http;
    }

}
<?php
/**
 * Horde_Serivce_VimeoSimple:: wrapper around Vimeo's (http://www.vimeo.com)
 * Simple API.
 *
 * Copyright 2008 The Horde Project (http://www.horde.org)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */
class Horde_Service_Vimeo_Simple extends Horde_Service_Vimeo {

    protected $_api_endpoint = 'http://www.vimeo.com/api/';
    protected $_oembed_endpoint = 'http://www.vimeo.com/api/oembed.json';


    public function getActivity($criteria)
    {
    }

    /**
     * Group:
     * User:
     * Album:
     * Channel:
     *
     *
     * @param unknown_type $criteria
     */
    public function getInfo($criteria)
    {
    }

    public function __call($name, $args)
    {
        switch ($name) {
        case 'user';
            $request = new Horde_Service_Vimeo_Request(array('type' => $args[0]));
            return $request;
        }
    }

}

class Horde_Service_Vimeo_Request {
    protected $_api_endpoint = 'http://www.vimeo.com/api';
    protected $_oembed_endpoint = 'http://www.vimeo.com/api/oembed.json';
    protected $_identifier;
    protected $_method;

    public function __construct($args)
    {
        $this->_identifier = $args['type'];
    }

    public function __call($name, $args)
    {
        switch ($name) {
        case 'clips':
            $this->_method = $name;
            return $this;
       }
    }

    public function run()
    {
        $req = Horde_Service_Vimeo::getHttpClient();
        $response = $req->request('GET', $this->_api_endpoint . '/' . $this->_identifier . '/' . $this->_method . '.' . Horde_Service_Vimeo::getFormat());
        return $response->getBody();
    }
}
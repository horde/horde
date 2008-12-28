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

    public function __construct($args = array())
    {
        if (count($args) && !empty($args['type'])) {
            $this->_identifier = $args['type'];

        }
    }

    public function __call($name, $args)
    {
        switch ($name) {
        case 'clips':
            $this->_method = $name;
            return $this;
       }
    }

    public function embed($options)
    {
        if (!is_array($options)) {
            // Assume it's a video id, need to get the video url
            // @TODO
        }

        // $options should be an array now
        if (empty($options['url']) && !empty($options['video_id'])) {
            // We were originally passed an array, but still need the url
            // @TODO
        }

        // We should have a url now, and possibly other options.
        $url = Util::addParameter($this->_oembed_endpoint, $options, null, false);

        $req = Horde_Service_Vimeo::getHttpClient();
        $response = $req->request('GET', $url);
        $results = $response->getBody();

        return $results;
    }


    public function run()
    {
        $req = Horde_Service_Vimeo::getHttpClient();
        $response = $req->request('GET', $this->_api_endpoint . '/' . $this->_identifier . '/' . $this->_method . '.' . Horde_Service_Vimeo::getFormat());
        return $response->getBody();
    }
}
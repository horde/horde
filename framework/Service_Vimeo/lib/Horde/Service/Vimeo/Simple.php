<?php
/**
 * Horde_Serivce_VimeoSimple:: wrapper around Vimeo's (http://www.vimeo.com)
 * Simple API.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org)
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
        return new Horde_Service_Vimeo_Request(array('type' => $name, 'identifier' => $args[0]));
    }

}

class Horde_Service_Vimeo_Request {
    protected $_api_endpoint = 'http://www.vimeo.com/api';
    protected $_oembed_endpoint = 'http://www.vimeo.com/api/oembed.json';

    // Qualifier in the URL after /api/
    protected $_identifier;
    protected $_method;
    protected $_type;

    protected $_methodTypes = array('user' => array('clips', 'likes', 'info', 'appears_in', 'all_clips', 'subscriptions', 'albums', 'channels', 'groups', 'contacts_clips', 'contacts_like'),
                                    'group' => array('clips', 'users', 'info'),
                                    'channel' => array('clips', 'info'),
                                    'album' => array('clips', 'info'));

    public function __construct($args = array())
    {
        if (count($args) && !empty($args['type'])) {

            // Might be useful to know the type at some point
            $this->_type = $args['type'];
            switch ($args['type']) {
            case 'user':
                $this->_identifier = $args['identifier'];
                break;
            case 'group':
                $this->_identifier = '/group/' . $args['identifier'];
                break;
            case 'channel':
                $this->_identifier = '/channel/' . $args['identifier'];
                break;
            case 'album':
                $this->_identifier = '/album/' . $args['identifier'];
                break;
            }
        }
    }

    /**
     * TODO: Validate the requested method fits with the type of query
     *
     * @param unknown_type $name
     * @param unknown_type $args
     * @return unknown
     */
    public function __call($name, $args)
    {
        if (!in_array($name, $this->_methodTypes[$this->_type])) {
            return;
        }
        $this->_method = $name;
        return $this;
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
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

    /**
     * An optional cache object that implments the same interface as
     * Horde_Cache
     *
     * @var Horde_Cache
     */
    protected $_cache;
    protected $_cache_lifetime;


    /**
     * Inject a cache obect
     *
     * @param Horde_Cache $cache  The cache object
     * @param int $lifetime       The cache lifetime in seconds
     */
    public function setCache($cache, $lifetime = 1)
    {
        $this->_cache = $cache;
        $this->_cache_lifetime = $lifetime;
    }

    /**
     * Set up a request based on the method name.
     *
     * @TODO: validate that we have a valid method or throw an exception
     *
     * @return Horde_Service_Vimeo_Request
     */
    public function __call($name, $args)
    {
        $params = array('type' => $name,
                        'identifier' => $args[0],
                        'cache' => array('object' => $this->_cache,
                                          'lifetime' => $this->_cache_lifetime));

        return new Horde_Service_Vimeo_Request($params);
    }

}

class Horde_Service_Vimeo_Request {

    /**
     * Cache object
     *
     * @var Horde_Cache
     */
    private $_cache;

    // The vimeo simple api endpoint
    protected $_api_endpoint = 'http://www.vimeo.com/api';

    // The vimeo oembed api endpoint
    protected $_oembed_endpoint = 'http://www.vimeo.com/api/oembed.json';

    // Qualifier in the URL after /api/ (like <user_id> or group/<group_id>)
    protected $_identifier;

    // The api method we are calling (clips, info etc...)
    protected $_method;

    // The type of request (user, group etc...)
    protected $_type;

    // Valid method/type map
    protected $_methodTypes = array('user' => array('clips', 'likes', 'info', 'appears_in', 'all_clips', 'subscriptions', 'albums', 'channels', 'groups', 'contacts_clips', 'contacts_like'),
                                    'group' => array('clips', 'users', 'info'),
                                    'channel' => array('clips', 'info'),
                                    'album' => array('clips', 'info'));

    /**
     * Contructor
     *
     * @param Horde_Service_Vimeo $parent  The requesting object
     * @param array $args                  Argument array
     */
    public function __construct($args = array())
    {
        if (count($args)) {
            $this->_cache = isset($args['cache']) ? $args['cache'] : null;
            if (!empty($args['type'])) {

                // The type of method we are calling (user, group, etc...)
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

    /**
     * Obtain the JSON needed to embed a single Vimeo video specified by the
     * parameter. Passing a url is the most effecient as we won't have to query
     * the vimeo service for the url.
     *
     * @param mixed $options  Either an array containing the vimeo url or
     *                        vimeo clip id, OR a scaler containing the clip id.

     * @return unknown
     */
    public function getEmbedJson($options)
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

        // See if we have a cache, and if so, try to get the data from it before
        // polling the vimeo service.
        if (!empty($this->_cache['object'])) {
            $cache_key = 'VimeoJson' . md5(serialize($options));
            $data = $this->_cache['object']->get($cache_key, $this->_cache['lifetime']);
            if ($data !== false) {
                return unserialize($data);
            }
        }

        // We should have a url now, and possibly other options.
        $url = Util::addParameter($this->_oembed_endpoint, $options, null, false);

        $req = Horde_Service_Vimeo::getHttpClient();
        $response = $req->request('GET', $url);
        $results = $response->getBody();
        if (!empty($this->_cache)) {
            $this->_cache['object']->set($cache_key, serialize($results));
        }

        return $results;
    }


    public function run()
    {
        $call =  '/' . $this->_identifier . '/' . $this->_method . '.' . Horde_Service_Vimeo::getFormat();
        if (!empty($this->_cache['object'])) {
            $cache_key = 'VimeoRequest' . md5($call);
            $data = $this->_cache['object']->get($cache_key, $this->_cache['lifetime']);
            if ($data !== false) {
                // php format is already returned serialized
                if (Horde_Service_Vimeo::getFormat() != 'php') {
                    $data = unserialize($data);
                }

                return $data;
            }
        }

        $req = Horde_Service_Vimeo::getHttpClient();
        $response = $req->request('GET', $this->_api_endpoint . $call);
        $data = $response->getBody();

        if (!empty($this->_cache['object'])) {
            if (Horde_Service_Vimeo::getFormat() != 'php') {
                $sdata = serialize($data);
            } else {
                $sdata = $data;
            }
            $this->_cache['object']->set($cache_key, $sdata);
        }

        return $data;

    }

}
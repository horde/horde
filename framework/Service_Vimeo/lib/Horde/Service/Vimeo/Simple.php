<?php
/**
 * Horde_Serivce_VimeoSimple:: wrapper around Vimeo's (http://www.vimeo.com)
 * Simple API.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Service_Vimeo
 */
class Horde_Service_Vimeo_Simple extends Horde_Service_Vimeo {

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
     * TODO: Validate the requested method fits with the type of query
     *
     * @param unknown_type $name
     * @param unknown_type $args
     * @return unknown
     */
    public function __call($name, $args)
    {
        // Is this a Vimeo type?
        if (in_array($name, array_keys($this->_methodTypes))) {

            // Make sure we have an identifier arguament.
            if (empty($args[0])) {
                throw new InvalidArgumentException(sprintf("Missing identifier argument when calling %s", $name));
            }

            // Remember the type we're requesting
            $this->_type = $name;

            // Build a valid identifier
            switch ($name) {
            case 'user':
                // user is the default type for a Vimeo simple query
                $this->_identifier = $args[0];
                break;
            default:
                $this->_identifier = '/' . $name . '/' . $args[0];
                break;
            }

            return $this;
        }

        // What about a method call - we must have already called a type
        if (in_array($name, $this->_methodTypes[$this->_type]) && !empty($this->_type)) {
            $this->_method = $name;
            return $this;
        }

        // Don't know what the heck is going on...
        throw new BadMethodCallException(sprintf("Unknown method call: %s", $name));
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
        if (!empty($this->_cache)) {
            $cache_key = 'VimeoJson' . hash('md5', serialize($options));
            $data = $this->_cache->get($cache_key, $this->_cache_lifetime);
            if ($data !== false) {
                return unserialize($data);
            }
        }

        // We should have a url now, and possibly other options.
        $url = Horde_Util::addParameter($this->_oembed_endpoint, $options, null, false);

        try {
            $response = $this->_http_client->request('GET', $url);
        } catch (Horde_Http_Exception $e) {
            // TODO:
            // Some videos it seems are not found via oembed? This appears to be
            // a fringe case, and only happens when I'm attempting to load
            // videos that aren't mine.  Maybe they are marked as
            // non-embeddable? I can't seem to find that setting though....
            // for now, ignore the Http_Client's exception so we don't kill
            // the page...just log it....once we have a logger, that is.
        }

        $results = $response->getBody();
        if (!empty($this->_cache)) {
            $this->_cache->set($cache_key, serialize($results));
        }

        return $results;
    }

    public function run()
    {
        $call =  '/' . $this->_identifier . '/' . $this->_method . '.' . $this->_format;
        if (!empty($this->_cache)) {
            $cache_key = 'VimeoRequest' . hash('md5', $call);
            $data = $this->_cache->get($cache_key, $this->_cache_lifetime);
            if ($data !== false) {
                // php format is already returned serialized
                if ($this->_format != 'php') {
                    $data = unserialize($data);
                }

                return $data;
            }
        }

        $response = $this->_http_client->request('GET', $this->_api_endpoint . $call);
        $data = $response->getBody();

        if (!empty($this->_cache)) {
            if ($this->_format != 'php') {
                $sdata = serialize($data);
            } else {
                $sdata = $data;
            }
            $this->_cache->set($cache_key, $sdata);
        }

        return $data;

    }

}

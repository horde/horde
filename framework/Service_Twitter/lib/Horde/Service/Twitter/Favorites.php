<?php
/**
 * Horde_Service_Twitter_Favorites class for updating favorite tweets.
 *
 * Copyright 2009-2015 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package Service_Twitter
 */
class Horde_Service_Twitter_Favorites
{
    /**
     * Endpoint for status api requests
     *
     * @var string
     */
    private $_endpoint = 'https://api.twitter.com/1.1/favorites/';

    /**
     * Format to use json or xml
     *
     * @var string
     */
    private $_format = 'json';

    /**
     * Constructor
     *
     * @param Horde_Service_Twitter $twitter
     */
    public function __construct($twitter)
    {
        $this->_twitter = $twitter;
    }

    /**
     * Obtain the requested status
     *
     * @return string  The method call results.
     */
    public function get()
    {
        $url = $this->_endpoint . 'list.' . $this->_format;
        return $this->_twitter->request->post($url);
    }

    /**
     * Destroy the specified favorite.
     *
     * @param string $id  The status id
     *
     * @return string
     */
    public function destroy($id)
    {
        $url = $this->_endpoint . 'destroy.' . $this->_format;
        return $this->_twitter->request->post($url, array('id' => $id));
    }

    /**
     * Add a new favorite
     *
     * @param string $id  The status id
     *
     * @return string  The favorited tweet.
     */
    public function create($id)
    {
        $url = $this->_endpoint . 'create.' . $this->_format;
        return $this->_twitter->request->post($url, array('id' => $id));
    }

}
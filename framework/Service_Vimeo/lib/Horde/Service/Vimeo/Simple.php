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

    /**
     * Return an array of clips data based on the search criteria.
     *
     * @param array $criteria  The search criteria:
     *     Users
     *       userClips:
     *       userLikes:
     *       userIn:
     *       userAll:
     *       userSubscriptions:
     *       contactsClips:
     *       contactsLikes:
     *
     *     Groups
     *       groupClips: clips in this group
     */
    public function getClips($criteria)
    {
        $key = array_pop(array_keys($criteria));

        switch ($key) {
        case 'userClips':
            $method = $criteria['userClips'] . '/clips.' . $this->_format;
            break;
        }

        $req = $this->getHttpClient();
        $response = $req->request('GET', $this->_api_endpoint . $method);
        return $response->getBody();
    }

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


    /**
     * Get the raw JSON response containing the data to embed a single video.
     *
     * @param mixed $optons  Either an array containing api parameters or the
     *                       video id. If an array, if the url is not passed,
     *                       we find it from the video_id.
     *
     *
     * @return JSON encoded data
     */
    public function getEmbedJSON($options)
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
        $url = Util::addParameter($this->_oembed_endpoint, $options, null, true);

        $req = $this->getHttpClient();
        $response = $req->request('GET', $url);
        $results = $response->getBody();

        return $results;
    }

}
<?php

/** HTTP_Request **/
require_once 'HTTP/Request.php';

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
class Service_VimeoSimple {

    protected $_api_endpoint = 'http://www.vimeo.com/api/';
    protected $_oembed_endpoint = 'http://www.vimeo.com/api/oembed.json';
    protected $_format = 'php';


    public function setReturnFormat($format)
    {
        // TODO: Validate (json, php, xml)
        $this->_format = $format;
    }

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
     *
     *
     *
     * @param unknown_type $criteria
     */
    public function getClips($criteria)
    {
        $key = array_pop(array_keys($criteria));

        switch ($key) {
        case 'userClips':
            $method = $criteria['userClips'] . '/clips.' . $this->_format;
            break;
        }

        $req = new HTTP_Request($this->_api_endpoint . $method);
        if (is_a($req, 'PEAR_Error')) {
            return $req;
        }
        $req->sendRequest();
        return $req->getResponseBody();
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
     * @param string $clipUrl  The URL of the video to embed.
     *
     * @return JSON encoded data
     */
    public function getEmbedJSON($clipUrl)
    {
        $url = $this->_oembed_endpoint . '?url=' . rawurlencode($clipUrl);
        $req = new HTTP_Request($url);
        //@TODO: We should probably throw an exception here.
        if (is_a($req, 'PEAR_Error')) {
            return $req;
        }
        $req->sendRequest();
        $response = $req->getResponseBody();
        return $response;
    }

}
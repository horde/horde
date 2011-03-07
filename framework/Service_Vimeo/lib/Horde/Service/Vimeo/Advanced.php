<?php
/**
 * Horde_Serivce_Vimeo_Advnaced:: wrapper around Vimeo's (http://www.vimeo.com)
 * Advanced API.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Service_Vimeo
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */
class Horde_Service_Vimeo_Simple extends Horde_Service_Vimeo {

    protected $_api_endpoint = '';
    protected $_oembed_endpoint = 'http://www.vimeo.com/api/oembed.json';

    protected $_api_key;
    protected $_shared_secret;


    public function setApiKey($key)
    {
        $this->_api_key = $key;
    }

    public function setSharedSecret($secret)
    {
        $this->_shared_secret = $secret;
    }

    protected function _generateSignature($request)
    {
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
     */
    public function getClips($criteria)
    {
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

}
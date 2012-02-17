<?php
/**
 * Update the freecode information.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Release
 * @author   Mike Hardy
 * @author   Jan Schneider <jan@horde.org>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Release
 */

/**
 * Update the freecode information.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Release
 * @author   Mike Hardy
 * @author   Jan Schneider <jan@horde.org>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Release
 */
class Horde_Release_Freecode
{
    /**
     * Freecode auth token.
     *
     * @var string
     */
    private $_token;

    /**
     * Freecode project name.
     *
     * @var string
     */
    private $_project;

    /**
     * Constructor.
     *
     * @param string $token  Freecode auth token.
     * @param string $proect Freecode project name.
     */
    public function __construct($token, $project)
    {
        $this->_token = $token;
        $this->_project = $project;
    }

    /**
     * Attempt to publish the new release to the fm restful api.
     *
     * @param array $params  The array of fm release parameters
     *
     * @return mixed Result of the attempt / PEAR_Error on failure
     */
    public function publish($params)
    {
        $params['tag_list'] = implode(', ', $params['tag_list']);
        $fm_params = array('auth_code' => $this->_token,
                           'release' => $params);
        $http = new Horde_Http_Client();
        try {
            $response = $http->post('http://freecode.com/projects/' . $this->_project . '/releases.json',
                                    Horde_Serialize::serialize($fm_params, Horde_Serialize::JSON),
                                    array('Content-Type' => 'application/json'));
        } catch (Horde_Http_Exception $e) {
            if (strpos($e->getMessage(), '201 Created') === false) {
                throw new Horde_Exception_Wrapped($e);
            } else {
                return '';
            }
        }

        // 201 Created
        return $response->getBody();
    }

    /**
     * Attempt to update FM project links
     */
    public function updateLinks($links)
    {
        // Need to get the list of current URLs first, then find the one we want
        // to update.
        $http = new Horde_Http_Client();
        try {
            $response = $http->get('http://freecode.com/projects/' . $this->_project . '/urls.json?auth_code=' . $this->_token);
        } catch (Horde_Http_Exception $e) {
            throw new Horde_Exception_Wrapped($e);
        }

        $url_response = Horde_Serialize::unserialize($response->getBody(), Horde_Serialize::JSON);
        if (!is_array($url_response)) {
            $url_response = array();
        }

        // Should be an array of URL info in response...go through our requested
        // updates and see if we can find the correct 'permalink' parameter.
        foreach ($links as $link) {
            $permalink = '';
            foreach ($url_response as $url) {
                // FM docs contradict this, but each url entry in the array is
                // wrapped in a 'url' property.
                $url = $url->url;
                if ($link['label'] == $url->label) {
                    $permalink = $url->permalink;
                    break;
                }
            }
            $link = array('auth_code' => $this->_token,
                          'url' => $link);
            $http = new Horde_Http_Client();
            if (empty($permalink)) {
                // No link found to update...create it.
                try {
                    $response = $http->post('http://freecode.com/projects/' . $this->_project . '/urls.json',
                                            Horde_Serialize::serialize($link, Horde_Serialize::JSON),
                                            array('Content-Type' => 'application/json'));
                    $response = $response->getBody();
                } catch (Horde_Http_Exception $e) {
                    if (strpos($e->getMessage(), '201 Created') === false) {
                        throw new Horde_Exception_Wrapped($e);
                    } else {
                        $response = '';
                    }
                }
            } else {
                // Found the link to update...update it.
                try {
                    $response = $http->put('http://freecode.com/projects/' . $this->_project . '/urls/' . $permalink . '.json',
                                           Horde_Serialize::serialize($link, Horde_Serialize::JSON),
                                           array('Content-Type' => 'application/json'));
                    $response = $response->getBody();
                    // Status: 200???
                } catch (Horde_Http_Exception $e) {
                    throw new Horde_Exception_Wrapped($e);
                }
            }
        }

        return true;
    }
}

<?php
/**
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * http://www.scribd.com/platform/documentation/api?method_name=Authentication
 * http://www.scribd.com/platform/account
 * http://github.com/richid/services_scribd/tree/master
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Service_Scribd
 */

/**
 * Scribd client class
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Service_Scribd
 */
class Horde_Service_Scribd
{
    const ENDPOINT = 'http://api.scribd.com/api';

    /**
     * HTTP client object to use for accessing the Scribd API.
     * @var Horde_Http_Client
     */
    protected static $_httpClient = null;

    /**
     * Set the HTTP client instance
     *
     * Sets the HTTP client object to use for Scribd requests. If none is set,
     * the default Horde_Http_Client will be used.
     *
     * @param Horde_Http_Client $httpClient
     */
    public static function setHttpClient($httpClient)
    {
        self::$_httpClient = $httpClient;
    }

    /**
     * Gets the HTTP client object.
     *
     * @return Horde_Http_Client
     */
    public static function getHttpClient()
    {
        if (!self::$_httpClient) {
            self::$_httpClient = new Horde_Http_Client;
        }

        return self::$_httpClient;
    }

    /**
     * @var array
     */
    protected $_config = array();

    /**
     * Constructor
     *
     * @param array  API parameters:
     *   api_key
     *   api_secret
     *   session_key
     *   my_user_id
     *
     * @link http://www.scribd.com/platform/documentation/api
     */
    public function __construct($config)
    {
        $this->_config = $config;
    }

    /**
     * Upload a local file.
     *
     * @param string   $file     Local file
     * @param string   $docType  Document type: PDF, DOC, TXT, PPT, etc.
     * @param string   $access   Document visibility. 'public' or 'private', default 'public'
     * @param integer  $rev_id   The doc_id to save uploaded file as a revision to
     *
     * @return array   [doc_id, access_key, [secret_password]]
     */
    public function upload($file, $doc_type = null, $access = null, $revId = null)
    {
        $args = array('file' => $file);
        if ($docType !== null) $args['doc_type'] = $docType;
        if ($access !== null) $args['access'] = $access;
        if ($revId !== null) $args['rev_id'] = $revId;

        $response = $this->newRequest('docs.upload', $args)->run();
        /*@TODO*/
    }

    /**
     * Upload a document from a publicly accessible URL.
     *
     * @param string   $url      Document location
     * @param string   $docType  Document type: PDF, DOC, TXT, PPT, etc.
     * @param string   $access   Document visibility. 'public' or 'private', default 'public'
     * @param integer  $rev_id   The doc_id to save uploaded file as a revision to
     *
     * @return array   [doc_id, access_key, [secret_password]]
     */
    public function uploadFromUrl($url, $doc_type = null, $access = null, $rev_id = null)
    {
        $args = array('url' => $url);
        if ($docType !== null) $args['doc_type'] = $docType;
        if ($access !== null) $args['access'] = $access;
        if ($revId !== null) $args['rev_id'] = $revId;

        $response = $this->newRequest('docs.uploadFromUrl', $args)->run();
        /*@TODO*/
    }

    /**
     * Return an iterator over the authorized user's documents.
     *
     * @return Traversable
     */
    public function getList()
    {
        return $this->newRequest('docs.getList')->run()->getResultSet();
    }

    /**
     * Get the current conversion status of a document.
     *
     * @param integer  $docId  Document id to get status for
     *
     * @return string  "DISPLAYABLE", "DONE", "ERROR", or "PROCESSING"
     */
    public function getConversionStatus($docId)
    {
        return (string)$this->newRequest('docs.getConversionStatus', array('doc_id' => $docId))->run()->conversion_status;
    }

    /**
     * Get a document's settings
     *
     * @param integer  $docId  Document id to get status for
     *
     * @return array  [doc_id, title, description, access, license, tags[], show_ads, access_key, thumbnail_url, secret_password]
     */
    public function getSettings($docId)
    {
        $response = $this->newRequest('docs.getSettings', array('doc_id' => $docId))->run();
        return array(
            'doc_id' => $response->doc_id(),
            'title' => $response->title(),
            'description' => $response->description(),
            'access' => $response->access(),
            'license' => $response->license(),
            'tags' => strpos($response->tags(), ',') !== false ? explode(',', $response->tags()) : array(),
            'show_ads' => $response->show_ads(),
            'access_key' => $response->access_key(),
            'thumbnail_url' => $response->thumbnail_url(),
            'secret_password' => $response->secret_password(),
        );
    }

    /**
     * Change a document's settings.
     *
     * @param mixed  $docIds    One or more document ids to change.
     * @param array  $settings  The values to set for each $docId. Possible keys:
     *                            title:          string
     *                            description:    string
     *                            access:         ["public", "private"]
     *                            license:        ["by", "by-nc", "by-nc-nd", "by-nc-sa", "by-nd", "by-sa", "c", "pd"
     *                            show_ads:       ["default", "true", "false"]
     *                            link_back_url:  string
     *                            tags:           comma-separated stringlist (or PHP array)
     *
     * @return true
     */
    public function changeSettings($docIds, $settings)
    {
        $args = array('doc_ids' => is_array($docIds) ? implode(',', $docIds) : $docIds);
        foreach (array('title', 'description', 'access', 'license', 'show_ads', 'link_back_url') as $key) {
            if (isset($settings[$key])) $args[$key] = $settings[$key];
        }
        if (isset($settings['tags'])) {
            $args['tags'] = is_array($settings['tags']) ? implode(',', $settings['tags']) : $settings['tags'];
        }

        $this->newRequest('docs.changeSettings', $args)->run();
        return true;
    }

    /**
     * Delete a document.
     *
     * @param integer  $docId  The document to delete
     *
     * @return true
     */
    public function delete($docId)
    {
        $this->newRequest('docs.delete', array('doc_id' => $docId))->run();
        return true;
    }

    /**
     * Search the Scribd database
     *
     * @param string $query : search query
     * @param int $num_results : number of results to return (10 default, 1000 max)
     * @param int $num_start : number to start from
     * @param string $scope : scope of search, "all" or "user"
     *
     * @return array of results, each of which contain doc_id, secret password, access_key, title, and description
     */
    public function search($query, $num_results = null, $num_start = null, $scope = null)
    {
        $params['query'] = $query;
        $params['num_results'] = $num_results;
        $params['num_start'] = $num_start;
        $params['scope'] = $scope;

        return $this->newRequest('docs.search', $args)->run()->getResultSet();
    }

    /**
     * Log in as a user
     *
     * @param string $username : username of user to log in
     * @param string $password : password of user to log in
     *
     * @return array containing session_key, name, username, and user_id of the user
     */
    public function login($username, $password)
    {
        $method = "user.login";
        $params['username'] = $username;
        $params['password'] = $password;

        $result = $this->postRequest($method, $params);
        $this->_config['session_key'] = $response->session_key();
        return $result;
    }

    /**
     * Sign up a new user
     *
     * @param string $username : username of user to create
     * @param string $password : password of user to create
     * @param string $email : email address of user
     * @param string $name : name of user
     *
     * @return array containing session_key, name, username, and user_id of the user
     */
    public function signup($username, $password, $email, $name = null)
    {
        $method = "user.signup";
        $params['username'] = $username;
        $params['password'] = $password;
        $params['name'] = $name;
        $params['email'] = $email;

        $result = $this->postRequest($method, $params);
        $this->_config['session_key'] = $response->session_key();
        return $result;
    }

    /**
     * Create an API request for $method with $args
     *
     * @param string $method  The API method to call.
     * @param string $args    Method arguments
     *
     * @return Horde_Service_Scribd_Request
     */
    public function newRequest($method, $args = array())
    {
        $request = new Horde_Service_Scribd_Request($method, $args);
        $request->setConfig($this->_config);
        return $request;
    }

}

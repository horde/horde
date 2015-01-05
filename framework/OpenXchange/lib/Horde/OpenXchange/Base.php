<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  OpenXchange
 */

/**
 * Horde_OpenXchange_Base is the base class for HTTP API requests to an
 * Open-Xchange server.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   OpenXchange
 */
abstract class Horde_OpenXchange_Base
{
    /**
     * Return private resources.
     *
     * @see listResources()
     */
    const RESOURCE_PRIVATE = 'private';

    /**
     * Return public resources.
     *
     * @see listResources()
     */
    const RESOURCE_PUBLIC = 'public';

    /**
     * The folder category.
     *
     * @var string
     */
    protected $_folderType;

    /**
     * Any parameters.
     *
     * @var array
     */
    protected $_params;

    /**
     * HTTP client
     *
     * @var Horde_Http_Client
     */
    protected $_client;

    /**
     * Base URI of the API endpoint.
     *
     * @var string
     */
    protected $_uri;

    /**
     * All cookies to sent with OX requests.
     *
     * @var array
     */
    protected $_cookies = array();

    /**
     * The current session ID.
     *
     * @var string
     */
    protected $_session;

    /**
     * Constructor.
     *
     * @param array $params  List of optional parameters:
     *                       - client: (Horde_Http_Client) An HTTP client.
     *                       - endpoint: (string) The URI of the OX API
     *                         endpoint.
     *                       - user: (string) Authentication user.
     *                       - password: (string) Authentication password.
     */
    public function __construct(array $params = array())
    {
        if (isset($params['client'])) {
            $this->_client = $params['client'];
            unset($params['client']);
        } else {
            $this->_client = new Horde_Http_Client(array('request.timeout' => 10));
        }
        $this->_params = array_merge(
            array('endpoint' => 'http://localhost/ajax'),
            $params
        );
        $this->_uri = $this->_params['endpoint'];
    }

    /**
     * Logs a user in.
     *
     * @param string $user      A user name.
     * @param string $password  A password.
     *
     * @throws Horde_OpenXchange_Exception.
     */
    public function login($user, $password)
    {
        $this->_params['user'] = $user;
        $this->_params['password'] = $password;
        $this->_login();
    }

    /**
     * Logs the current user out.
     *
     * @throws Horde_OpenXchange_Exception.
     */
    public function logout()
    {
        if (!isset($this->_session)) {
            return;
        }

        $response = $this->_request(
            'GET',
            'login',
            array('action' => 'logout'),
            array('session' => $this->_session)
        );

        unset($this->_session);
        $this->_cookies = array();
    }

    /**
     * Returns information about a system user.
     *
     * @param integer $id  A user ID.
     *
     * @return array  User information hash.
     * @throws Horde_OpenXchange_Exception.
     */
    public function getUser($id)
    {
        $user = $this->_request(
            'GET',
            'user',
            array(
                'action' => 'get',
                'session' => $this->_session,
                'id' => $id,
            )
        );
        return $user['data'];
    }

    /**
     * Returns information about a system group.
     *
     * @param integer $id  A group ID.
     *
     * @return array  Group information hash.
     * @throws Horde_OpenXchange_Exception.
     */
    public function getGroup($id)
    {
        $group = $this->_request(
            'GET',
            'group',
            array(
                'action' => 'get',
                'session' => $this->_session,
                'id' => $id,
            )
        );
        return $group;
    }

    /**
     * Returns user configuration.
     *
     * @param string $config  A configuration namespace.
     *
     * @return mixed  Configuration contents.
     * @throws Horde_OpenXchange_Exception.
     */
    public function getConfig($config)
    {
        $content = $this->_request(
            'GET',
            'config/' . $config,
            array('session' => $this->_session)
        );
        switch ($content['data']) {
        case 'true':
            return true;
        case 'false':
            return false;
        case 'null':
            return null;
        default:
            return $content['data'];
        }
    }

    /**
     * Returns a list of visible groupware resources.
     *
     * @param string $type  An resource type, one of the RESOURCE_* constants.
     *
     * @return array  List of resources with resource IDs as keys and
     *                information hashes as values.
     * @throws Horde_OpenXchange_Exception.
     */
    public function listResources($type = self::RESOURCE_PRIVATE)
    {
        $this->_login();

        $response = $this->_request(
            'PUT',
            'folders',
            array(
                'action' => 'allVisible',
                'session' => $this->_session,
                'content_type' => $this->_folderType,
                'columns' => '1,300,306,308'
            )
        );

        $resources = $users = $groups = array();
        foreach ($response['data'][$type] as $resource) {
            $info = array(
                'label' => $resource[1],
                'default' => $resource[3],
            );
            foreach ($resource[2] as $perm) {
                // http://oxpedia.org/wiki/index.php?title=HTTP_API#PermissionFlags
                $permission = 0;
                if ($perm['bits'] & 127) {
                    $permission |= Horde_Perms::SHOW;
                }
                if ((($perm['bits'] >> 7) & 127) > 1) {
                    $permission |= Horde_Perms::READ;
                }
                if ((($perm['bits'] >> 14) & 127) > 1) {
                    $permission |= Horde_Perms::EDIT;
                }
                if ((($perm['bits'] >> 21) & 127) > 1) {
                    $permission |= Horde_Perms::DELETE;
                }
                if ($perm['group']) {
                    if (!isset($groups[$perm['entity']])) {
                        $group = $this->getGroup($perm['entity']);
                        $groups[$perm['entity']] = $group['name'];
                    }
                    $info['permission']['group'][$groups[$perm['entity']]] = $perm['bits'];
                    $info['hordePermission']['group'][$groups[$perm['entity']]] = $permission;
                } else {
                    if (!isset($users[$perm['entity']])) {
                        $user = $this->getUser($perm['entity']);
                        $users[$perm['entity']] = $user['login_info'];
                    }
                    $info['permission']['user'][$users[$perm['entity']]] = $perm['bits'];
                    $info['hordePermission']['user'][$users[$perm['entity']]] = $permission;
                }
            }
            $resources[$resource[0]] = $info;
        }

        return $resources;
    }

    /**
     * Logs a user in, if necessary.
     *
     * @throws Horde_OpenXchange_Exception.
     */
    protected function _login()
    {
        if (isset($this->_session)) {
            return;
        }

        if (!isset($this->_params['user']) ||
            !isset($this->_params['password'])) {
            throw new LogicException('User name or password missing');
        }

        $response = $this->_request(
            'POST',
            'login',
            array('action' => 'login'),
            array(
                'name' => $this->_params['user'],
                'password' => $this->_params['password']
            )
        );

        $this->_session = $response['session'];
    }

    /**
     * Sends a request and parses the response.
     *
     * @param string $method      A HTTP request method (uppercase).
     * @param string $namespace   An API namespace.
     * @param array $params       URL parameters.
     * @param array|string $data  Request data.
     *
     * @return array  The decoded result data or null if no data has been
     *                returned but the request was still successful.
     * @throws Horde_OpenXchange_Exception.
     */
    protected function _request($method, $namespace, $params, $data = array())
    {
        $uri = new Horde_Url($this->_uri . '/' . $namespace, true);
        try {
            $headers = array();
            if (isset($this->_cookies)) {
                $headers['Cookie'] = implode('; ', $this->_cookies);
            }
            if ($method == 'GET') {
                $params = array_merge($params, $data);
                $data = null;
            }
            $response = $this->_client->request(
                $method,
                (string)$uri->add($params),
                $data,
                $headers
            );
            if ($cookies = $response->getHeader('set-cookie')) {
                if (!is_array($cookies)) {
                    $cookies = array($cookies);
                }
                foreach ($cookies as $cookie) {
                    $cookie = preg_split('/;\s*/', $cookie);
                    for ($i = 1, $c = count($cookie); $i < $c; $i++) {
                        list($key, $value) = explode('=', $cookie[$i]);
                        if ($key == 'Expires') {
                            $expire = new Horde_Date($value);
                            if ($expire->before(time())) {
                                continue 2;
                            }
                            break;
                        }
                    }
                    $this->_cookies[] = $cookie[0];
                }
            }
            $body = $response->getBody();
            $data = json_decode($body, true);
            if (!$data) {
                if ($response->code == 200) {
                    return;
                }
                throw new Horde_OpenXchange_Exception($body);
            }
            if (isset($data['error'])) {
                $e = new Horde_OpenXchange_Exception($data['error']);
                $e->details = $data;
                throw $e;
            }
            return $data;
        } catch (Horde_Http_Exception $e) {
            throw new Horde_OpenXchange_Exception($e);
        }
    }
}

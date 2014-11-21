<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Dav
 */

/**
 * A wrapper around Sabre\DAV\Client that uses Horde's HTTP library.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Dav
 */
class Horde_Dav_Client extends Sabre\DAV\Client
{
    /**
     * A HTTP client.
     *
     * @var Horde_Http_Client
     */
    protected $_http;

    /**
     * Constructor
     *
     * Settings are provided through the 'settings' argument. The following
     * settings are supported:
     *
     *   * client
     *   * baseUri
     *   * userName (optional)
     *   * password (optional)
     *   * proxy (optional)
     *
     * @param array $settings
     */
    public function __construct(array $settings)
    {
        if (!isset($settings['client'])) {
            throw new InvalidArgumentException('A client must be provided');
        }
        $this->_http = $settings['client'];
        $this->propertyMap['{DAV:}current-user-privilege-set'] = 'Sabre\\DAVACL\\Property\\CurrentUserPrivilegeSet';
        parent::__construct($settings);
    }

    /**
     * Performs an actual HTTP request, and returns the result.
     *
     * If the specified url is relative, it will be expanded based on the base
     * url.
     *
     * The returned array contains 3 keys:
     *   * body - the response body
     *   * httpCode - a HTTP code (200, 404, etc)
     *   * headers - a list of response http headers. The header names have
     *     been lowercased.
     *
     * @param string $method
     * @param string $url
     * @param string $body
     * @param array $headers
     *
     * @return array
     * @throws Horde_Dav_Exception
     */
    public function request($method, $url = '', $body = null, $headers = array())
    {
        $url = $this->getAbsoluteUrl($url);

        $this->_http->{'request.redirects'} = 5;
        $this->_http->{'request.verifyPeer'} = $this->verifyPeer;
        if ($this->proxy) {
            $this->_http->{'request.proxyServer'} = $this->proxy;
        }
        if ($this->userName && $this->authType) {
            switch ($this->authType) {
            case self::AUTH_BASIC:
                $this->_http->{'request.authenticationScheme'} = Horde_Http::AUTH_BASIC;
                break;
            case self::AUTH_DIGEST:
                $this->_http->{'request.authenticationScheme'} = Horde_Http::AUTH_DIGEST;
                break;
            default:
                $this->_http->{'request.authenticationScheme'} = Horde_Http::AUTH_ANY;
                break;
            }
            $this->_http->{'request.username'} = $this->userName;
            $this->_http->{'request.password'} = $this->password;
        }

        // Not supported by Horde_Http_Client yet:
        // $this->trustedCertificates;

        if ($method == 'HEAD') {
            $body = null;
        }

        try {
            $result = $this->_http->request($method, $url, $body, $headers);
        } catch (Horde_Http_Exception $e) {
            throw new Horde_Dav_Exception($e);
        }

        if (isset($result->headers['dav']) &&
            is_array($result->headers['dav'])) {
            $result->headers['dav'] = implode(', ', $result->headers['dav']);
        }
        $response = array(
            'body' => $result->getBody(),
            'statusCode' => $result->code,
            'headers' => $result->headers,
            'url' => $result->uri,
        );

        if ($response['statusCode'] >= 400) {
            switch ($response['statusCode']) {
            case 400:
                throw new Horde_Dav_Exception('Bad request', $response['statusCode']);
            case 401:
                throw new Horde_Dav_Exception('Not authenticated', $response['statusCode']);
            case 402:
                throw new Horde_Dav_Exception('Payment required', $response['statusCode']);
            case 403:
                throw new Horde_Dav_Exception('Forbidden', $response['statusCode']);
            case 404:
                throw new Horde_Dav_Exception('Resource not found.', $response['statusCode']);
            case 405:
                throw new Horde_Dav_Exception('Method not allowed', $response['statusCode']);
            case 409:
                throw new Horde_Dav_Exception('Conflict', $response['statusCode']);
            case 412:
                throw new Horde_Dav_Exception('Precondition failed', $response['statusCode']);
            case 416:
                throw new Horde_Dav_Exception('Requested Range Not Satisfiable', $response['statusCode']);
            case 500:
                throw new Horde_Dav_Exception('Internal server error', $response['statusCode']);
            case 501:
                throw new Horde_Dav_Exception('Not Implemented', $response['statusCode']);
            case 507:
                throw new Horde_Dav_Exception('Insufficient storage', $response['statusCode']);
            default:
                throw new Horde_Dav_Exception('HTTP error response. (errorcode ' . $response['statusCode'] . ')', $response['statusCode']);
            }
        }

        return $response;
    }
}

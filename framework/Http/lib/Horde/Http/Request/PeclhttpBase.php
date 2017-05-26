<?php
/**
 * Copyright 2007-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Michael Cramer <michael@bigmichi1.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Http
 */

/**
 * Base HTTP request object for the pecl_http backends.
 *
 * @author    Michael Cramer <michael@bigmichi1.de>
 * @category  Horde
 * @copyright 2007-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Http
 */
abstract class Horde_Http_Request_PeclhttpBase extends Horde_Http_Request_Base
{
    /**
     * Map of HTTP authentication schemes from Horde_Http constants to
     * implementation specific constants.
     *
     * @var array
     */
    protected $_httpAuthSchemes = array();

    /**
     * Map of proxy types from Horde_Http to implementation specific constants.
     *
     * @var array
     */
    protected $_proxyTypes = array();

    /**
     * Translates a Horde_Http::AUTH_* constant to implementation specific
     * constants.
     *
     * @param string $httpAuthScheme  A Horde_Http::AUTH_* constant.
     *
     * @return const An implementation specific authentication scheme constant.
     * @throws Horde_Http_Exception
     */
    protected function _httpAuthScheme($httpAuthScheme)
    {
        if (!isset($this->_httpAuthSchemes[$httpAuthScheme])) {
            throw new Horde_Http_Exception('Unsupported authentication scheme (' . $httpAuthScheme . ')');
        }
        return $this->_httpAuthSchemes[$httpAuthScheme];
    }

    /**
     * Translates a Horde_Http::PROXY_* constant to implementation specific
     * constants.
     *
     * @return const
     * @throws Horde_Http_Exception
     */
    protected function _proxyType()
    {
        $proxyType = $this->proxyType;
        if (!isset($this->_proxyTypes[$proxyType])) {
            throw new Horde_Http_Exception('Unsupported proxy type (' . $proxyType . ')');
        }
        return $this->_proxyTypes[$proxyType];
    }

    /**
     * Generates the HTTP options for the request.
     *
     * @return array array with options
     * @throws Horde_Http_Exception
     */
    protected function _httpOptions()
    {
        // Set options
        $httpOptions = array(
            'headers' => $this->headers,
            'redirect' => (int)$this->redirects,
            'ssl' => array(
                'verifypeer' => $this->verifyPeer,
                'verifyhost' => $this->verifyPeer
            ),
            'timeout' => $this->timeout,
            'useragent' => $this->userAgent
        );

        // Proxy settings
        if ($this->proxyServer) {
            $httpOptions['proxyhost'] = $this->proxyServer;
            if ($this->proxyPort) {
                $httpOptions['proxyport'] = $this->proxyPort;
            }
            if ($this->proxyUsername && $this->proxyPassword) {
                $httpOptions['proxyauth'] = $this->proxyUsername . ':' . $this->proxyPassword;
                $httpOptions['proxyauthtype'] = $this->_httpAuthScheme($this->proxyAuthenticationScheme);
            }
            if ($this->proxyType == Horde_Http::PROXY_SOCKS4 || $this->proxyType == Horde_Http::PROXY_SOCKS5) {
                $httpOptions['proxytype'] = $this->_proxyType();
            } else if ($this->proxyType != Horde_Http::PROXY_HTTP) {
                throw new Horde_Http_Exception(sprintf('Proxy type %s not supported by this request type!', $this->proxyType));
            }
        }

        // Authentication settings
        if ($this->username) {
            $httpOptions['httpauth'] = $this->username . ':' . $this->password;
            $httpOptions['httpauthtype'] = $this->_httpAuthScheme($this->authenticationScheme);
        }

        return $httpOptions;
    }
}

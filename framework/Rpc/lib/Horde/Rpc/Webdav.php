<?php
/**
 * The Horde_Rpc_Webdav class provides a WebDAV implementation of the
 * Horde RPC system.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * Derived from the HTTP_WebDAV_Server PEAR package:
 * +------------------------------------------------------------------------+
 * | Portions Copyright 2002-2007 Christian Stocker, Hartmut Holzgraefe |
 * | All rights reserved                                                    |
 * |                                                                        |
 * | Redistribution and use in source and binary forms, with or without     |
 * | modification, are permitted provided that the following conditions     |
 * | are met:                                                               |
 * |                                                                        |
 * | 1. Redistributions of source code must retain the above copyright      |
 * |    notice, this list of conditions and the following disclaimer.       |
 * | 2. Redistributions in binary form must reproduce the above copyright   |
 * |    notice, this list of conditions and the following disclaimer in     |
 * |    the documentation and/or other materials provided with the          |
 * |    distribution.                                                       |
 * | 3. The names of the authors may not be used to endorse or promote      |
 * |    products derived from this software without specific prior          |
 * |    written permission.                                                 |
 * |                                                                        |
 * | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS    |
 * | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT      |
 * | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS      |
 * | FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE         |
 * | COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,    |
 * | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,   |
 * | BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;       |
 * | LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER       |
 * | CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT     |
 * | LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN      |
 * | ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE        |
 * | POSSIBILITY OF SUCH DAMAGE.                                            |
 * +------------------------------------------------------------------------+
 *
 * Portions Copyright 2004-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Ben Klang <ben@alkaloid.net>
 * @author  Hartmut Holzgraefe
 * @author  Christian Stocker
 * @package Rpc
 */
class Horde_Rpc_Webdav extends Horde_Rpc
{
    /**
     * CalDAV XML namespace
     *
     * @var string
     */
    const CALDAVNS = 'urn:ietf:params:xml:ns:caldav';

    /**
     * Realm string to be used in authentification popups
     *
     * @var string
     */
    var $http_auth_realm = 'Horde WebDAV';

    /**
     * String to be used in "X-Dav-Powered-By" header
     *
     * @var string
     */
    var $dav_powered_by = 'Horde WebDAV Server';

    /**
     * success state flag
     *
     * @var bool
     * @access public
     */
    var $parseSuccess = false;

    /**
     * found properties are collected here
     *
     * @var array
     * @access public
     */
    var $parseProps = false;

    /**
     * internal tag nesting depth counter
     *
     * @var int
     * @access private
     */
    var $parseDepth = 0;

    /**
     * lock type, currently only "write"
     *
     * @var string
     * @access public
     */
    var $locktype = "";

    /**
     * lock scope, "shared" or "exclusive"
     *
     * @var string
     * @access public
     */
    var $lockscope = "";

    /**
     * lock owner information
     *
     * @var string
     * @access public
     */
    var $owner = "";

    /**
     * flag that is set during lock owner read
     *
     * @var bool
     * @access private
     */
    var $collect_owner = false;

    /**
     *
     *
     * @var
     * @access
     */
    var $mode;

    /**
     *
     *
     * @var
     * @access
     */
    var $current;

    /**
     * complete URI for this request
     *
     * @var string
     */
    var $uri;


    /**
     * base URI for this request
     *
     * @var string
     */
    var $base_uri;


    /**
     * URI path for this request
     *
     * @var string
     */
    var $path;

    /**
     * Remember parsed If: (RFC2518/9.4) header conditions
     *
     * @var array
     */
    var $_if_header_uris = array();

    /**
     * HTTP response status/message
     *
     * @var string
     */
    var $_http_status = "200 OK";

    /**
     * Copy of $_SERVER superglobal array
     *
     * Derived classes may extend the constructor to
     * modify its contents
     *
     * @var array
     */
    var $_SERVER;

    /**
     * Mapping of XML namespaces to their XML nickname
     *
     * @var array
     */
    var $ns_hash = array('DAV:' => 'D');

    /**
     * Xml_Element object
     * @var object
     */
    var $_xml;

    /**
     * WebDav server constructor.
     *
     * @access private
     */
    public function __construct($request, $params = array())
    {
        // PHP messages destroy XML output -> switch them off
        ini_set('display_errors', 0);

        // copy $_SERVER variables to local _SERVER array
        // so that derived classes can simply modify these
        $this->_SERVER = $_SERVER;

        parent::__construct($request, $params);
    }

    /**
     * WebDAV handles authentication internally, so bypass the
     * system-level auth check by just returning true here.
     */
    function authorize()
    {
        return true;
    }

    /**
     * If the webdav backend is used, the input should not be read, it is
     * being read by HTTP_WebDAV_Server.
     */
    function getInput()
    {
    }

    /**
     * Sends an RPC request to the server and returns the result.
     *
     * @param string  The raw request string.
     *
     * @return string  The XML encoded response from the server.
     */
    function getResponse($request)
    {
        $this->ServeRequest();
        exit;
    }

    /**
     * GET implementation.
     *
     * @param array $options  Array of input and output parameters.
     * <br><strong>input</strong><ul>
     * <li> path -
     * </ul>
     * <br><strong>output</strong><ul>
     * <li> size -
     * </ul>
     *
     * @return string|boolean  HTTP-Statuscode.
     */
    function GET(&$options)
    {
        if ($options['path'] == '/') {
            $options['mimetype'] = 'httpd/unix-directory';
        } else {
            // Ensure we only retrieve the exact item
            $options['depth'] = 0;
            try {
                $result = $this->_list($options, false);
                if ($result === false) {
                    return '404 File Not Found';
                }
            } catch (Horde_Rpc_Exception $e) {
                if ($e->getCode()) {
                    // Allow called applications to set the result code
                    return $this->_checkHTTPCode($e->getCode())
                        . ' ' . $e->getMessage();
                }
                return '500 Internal Server Error';
            }
            $options = $result;
        }

        return true;
    }

    /**
     * PUT implementation.
     *
     * @param array &$options  Parameter passing array.
     *
     * @return string|boolean  HTTP-Statuscode.
     */
    function PUT(&$options)
    {
        $path = trim($options['path'], '/');

        if (empty($path)) {
            return '403 PUT requires a path.';
        }

        $pieces = explode('/', $path);

        if (count($pieces) < 2 || empty($pieces[0])) {
            return '403 PUT denied outside of application directories.';
        }

        $content = '';
        while (!feof($options['stream'])) {
            $content .= fgets($options['stream']);
        }

        try {
            $GLOBALS['registry']->callByPackage($pieces[0], 'put', array('path' => $path, 'content' => $content, 'type' => $options['content_type']));
        } catch (Horde_Exception $e) {
            Horde::logMessage($e, 'ERR');
            if ($e->getCode()) {
                return $this->_checkHTTPCode($e->getCode()) . ' ' . $result->getMessage();
            }

            return '500 Internal Server Error. Check server logs';
        }

        return true;
    }

    /**
     * Performs a WebDAV DELETE.
     *
     * Deletes a single object from a database. The path passed in must
     * be in [app]/[path] format.
     *
     * @see HTTP_WebDAV_Server::http_DELETE()
     *
     * @param array $options An array of parameters from the setup
     * method in HTTP_WebDAV_Server.
     *
     * @return string|boolean  HTTP-Statuscode.
     */
    function DELETE($options)
    {
        $path = $options['path'];
        $pieces = explode('/', trim($this->path, '/'), 2);

        if (count($pieces) != 2) {
            Horde::logMessage(sprintf(Horde_Rpc_Translation::t("Error deleting from path %s; must be [app]/[path]", $options['path'])), 'INFO');
            return '403 Must supply a resource within the application to delete.';
        }

        $app = $pieces[0];
        $path = $pieces[1];

        // TODO: Support HTTP/1.1 If-Match on ETag here

        // Delete access is checked in each app.
        try {
            $GLOBALS['registry']->callByPackage($app, 'path_delete', array($path));
        } catch (Horde_Exception $e) {
            Horde::logMessage($e, 'INFO');
            if ($e->getCode()) {
                return $this->_checkHTTPCode($e->getCode()) . ' ' . $e->getMessage();
            }

            return '500 Internal Server Error. Check server logs';
        }

        return '204 No Content';
    }

    /**
     * PROPFIND method handler
     *
     * @param array $options  General parameter passing array.
     * @param array &$files   Return array for file properties.
     *
     * @return boolean  True on success.
     */
    function PROPFIND($options, &$files)
    {
        // Always return '404 File Not Found';
        // Work around HTTP_WebDAV_Server behavior.
        // See: http://pear.php.net/bugs/bug.php?id=11390
        try {
            $list = $this->_list($options, true);
            if ($list === false) {
                return false;
            }
        } catch (Horde_Rpc_Exception $e) {
            return false;
        }
        $files['files'] = $list;
        return true;
    }

    /**
     * MKCOL method handler
     *
     * @param array $options
     * @return string HTTP response string
     */
    function MKCOL($options)
    {
        $path = $options['path'];
        if (substr($path, 0, 1) == '/') {
            $path = substr($path, 1);
        }

        // Take the module name from the path
        $pieces = explode('/', $path, 2);
        if (count($pieces) != 2) {
            Horde::logMessage(sprintf(Horde_Rpc_Translation::t("Unable to create directory %s; must be [app]/[path]"), $path), 'INFO');
            return '403 Must specify a resource within an application.  MKCOL disallowed at top level.';
        }

        // Send the request to the application
        try {
            $GLOBALS['registry']->callByPackage($pieces[0], 'mkcol', array('path' => $path));
        } catch (Horde_Exception $e) {
            Horde::logMessage($result, 'ERR');
            if ($e->getCode()) {
                return $this->_checkHTTPCode($e->getCode()) . ' ' . $e->getMessage();
            }

            return '500 Internal Server Error. Check server logs';
        }

        return '200 OK';
    }

    /**
     * MOVE method handler
     *
     * @param array $options
     * @return string HTTP response string
     */
    function MOVE($options)
    {
        $path = $options['path'];
        if (substr($path, 0, 1) == '/') {
            $path = substr($path, 1);
        }

        // Take the module name from the path
        $sourcePieces = explode('/', $path, 2);
        if (count($sourcePieces) != 2) {
            Horde::logMessage(sprintf(Horde_Rpc_Translation::t("Unable to rename %s; must be [app]/[path] and within the same application."), $path), 'INFO');
            return '403 Must specify a resource within an application.  MOVE disallowed at top level.';
        }

        $destPieces = explode('/', $options['dest'], 2);
        if (!(count($destPieces) == 2) || $sourcesPieces[0] != $destPieces[0]) {
            return '400 Can not move across applications.';
        }

        // Send the request to the module
        try {
            $GLOBALS['registry']->callByPackage($sourcePieces[0], 'move', array('path' => $path, 'dest' => $options['dest']));
        } catch (Horde_Exception $e) {
            Horde::logMessage($e, 'ERR');
            if ($e->getCode()) {
                return $this->_checkHTTPCode($e->getCode()) . ' ' . $e->getMessage();
            }

            return '500 Internal Server Error. Check server logs';
        }

        return '200 OK';
    }

    /**
     * Generates a response to a GET or PROPFIND request.
     *
     * @param array $options        Array of WebDAV options
     * @param boolean $propperties  Whether to only return properties or actual
     *                              data.
     *
     * @return mixed  Array of objects with properties if the request is a dir,
     *                array of file metadata + data if request is a file,
     *                false if the object is not found.
     * @throws Horde_Exception
     */
    function _list($options, $properties)
    {
        global $registry;

        // $path (or $options['path']) is the node on which we will list
        // collections and resources.  $this->path is the path of the original
        // request from the client.
        $path = $options['path'];
        $depth = $options['depth'];

        // $list will contain the data to return to the client
        $list = array();

        if ($path == '/') {
            // $root is a virtual collection describing the root of the Horde
            // WebDAV space
            $now = time();
            $root = array('name' => '/',
                          'created' => $now,
                          'modified' => $now,
                          'contenttype' => 'httpd/unix-directory',
                          'contentlength' => 0,
                          'browseable' => true);
            $list[] = array('path' => $path,
                            'props' => $this->_getProps($options['props'], $root));

            try {
                $apps = $registry->listApps(null, false, Horde_Perms::READ);
            } catch (Horde_Exception $e) {
                Horde::logMessage($e);
                throw new Horde_Rpc_Exception($e);
            }
            foreach ($apps as $app) {
                // Only include apps that have browse() methods.
                if ($registry->hasMethod('browse', $app)) {
                    $list[] = array('path' => '/' . $app,
                                    'props' => $this->_getProps($options['props'], array_merge($root, array('name' => $registry->get('name', $app)))));
                }
            }
            return $list;
        } else {
            $path = trim($path, '/');
            $pieces = explode('/', $path);

            try {
                $items = $registry->callByPackage($pieces[0], 'browse', array('path' => $path, 'properties' => array('name', 'browseable', 'contenttype', 'contentlength', 'created', 'modified')));
            } catch (Horde_Exception $e) {
                Horde::logMessage($e);
                throw new Horde_Rpc_Exception($e);
            }

            if ($items === false) {
                // File not found
                return $items;
            }
            if (empty($items)) {
                // No content exists at this level.
                return array();
            }
            if (!is_array(reset($items))) {
                /* A one-dimensional array means we have an actual object with
                 * data to return to the client. */
                if ($properties) {
                    $props = $this->_getProps($options['props'], $items);
                    $items = array(array('path' => $this->path,
                                         'props' => $props));
                }
                return $items;
            }

            /* A directory full of objects has been returned. */
            foreach ($items as $sub_path => $i) {
                $props = $this->_getProps($options['props'], $i);

                $item = array('path' => '/' . $sub_path,
                              'props' => $props);
                $list[] = $item;
            }
        }

        return $list;
    }

    /**
     * Given a set of requested properties ($reqprops) and an items holding
     * properties, return a list of properties and values from the item that
     * were requested.
     *
     * @param array  $reqprops List of requested properties
     * @param array  $item     Item with properties to be filtered
     *
     * @return array           List of filtered properties and values
     */
    function _getProps($reqprops, $item)
    {
        $props = array();
        $properties = array();
        foreach ($reqprops as $prop) {
            if (!isset($properties[$prop['xmlns']])) {
                $properties[$prop['xmlns']] = array();
            }
            $properties[$prop['xmlns']][$prop['name']] = $prop['name'];
        }

        // Handle certain standard properties specially
        if (in_array('displayname', $properties['DAV:'])) {
            $props[] = $this->mkprop('displayname', $item['name']);
            unset($properties['DAV:']['displayname']);
        }
        if (in_array('resourcetype', $properties['DAV:'])) {
            $props[] = $this->mkprop('resourcetype', $item['browseable'] ? 'collection' : '');
            unset($properties['DAV:']['resourcetype']);
        }
        if (in_array('getcontenttype', $properties['DAV:'])) {
            $props[] = $this->mkprop('getcontenttype', empty($item['contenttype']) ? 'application/octet-stream' : $item['contenttype']);
            unset($properties['DAV:']['getcontenttype']);
        }
        if (in_array('getcontentlength', $properties['DAV:'])) {
            if (empty($item['contentlength']) && empty($item['data'])) {
                $size = 0;
            } else {
                $size = empty($item['contentlength']) ? strlen($item['data']) : $item['contentlength'];
            }
            $props[] = $this->mkprop('getcontentlength', $size);
            unset($properties['DAV:']['getcontentlength']);
        }
        if (in_array('creationdate', $properties['DAV:'])) {
            $props[] = $this->mkprop('creationdate', empty($item['created']) ? time() : $item['created']);
            unset($properties['DAV:']['creationdate']);
        }
        if (in_array('getlastmodified', $properties['DAV:'])) {
            $props[] = $this->mkprop('getlastmodified', empty($item['modified']) ? time() : $item['modified']);
            unset($properties['DAV:']['getlastmodified']);
        }

        if (isset($properties[self::CALDAVNS])) {
            if (in_array('calendar-home-set', $properties[self::CALDAVNS]) &&
                isset($item[self::CALDAVNS . ':calendar-home-set'])) {
                $calendar_home_set = array();
                foreach ($item[self::CALDAVNS . ':calendar-home-set'] as $calUrl) {
                    $calendar_home_set[] = $this->mkprop('href', $calUrl);
                }
                $props[] = $this->mkprop('caldav', 'calendar-home-set', $calendar_home_set);
                unset($properties[self::CALDAVNS]['calendar-home-set']);
            }

            if (in_array('calendar-user-address-set', $properties[self::CALDAVNS]) &&
                isset($item[self::CALDAVNS . ':calendar-user-address-set'])) {
                $calendar_user_address_set = array();
                foreach ($item[self::CALDAVNS . ':calendar-user-address-set'] as $userAddress) {
                    $calendar_user_address_set[] = $this->mkprop('href', $userAddress);
                }
                $props[] = $this->mkprop('caldav', 'calendar-user-address-set', $calendar_user_address_set);
                unset($properties[self::CALDAVNS]['calendar-user-address-set']);
            }
        }

        // Handle any other requested properties genericly
        $itemprops = array_keys($item);
        foreach (array_keys($properties) as $xmlns) {
            foreach ($properties[$xmlns] as $propname) {
                if ($xmlns != 'DAV:') {
                    $propname = $xmlns . ':' . $propname;
                }
                if (in_array($propname, $itemprops)) {
                    $props[] = $this->mkprop($xmlns, $propname, $item[$propname]);
                }
            }
        }

        return $props;
    }

    /**
     * Attempts to set a lock on a specified resource.
     *
     * @param array &$params  Reference to array of parameters.  These
     *                        parameters should be overwritten with the lock
     *                        information.
     *
     * @return int            HTTP status code
     */
    function LOCK(&$params)
    {
        if (!isset($GLOBALS['conf']['lock']['driver']) ||
            $GLOBALS['conf']['lock']['driver'] == 'none') {
            return 500;
        }

        if (empty($params['path'])) {
            Horde::logMessage('Empty path supplied to LOCK()', 'ERR');
            return 403;
        }
        if ($params['path'] == '/') {
            // Locks are always denied to the root directory
            return 403;
        }
        if (isset($params['depth']) && $params['depth'] == 'infinity') {
            // For now we categorically disallow recursive locks
            return 403;
        }

        if (!is_array($params['timeout']) || count($params['timeout']) != 1) {
            // Unexpected timeout parameter.  Assume 600 seconds.
            $timeout = 600;
        }
        $tmp = explode('-', $params['timeout'][0]);
        if (count($tmp) != 2) {
            // Unexpected timeout parameter.  Assume 600 seconds.
            $timeout = 600;
        }
        if (strtolower($tmp[0]) == 'second') {
            $timeout = $tmp[1];
        } else {
            // Unexpected timeout parameter.  Assume 600 seconds.
            $timeout = 600;
        }

        try {
            $locks = $GLOBALS['injector']->getInstance('Horde_Lock');
        } catch (Horde_Lock_Exception $e) {
            Horde::logMessage($e, 'ERR');
            return 500;
        }

        $locktype = Horde_Lock::TYPE_SHARED;
        if ($params['scope'] == 'exclusive') {
            $locktype = Horde_Lock::TYPE_EXCLUSIVE;
        }

        try {
            $lockid = $locks->setLock($GLOBALS['registry']->getAuth(), 'webdav', $params['path'],
                                      $timeout, $locktype);
        } catch (Horde_Lock_Exception $e) {
            Horde::logMessage($e, 'ERR');
            return 500;
        }
        if ($lockid === false) {
            // Resource is already locked.
            return 423;
        }

        $params['locktoken'] = $lockid;
        $params['owner'] = $GLOBALS['registry']->getAuth();
        $params['timeout'] = $timeout;

        return "200";
    }

    /**
     * Attempts to remove a specified lock.
     *
     * @param array &$params  Reference to array of parameters.  These
     *                        parameters should be overwritten with the lock
     *                        information.
     *
     * @return int            HTTP status code
     */
    function UNLOCK(&$params)
    {
        if (!isset($GLOBALS['conf']['lock']['driver']) ||
            $GLOBALS['conf']['lock']['driver'] == 'none') {
            return 500;
        }

        try {
            $locks = $GLOBALS['injector']->getInstance('Horde_Lock');
        } catch (Horde_Lock_Exception $e) {
            Horde::logMessage($e, 'ERR');
            return 500;
        }

        try {
            $res = $locks->clearLock($params['token']);
        } catch (Horde_Lock_Exception $e) {
            Horde::logMessage($e, 'ERR');
            return 500;
        }
        if ($res === false) {
            Horde::logMessage('clearLock() returned false', 'ERR');
            // Something else has failed:  424 (Method Failure)
            return 424;
        }

        // Lock cleared.  Use 204 (No Content) instead of 200 because there is
        // no lock information to return to the client.
        return 204;
    }

    function checkLock($resource)
    {
        if (!isset($GLOBALS['conf']['lock']['driver']) ||
            $GLOBALS['conf']['lock']['driver'] == 'none') {
            Horde::logMessage('WebDAV locking failed because no lock driver has been configured.', 'WARN');
            return false;
        }

        try {
            $locks = $GLOBALS['injector']->getInstance('Horde_Lock');
        } catch (Horde_Lock_Exception $e) {
            Horde::logMessage($e, 'ERR');
            return false;
        }

        try {
            $res =  $locks->getLocks('webdav', $resource);
        } catch (Horde_Lock_Exception $e) {
            Horde::logMessage($e, 'ERR');
            return false;
        }

        if (empty($res)) {
            // No locks found.
            return $res;
        }

        // WebDAV only supports one lock.  Return the first lock.
        $lock = reset($res);

        // Format the array keys for HTTP_WebDAV_Server
        $ret = array();
        if ($lock['lock_type'] == Horde_Lock::TYPE_EXCLUSIVE) {
            $ret['scope'] = 'exclusive';
        } else {
            $ret['scope'] = 'shared';
        }
        $ret['type'] = 'write';
        $ret['expires'] = $lock['lock_expiry_timestamp'];
        $ret['token'] = $lock['lock_id'];
        $ret['depth'] = 1;

        return $ret;
    }

    /**
     * Check authentication. We always return true here since we
     * handle permissions based on the resource that's requested, but
     * we do record the authenticated user for later use.
     *
     * @param string $type      Authentication type, e.g. "basic" or "digest"
     * @param string $username  Transmitted username.
     * @param string $password  Transmitted password.
     *
     * @return boolean  Authentication status. Always true.
     */
    function check_auth($type, $username, $password)
    {
        $auth = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Auth')->create();
        return $auth->authenticate($username, array('password' => $password));
    }

    /**
     * Make sure the error code returned in the PEAR_Error object is a valid
     * HTTP response code.
     *
     * This is necessary because in pre-Horde 3.2 apps the response codes are
     * not sanitized.  This backward compatibility check can be removed when
     * we drop support for pre-3.2 apps.  Intentionally, not every valid HTTP
     * code is listed here.  Only common ones are here to reduce the
     * possibility of an invalid code being confused with a valid HTTP code.
     *
     * @todo Remove for Horde 4.0
     *
     * @param integer $code  Status code to check for validity.
     *
     * @return integer  Either the original code if valid or 500 for internal
     *                  server error.
     */
    function _checkHTTPcode($code)
    {
        $valid = array(200, // OK
                       201, // Created
                       202, // Accepted
                       204, // No Content
                       301, // Moved Permanently
                       302, // Found
                       304, // Not Modified
                       307, // Temporary Redirect
                       400, // Bad Request
                       401, // Unauthorized
                       403, // Forbidden
                       404, // Not Found
                       405, // Method Not Allowed
                       406, // Not Acceptable
                       408, // Request Timeout
                       413, // Request Entity Too Large
                       415, // Unsupported Media Type
                       500, // Internal Server Error
                       501, // Not Implemented
                       503, // Service Unavailable
        );
        if (in_array($code, $valid)) {
            return $code;
        } else {
            return 500;
        }
    }

    /**
     * Serve WebDAV HTTP request
     *
     * dispatch WebDAV HTTP request to the apropriate method handler
     *
     * @param  void
     * @return void
     */
    function ServeRequest()
    {
        // prevent warning in litmus check 'delete_fragment'
        if (strstr($this->_SERVER["REQUEST_URI"], '#')) {
            $this->http_status("400 Bad Request");
            return;
        }

        // default uri is the complete request uri
        $script_name = preg_replace('/index.php$/', '', $this->_SERVER["SCRIPT_NAME"]);
        $uri = "http";
        if (isset($this->_SERVER["HTTPS"]) && $this->_SERVER["HTTPS"] === "on") {
          $uri = "https";
        }
        $uri.= "://".$this->_SERVER["HTTP_HOST"].$script_name;

        // WebDAV has no concept of a query string and clients (including cadaver)
        // seem to pass '?' unencoded, so we need to extract the path info out
        // of the request URI ourselves
        $path_info = substr($this->_SERVER["REQUEST_URI"], strlen($script_name));

        // just in case the path came in empty ...
        if (empty($path_info)) {
            $path_info = "/";
        }

        $this->uri = $this->base_uri = $uri;
        if (substr($uri, -1) == '/') {
            $this->uri = substr($this->uri, 0, -1);
        }
        $this->uri .= $path_info;

        // set path
        $this->path = $this->_urldecode($path_info);
        if (!strlen($this->path)) {
            if ($this->_SERVER["REQUEST_METHOD"] == "GET") {
                // redirect clients that try to GET a collection
                // WebDAV clients should never try this while
                // regular HTTP clients might ...
                header("Location: ".$this->base_uri."/");
                return;
            } else {
                // if a WebDAV client didn't give a path we just assume '/'
                $this->path = "/";
            }
        }

        if (ini_get("magic_quotes_gpc")) {
            $this->path = stripslashes($this->path);
        }


        // identify ourselves
        if (empty($this->dav_powered_by)) {
            header("X-Dav-Powered-By: PHP class: ".get_class($this));
        } else {
            header("X-Dav-Powered-By: ".$this->dav_powered_by);
        }

        // check authentication
        // for the motivation for not checking OPTIONS requests on / see
        // http://pear.php.net/bugs/bug.php?id=5363
        if ( (   !(($this->_SERVER['REQUEST_METHOD'] == 'OPTIONS') && ($this->path == "/")))
             && (!$this->_check_auth())) {
            // RFC2518 says we must use Digest instead of Basic
            // but Microsoft Clients do not support Digest
            // and we don't support NTLM and Kerberos
            // so we are stuck with Basic here
            header('WWW-Authenticate: Basic realm="'.($this->http_auth_realm).'"');

            // Windows seems to require this being the last header sent
            // (changed according to PECL bug #3138)
            $this->http_status('401 Unauthorized');

            return;
        }

        // check
        if (! $this->_check_if_header_conditions()) {
            return;
        }

        // detect requested method names
        $method  = strtolower($this->_SERVER["REQUEST_METHOD"]);
        $wrapper = "http_".$method;

        // activate HEAD emulation by GET if no HEAD method found
        if ($method == "head" && !method_exists($this, "head")) {
            $method = "get";
        }

        if (method_exists($this, $wrapper) && ($method == "options" || method_exists($this, $method))) {
            $this->$wrapper();  // call method by name
        } else { // method not found/implemented
            if ($this->_SERVER["REQUEST_METHOD"] == "LOCK") {
                $this->http_status("412 Precondition failed");
            } else {
                $this->http_status("405 Method not allowed");
                header("Allow: ".join(", ", $this->_allow()));  // tell client what's allowed
            }
        }
    }

    // }}}

    // {{{ abstract WebDAV methods

    // {{{ GET()
    /**
     * GET implementation
     *
     * overload this method to retrieve resources from your server
     * <br>
     *
     *
     * @abstract
     * @param array &$params Array of input and output parameters
     * <br><b>input</b><ul>
     * <li> path -
     * </ul>
     * <br><b>output</b><ul>
     * <li> size -
     * </ul>
     * @returns int HTTP-Statuscode
     */

    /* abstract
     function GET(&$params)
     {
     // dummy entry for PHPDoc
     }
    */

    // }}}

    // {{{ PUT()
    /**
     * PUT implementation
     *
     * PUT implementation
     *
     * @abstract
     * @param array &$params
     * @returns int HTTP-Statuscode
     */

    /* abstract
     function PUT()
     {
     // dummy entry for PHPDoc
     }
    */

    // }}}

    // {{{ COPY()

    /**
     * COPY implementation
     *
     * COPY implementation
     *
     * @abstract
     * @param array &$params
     * @returns int HTTP-Statuscode
     */

    /* abstract
     function COPY()
     {
     // dummy entry for PHPDoc
     }
    */

    // }}}

    // {{{ MOVE()

    /**
     * MOVE implementation
     *
     * MOVE implementation
     *
     * @abstract
     * @param array &$params
     * @returns int HTTP-Statuscode
     */

    /* abstract
     function MOVE()
     {
     // dummy entry for PHPDoc
     }
    */

    // }}}

    // {{{ DELETE()

    /**
     * DELETE implementation
     *
     * DELETE implementation
     *
     * @abstract
     * @param array &$params
     * @returns int HTTP-Statuscode
     */

    /* abstract
     function DELETE()
     {
     // dummy entry for PHPDoc
     }
    */
    // }}}

    // {{{ PROPFIND()

    /**
     * PROPFIND implementation
     *
     * PROPFIND implementation
     *
     * @abstract
     * @param array &$params
     * @returns int HTTP-Statuscode
     */

    /* abstract
     function PROPFIND()
     {
     // dummy entry for PHPDoc
     }
    */

    // }}}

    // {{{ PROPPATCH()

    /**
     * PROPPATCH implementation
     *
     * PROPPATCH implementation
     *
     * @abstract
     * @param array &$params
     * @returns int HTTP-Statuscode
     */

    /* abstract
     function PROPPATCH()
     {
     // dummy entry for PHPDoc
     }
    */
    // }}}

    // {{{ LOCK()

    /**
     * LOCK implementation
     *
     * LOCK implementation
     *
     * @abstract
     * @param array &$params
     * @returns int HTTP-Statuscode
     */

    /* abstract
     function LOCK()
     {
     // dummy entry for PHPDoc
     }
    */
    // }}}

    // {{{ UNLOCK()

    /**
     * UNLOCK implementation
     *
     * UNLOCK implementation
     *
     * @abstract
     * @param array &$params
     * @returns int HTTP-Statuscode
     */

    /* abstract
     function UNLOCK()
     {
     // dummy entry for PHPDoc
     }
    */
    // }}}

    // }}}

    // {{{ other abstract methods

    // {{{ check_auth()

    /**
     * check authentication
     *
     * overload this method to retrieve and confirm authentication information
     *
     * @abstract
     * @param string type Authentication type, e.g. "basic" or "digest"
     * @param string username Transmitted username
     * @param string passwort Transmitted password
     * @returns bool Authentication status
     */

    /* abstract
     function checkAuth($type, $username, $password)
     {
     // dummy entry for PHPDoc
     }
    */

    // }}}

    // {{{ checklock()

    /**
     * check lock status for a resource
     *
     * overload this method to return shared and exclusive locks
     * active for this resource
     *
     * @abstract
     * @param string resource Resource path to check
     * @returns array An array of lock entries each consisting
     *                of 'type' ('shared'/'exclusive'), 'token' and 'timeout'
     */

    /* abstract
     function checklock($resource)
     {
     // dummy entry for PHPDoc
     }
    */

    // }}}

    // }}}

    // {{{ WebDAV HTTP method wrappers

    // {{{ http_OPTIONS()

    /**
     * OPTIONS method handler
     *
     * The OPTIONS method handler creates a valid OPTIONS reply
     * including Dav: and Allowed: headers
     * based on the implemented methods found in the actual instance
     *
     * @param  void
     * @return void
     */
    function http_OPTIONS()
    {
        // Microsoft clients default to the Frontpage protocol
        // unless we tell them to use WebDAV
        header("MS-Author-Via: DAV");

        // get allowed methods
        $allow = $this->_allow();

        // dav header
        $dav = array(1);        // assume we are always dav class 1 compliant
        if (isset($allow['LOCK'])) {
            $dav[] = 2;         // dav class 2 requires that locking is supported
        }

        // tell clients what we found
        $this->http_status("200 OK");
        header("DAV: "  .join(", ", $dav));
        header("Allow: ".join(", ", $allow));

        header("Content-length: 0");
    }

    // }}}


    // {{{ http_PROPFIND()

    /**
     * PROPFIND method handler
     *
     * @param  void
     * @return void
     */
    function http_PROPFIND()
    {
        $options = Array();
        $files   = Array();

        $options["path"] = $this->path;

        // search depth from header (default is "infinity)
        if (isset($this->_SERVER['HTTP_DEPTH'])) {
            $options["depth"] = $this->_SERVER["HTTP_DEPTH"];
        } else {
            $options["depth"] = "infinity";
        }

        // analyze request payload
        $propinfo = $this->_parse_propfind("php://input");
        if (!$this->parseSuccess) {
            $this->http_status("400 Error");
            return;
        }
        $options['props'] = $this->parseProps;

        // call user handler
        if (!$this->PROPFIND($options, $files)) {
            $files = array("files" => array());
            if (method_exists($this, "checkLock")) {
                // is locked?
                $lock = $this->checkLock($this->path);

                if (is_array($lock) && count($lock)) {
                    $created          = isset($lock['created'])  ? $lock['created']  : time();
                    $modified         = isset($lock['modified']) ? $lock['modified'] : time();
                    $files['files'][] = array("path"  => $this->_slashify($this->path),
                                              "props" => array($this->mkprop("displayname",      $this->path),
                                                               $this->mkprop("creationdate",     $created),
                                                               $this->mkprop("getlastmodified",  $modified),
                                                               $this->mkprop("resourcetype",     ""),
                                                               $this->mkprop("getcontenttype",   ""),
                                                               $this->mkprop("getcontentlength", 0))
                                              );
                }
            }

            if (empty($files['files'])) {
                $this->http_status("404 Not Found");
                return;
            }
        }

        $this->_xml = new Horde_Xml_Element('<D:multistatus xmlns:D="DAV:"/>');
        $this->_xml->registerNamespace('D', "DAV:");
        $this->_xml->registerNamespace('caldav', self::CALDAVNS);
        // Microsoft Clients need this special namespace for date and
        // time values
        //$this->_xml->registerNamespace('xmldata', "urn:uuid:c2f41010-65b3-11d1-a29f-00aa00c14882/");

        // now we loop over all returned file entries
        foreach ($files["files"] as $filekey => $file) {

            // nothing to do if no properties were returend for a file
            if (!isset($file["props"]) || !is_array($file["props"])) {
                continue;
            }

            // now loop over all returned properties
            foreach ($file["props"] as $key => $prop) {
                // as a convenience feature we do not require that user handlers
                // restrict returned properties to the requested ones
                // here we strip all unrequested entries out of the response

                switch($options['props']) {
                case "all":
                    // nothing to remove
                    break;

                case "names":
                    // only the names of all existing properties were requested
                    // so we remove all values
                    unset($files["files"][$filekey]["props"][$key]["val"]);
                    break;

                default:
                    $found = false;

                    // search property name in requested properties
                    foreach ((array)$options["props"] as $reqprop) {
                        if (!isset($reqprop["xmlns"])) {
                            $reqprop["xmlns"] = "";
                        }
                        if (   $reqprop["name"]  == $prop["name"]
                               && $reqprop["xmlns"] == $prop["ns"]) {
                            $found = true;
                            break;
                        }
                    }

                    // unset property and continue with next one if not found/requested
                    if (!$found) {
                        $files["files"][$filekey]["props"][$key]="";
                        continue(2);
                    }
                    break;
                }

                // namespace handling
                if (empty($prop["ns"])) continue; // no namespace
                $ns = $prop["ns"];
                if ($ns == "DAV:") continue; // default namespace
                if (isset($this->ns_hash[$ns])) continue; // already known

                // register namespace
                $ns_name = "ns".(count($this->ns_hash));
                $this->ns_hash[$ns] = $ns_name;
                $this->_xml->registerNamespace($ns_name, $ns);
            }

            // we also need to add empty entries for properties that were requested
            // but for which no values where returned by the user handler
            if (is_array($options['props'])) {
                foreach ($options["props"] as $reqprop) {
                    if ($reqprop['name']=="") continue; // skip empty entries

                    $found = false;

                    if (!isset($reqprop["xmlns"])) {
                        $reqprop["xmlns"] = "";
                    }

                    // check if property exists in result
                    foreach ($file["props"] as $prop) {
                        if (   $reqprop["name"]  == $prop["name"]
                               && $reqprop["xmlns"] == $prop["ns"]) {
                            $found = true;
                            break;
                        }
                    }

                    if (!$found) {
                        if ($reqprop["xmlns"]==="DAV:" && $reqprop["name"]==="lockdiscovery") {
                            // lockdiscovery is handled by the base class
                            $files["files"][$filekey]["props"][]
                                = $this->mkprop("DAV:",
                                                "lockdiscovery",
                                                $this->lockdiscovery($files["files"][$filekey]['path']));
                        } else {
                            // add empty value for this property
                            $files["files"][$filekey]["noprops"][] =
                                $this->mkprop($reqprop["xmlns"], $reqprop["name"], "");

                            // register property namespace if not known yet
                            if ($reqprop["xmlns"] != "DAV:" && !isset($this->ns_hash[$reqprop["xmlns"]])) {
                                $ns_name = "ns".(count($this->ns_hash));
                                $this->ns_hash[$reqprop["xmlns"]] = $ns_name;
                                $this->_xml->registerNamespace($ns_name, $reqprop['xmlns']);
                            }
                        }
                    }
                }
            }
        }

        // now we generate the reply header ...
        $this->http_status("207 Multi-Status");
        header('Content-Type: text/xml; charset="utf-8"');

        // ... and payload
        foreach ($files["files"] as $file) {
            // ignore empty or incomplete entries
            if (!is_array($file) || empty($file) || !isset($file["path"])) continue;
            $path = $file['path'];
            if (!is_string($path) || $path==="") continue;

            $xmldata = array('D:response' => array());
            #echo " <D:response $ns_defs>\n";

            /* TODO right now the user implementation has to make sure
             collections end in a slash, this should be done in here
             by checking the resource attribute */
            $href = $this->_mergePaths($this->base_uri, $path);

            /* minimal urlencoding is needed for the resource path */
            $xmldata['D:response']['D:href'] = $this->_urlencode($href);
            #echo "  <D:href>$href</D:href>\n";

            // report all found properties and their values (if any)
            if (isset($file["props"]) && is_array($file["props"])) {
                #echo "  <D:propstat>\n";
                $i = 0;
                $propstats = array($i => array('D:prop' => array()));
                #echo "   <D:prop>\n";

                foreach ($file["props"] as $key => $prop) {

                    if (!is_array($prop)) continue;
                    if (!isset($prop["name"])) continue;

                    if (!isset($prop["val"]) || $prop["val"] === "" || $prop["val"] === false) {
                        // empty properties (cannot use empty() for check as "0" is a legal value here)
                        if ($prop["ns"]=="DAV:") {
                            $propstats[$i]['D:prop']['D:' . $prop['name']] = '';
                            #echo "     <D:$prop[name]/>\n";
                        } else if (!empty($prop["ns"])) {
                            $propstats[$i]['D:prop'][$this->ns_hash[$prop["ns"]].':'.$prop['name']] = '';
                            #echo "     <".$this->ns_hash[$prop["ns"]].":$prop[name]/>\n";
                        } else {
                            $propstats[$i]['D:prop'][$prop['name']] = '';
                            $propstats[$i]['D:prop'][$prop['name'] . '#xmlns'] = '';
                            #echo "     <$prop[name] xmlns=\"\"/>";
                        }
                    } else if ($prop["ns"] == "DAV:") {
                        // some WebDAV properties need special treatment
                        switch ($prop["name"]) {
                        case "creationdate":
                            $propstats[$i]['D:prop']['D:creationdate'] = gmdate("Y-m-d\\TH:i:s\\Z", $prop['val']);
                            $propstats[$i]['D:prop']['D:creationdate#xmldata:dt'] = 'dateTime.tz';
                            #echo "     <D:creationdate ns0:dt=\"dateTime.tz\">"
                            #    . gmdate("Y-m-d\\TH:i:s\\Z", $prop['val'])
                            #    . "</D:creationdate>\n";
                            break;
                        case "getlastmodified":
                            $propstats[$i]['D:prop']['D:getlastmodified'] = gmdate("D, d M Y H:i:s ", $prop['val']);
                            $propstats[$i]['D:prop']['D:getlastmodified#xmldata:dt'] = 'dateTime.rfc1123';
                            #echo "     <D:getlastmodified ns0:dt=\"dateTime.rfc1123\">"
                            #    . gmdate("D, d M Y H:i:s ", $prop['val'])
                            #    . "GMT</D:getlastmodified>\n";
                            break;
                        case "resourcetype":
                            $propstats[$i]['D:prop']['D:resourcetype']['D:'.$prop['val']] = '';
                            #echo "     <D:resourcetype><D:$prop[val]/></D:resourcetype>\n";
                            break;
                        case "supportedlock":
                            $propstats[$i]['D:prop']['D:supportedlock'] = $prop['val'];
                            #echo "     <D:supportedlock>$prop[val]</D:supportedlock>\n";
                            break;
                        case "lockdiscovery":
                            $propstats[$i]['D:prop']['D:lockdiscovery'] = $prop['val'];
                            #echo "     <D:lockdiscovery>\n";
                            #echo $prop["val"];
                            #echo "     </D:lockdiscovery>\n";
                            break;
                        // the following are non-standard Microsoft extensions to the DAV namespace
                        case "lastaccessed":
                            $propstats[$i]['D:prop']['D:lastaccessed'] = gmdate("D, d M Y H:i:s ", $prop['val']);
                            $propstats[$i]['D:prop']['D:lastaccessed#xmldata:dt'] = 'dateTime.rfc1123';
                            #echo "     <D:lastaccessed ns0:dt=\"dateTime.rfc1123\">"
                            #    . gmdate("D, d M Y H:i:s ", $prop['val'])
                            #    . "GMT</D:lastaccessed>\n";
                            break;
                        case "ishidden":
                            $propstats[$i]['D:prop']['D:ishidden'] = is_string($prop['val']) ? $prop['val'] : ($prop['val'] ? 'true' : 'false');
                            #echo "     <D:ishidden>"
                            #    . is_string($prop['val']) ? $prop['val'] : ($prop['val'] ? 'true' : 'false')
                            #    . "</D:ishidden>\n";
                            break;
                        default:
                            $propstats[$i]['D:prop']['D:'. $prop['name']] = $prop['val'];
                            #echo "     <D:$prop[name]>"
                            #    . htmlspecialchars($prop['val'])
                            #    .     "</D:$prop[name]>\n";
                            break;
                        }
                    } else {
                        list($key, $val) = $this->_prop2xml($prop);
                        $propstats[$i]['D:prop'][$key] = $val;
                        #echo $this->_prop2xml($prop);
                    }
                }

                #echo "   </D:prop>\n";
                $propstats[$i]['D:status'] = 'HTTP/1.1 200 OK';
                #echo "   <D:status>HTTP/1.1 200 OK</D:status>\n";
                #echo "  </D:propstat>\n";
            }
            // Increment to the next propstat stanza.
            $i++;

            // now report all properties requested but not found
            if (isset($file["noprops"])) {
                #echo "  <D:propstat>\n";
                $propstats[$i]['D:prop'] = array();
                #echo "   <D:prop>\n";

                foreach ($file["noprops"] as $key => $prop) {
                    if ($prop["ns"] == "DAV:") {
                        $propstats[$i]['D:prop']['D:' . $prop['name']] = '';
                        #echo "     <D:$prop[name]/>\n";
                    } else if ($prop["ns"] == "") {
                        $propstats[$i]['D:prop'][$prop['name']] = '';
                        $propstats[$i]['D:prop'][$prop['name'] . '#xmlns'] = '';
                        #echo "     <$prop[name] xmlns=\"\"/>\n";
                    } else {
                        $propstats[$i]['D:prop'][$this->ns_hash[$prop['ns']] . ':' . $prop['name']] = '';
                        #echo "     <" . $this->ns_hash[$prop["ns"]] . ":$prop[name]/>\n";
                    }
                }

                #echo "   </D:prop>\n";
                $propstats[$i]['D:status'] = 'HTTP/1.1 404 Not Found';
                #echo "   <D:status>HTTP/1.1 404 Not Found</D:status>\n";
                #echo "  </D:propstat>\n";
            }

            $xmldata['D:response']['D:propstat'] = $propstats;
            #echo " </D:response>\n";
            $this->_xml->fromArray($xmldata);
        }

        #echo "</D:multistatus>\n";
        echo $this->_xml->saveXml();
    }


    // }}}

    // {{{ http_PROPPATCH()

    /**
     * PROPPATCH method handler
     *
     * @param  void
     * @return void
     */
    function http_PROPPATCH()
    {
        if ($this->_check_lock_status($this->path)) {
            $options = Array();

            $options["path"] = $this->path;

            $propinfo = $this->_parse_proppatch("php://input");

            if (!$this->parseSuccess) {
                $this->http_status("400 Error");
                return;
            }

            $options['props'] = $this->parseProps;

            $responsedescr = $this->PROPPATCH($options);

            $this->http_status("207 Multi-Status");
            header('Content-Type: text/xml; charset="utf-8"');

            echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";

            echo "<D:multistatus xmlns:D=\"DAV:\">\n";
            echo " <D:response>\n";
            echo "  <D:href>".$this->_urlencode($this->_mergePaths($this->_SERVER["SCRIPT_NAME"], $this->path))."</D:href>\n";

            foreach ($options["props"] as $prop) {
                echo "   <D:propstat>\n";
                echo "    <D:prop><$prop[name] xmlns=\"$prop[ns]\"/></D:prop>\n";
                echo "    <D:status>HTTP/1.1 $prop[status]</D:status>\n";
                echo "   </D:propstat>\n";
            }

            if ($responsedescr) {
                echo "  <D:responsedescription>".
                    htmlspecialchars($responsedescr).
                    "</D:responsedescription>\n";
            }

            echo " </D:response>\n";
            echo "</D:multistatus>\n";
        } else {
            $this->http_status("423 Locked");
        }
    }

    // }}}


    // {{{ http_MKCOL()

    /**
     * MKCOL method handler
     *
     * @param  void
     * @return void
     */
    function http_MKCOL()
    {
        $options = Array();

        $options["path"] = $this->path;

        $stat = $this->MKCOL($options);

        $this->http_status($stat);
    }

    // }}}


    // {{{ http_GET()

    /**
     * GET method handler
     *
     * @param void
     * @returns void
     */
    function http_GET()
    {
        // TODO check for invalid stream
        $options         = Array();
        $options["path"] = $this->path;

        $this->_get_ranges($options);

        if (true === ($status = $this->GET($options))) {
            if (!headers_sent()) {
                $status = "200 OK";

                if (!isset($options['mimetype'])) {
                    $options['mimetype'] = "application/octet-stream";
                }
                header("Content-type: $options[mimetype]");

                if (isset($options['mtime'])) {
                    header("Last-modified:".gmdate("D, d M Y H:i:s ", $options['mtime'])."GMT");
                }

                if (isset($options['stream'])) {
                    // GET handler returned a stream
                    if (!empty($options['ranges']) && (0===fseek($options['stream'], 0, SEEK_SET))) {
                        // partial request and stream is seekable

                        if (count($options['ranges']) === 1) {
                            $range = $options['ranges'][0];

                            if (isset($range['start'])) {
                                fseek($options['stream'], $range['start'], SEEK_SET);
                                if (feof($options['stream'])) {
                                    $this->http_status("416 Requested range not satisfiable");
                                    return;
                                }

                                if (isset($range['end'])) {
                                    $size = $range['end']-$range['start']+1;
                                    $this->http_status("206 partial");
                                    header("Content-length: $size");
                                    header("Content-range: $range[start]-$range[end]/"
                                           . (isset($options['size']) ? $options['size'] : "*"));
                                    while ($size && !feof($options['stream'])) {
                                        $buffer = fread($options['stream'], 4096);
                                        $size  -= $this->bytes($buffer);
                                        echo $buffer;
                                    }
                                } else {
                                    $this->http_status("206 partial");
                                    if (isset($options['size'])) {
                                        header("Content-length: ".($options['size'] - $range['start']));
                                        header("Content-range: ".$range['start']."-".$range['end']."/"
                                               . (isset($options['size']) ? $options['size'] : "*"));
                                    }
                                    fpassthru($options['stream']);
                                }
                            } else {
                                header("Content-length: ".$range['last']);
                                fseek($options['stream'], -$range['last'], SEEK_END);
                                fpassthru($options['stream']);
                            }
                        } else {
                            $this->_multipart_byterange_header(); // init multipart
                            foreach ($options['ranges'] as $range) {
                                // TODO what if size unknown? 500?
                                if (isset($range['start'])) {
                                    $from = $range['start'];
                                    $to   = !empty($range['end']) ? $range['end'] : $options['size']-1;
                                } else {
                                    $from = $options['size'] - $range['last']-1;
                                    $to   = $options['size'] -1;
                                }
                                $total = isset($options['size']) ? $options['size'] : "*";
                                $size  = $to - $from + 1;
                                $this->_multipart_byterange_header($options['mimetype'], $from, $to, $total);


                                fseek($options['stream'], $from, SEEK_SET);
                                while ($size && !feof($options['stream'])) {
                                    $buffer = fread($options['stream'], 4096);
                                    $size  -= $this->bytes($buffer);
                                    echo $buffer;
                                }
                            }
                            $this->_multipart_byterange_header(); // end multipart
                        }
                    } else {
                        // normal request or stream isn't seekable, return full content
                        if (isset($options['size'])) {
                            header("Content-length: ".$options['size']);
                        }
                        fpassthru($options['stream']);
                        return; // no more headers
                    }
                } elseif (isset($options['data'])) {
                    if (is_array($options['data'])) {
                        // reply to partial request
                    } else {
                        header("Content-length: ".$this->bytes($options['data']));
                        echo $options['data'];
                    }
                }
            }
        }

        if (!headers_sent()) {
            if (false === $status) {
                $this->http_status("404 not found");
            } else {
                // TODO: check setting of headers in various code paths above
                $this->http_status("$status");
            }
        }
    }


    /**
     * parse HTTP Range: header
     *
     * @param  array options array to store result in
     * @return void
     */
    function _get_ranges(&$options)
    {
        // process Range: header if present
        if (isset($this->_SERVER['HTTP_RANGE'])) {

            // we only support standard "bytes" range specifications for now
            if (preg_match('/bytes\s*=\s*(.+)/', $this->_SERVER['HTTP_RANGE'], $matches)) {
                $options["ranges"] = array();

                // ranges are comma separated
                foreach (explode(",", $matches[1]) as $range) {
                    // ranges are either from-to pairs or just end positions
                    list($start, $end) = explode("-", $range);
                    $options["ranges"][] = ($start==="")
                        ? array("last"=>$end)
                        : array("start"=>$start, "end"=>$end);
                }
            }
        }
    }

    /**
     * generate separator headers for multipart response
     *
     * first and last call happen without parameters to generate
     * the initial header and closing sequence, all calls inbetween
     * require content mimetype, start and end byte position and
     * optionaly the total byte length of the requested resource
     *
     * @param  string  mimetype
     * @param  int     start byte position
     * @param  int     end   byte position
     * @param  int     total resource byte size
     */
    function _multipart_byterange_header($mimetype = false, $from = false, $to=false, $total=false)
    {
        if ($mimetype === false) {
            if (!isset($this->multipart_separator)) {
                // initial

                // a little naive, this sequence *might* be part of the content
                // but it's really not likely and rather expensive to check
                $this->multipart_separator = "SEPARATOR_" . uniqid(mt_rand());

                // generate HTTP header
                header("Content-type: multipart/byteranges; boundary=".$this->multipart_separator);
            } else {
                // final

                // generate closing multipart sequence
                echo "\n--{$this->multipart_separator}--";
            }
        } else {
            // generate separator and header for next part
            echo "\n--{$this->multipart_separator}\n";
            echo "Content-type: $mimetype\n";
            echo "Content-range: $from-$to/". ($total === false ? "*" : $total);
            echo "\n\n";
        }
    }



    // }}}

    // {{{ http_HEAD()

    /**
     * HEAD method handler
     *
     * @param  void
     * @return void
     */
    function http_HEAD()
    {
        $status          = false;
        $options         = Array();
        $options["path"] = $this->path;

        if (method_exists($this, "HEAD")) {
            $status = $this->head($options);
        } else if (method_exists($this, "GET")) {
            ob_start();
            $status = $this->GET($options);
            if (!isset($options['size'])) {
                $options['size'] = ob_get_length();
            }
            ob_end_clean();
        }

        if (!isset($options['mimetype'])) {
            $options['mimetype'] = "application/octet-stream";
        }
        header("Content-type: $options[mimetype]");

        if (isset($options['mtime'])) {
            header("Last-modified:".gmdate("D, d M Y H:i:s ", $options['mtime'])."GMT");
        }

        if (isset($options['size'])) {
            header("Content-length: ".$options['size']);
        }

        if ($status === true)  $status = "200 OK";
        if ($status === false) $status = "404 Not found";

        $this->http_status($status);
    }

    // }}}

    // {{{ http_PUT()

    /**
     * PUT method handler
     *
     * @param  void
     * @return void
     */
    function http_PUT()
    {
        if ($this->_check_lock_status($this->path)) {
            $options                   = Array();
            $options["path"]           = $this->path;
            $options["content_length"] = $this->_SERVER["CONTENT_LENGTH"];

            // get the Content-type
            if (isset($this->_SERVER["CONTENT_TYPE"])) {
                // for now we do not support any sort of multipart requests
                if (!strncmp($this->_SERVER["CONTENT_TYPE"], "multipart/", 10)) {
                    $this->http_status("501 not implemented");
                    echo "The service does not support mulipart PUT requests";
                    return;
                }
                $options["content_type"] = $this->_SERVER["CONTENT_TYPE"];
            } else {
                // default content type if none given
                $options["content_type"] = "application/octet-stream";
            }

            /* RFC 2616 2.6 says: "The recipient of the entity MUST NOT
             ignore any Content-* (e.g. Content-Range) headers that it
             does not understand or implement and MUST return a 501
             (Not Implemented) response in such cases."
            */
            foreach ($this->_SERVER as $key => $val) {
                if (strncmp($key, "HTTP_CONTENT", 11)) continue;
                switch ($key) {
                case 'HTTP_CONTENT_ENCODING': // RFC 2616 14.11
                    // TODO support this if ext/zlib filters are available
                    $this->http_status("501 not implemented");
                    echo "The service does not support '$val' content encoding";
                    return;

                case 'HTTP_CONTENT_LANGUAGE': // RFC 2616 14.12
                    // we assume it is not critical if this one is ignored
                    // in the actual PUT implementation ...
                    $options["content_language"] = $val;
                    break;

                case 'HTTP_CONTENT_LENGTH':
                    // defined on IIS and has the same value as CONTENT_LENGTH
                    break;

                case 'HTTP_CONTENT_LOCATION': // RFC 2616 14.14
                    /* The meaning of the Content-Location header in PUT
                     or POST requests is undefined; servers are free
                     to ignore it in those cases. */
                    break;

                case 'HTTP_CONTENT_RANGE':    // RFC 2616 14.16
                    // single byte range requests are supported
                    // the header format is also specified in RFC 2616 14.16
                    // TODO we have to ensure that implementations support this or send 501 instead
                    if (!preg_match('@bytes\s+(\d+)-(\d+)/((\d+)|\*)@', $val, $matches)) {
                        $this->http_status("400 bad request");
                        echo "The service does only support single byte ranges";
                        return;
                    }

                    $range = array("start"=>$matches[1], "end"=>$matches[2]);
                    if (is_numeric($matches[3])) {
                        $range["total_length"] = $matches[3];
                    }
                    $option["ranges"][] = $range;

                    // TODO make sure the implementation supports partial PUT
                    // this has to be done in advance to avoid data being overwritten
                    // on implementations that do not support this ...
                    break;

                case 'HTTP_CONTENT_TYPE':
                    // defined on IIS and has the same value as CONTENT_TYPE
                    break;

                case 'HTTP_CONTENT_MD5':      // RFC 2616 14.15
                    // TODO: maybe we can just pretend here?
                    $this->http_status("501 not implemented");
                    echo "The service does not support content MD5 checksum verification";
                    return;

                default:
                    // any other unknown Content-* headers
                    $this->http_status("501 not implemented");
                    echo "The service does not support '$key'";
                    return;
                }
            }

            $options["stream"] = fopen("php://input", "r");

            $stat = $this->PUT($options);

            if ($stat === false) {
                $stat = "403 Forbidden";
            } else if (is_resource($stat) && get_resource_type($stat) == "stream") {
                $stream = $stat;

                $stat = $options["new"] ? "201 Created" : "204 No Content";

                if (!empty($options["ranges"])) {
                    // TODO multipart support is missing (see also above)
                    if (0 == fseek($stream, $range[0]["start"], SEEK_SET)) {
                        $length = $range[0]["end"]-$range[0]["start"]+1;
                        if (!fwrite($stream, fread($options["stream"], $length))) {
                            $stat = "403 Forbidden";
                        }
                    } else {
                        $stat = "403 Forbidden";
                    }
                } else {
                    while (!feof($options["stream"])) {
                        if (false === fwrite($stream, fread($options["stream"], 4096))) {
                            $stat = "403 Forbidden";
                            break;
                        }
                    }
                }

                fclose($stream);
            }

            $this->http_status($stat);
        } else {
            $this->http_status("423 Locked");
        }
    }

    // }}}


    // {{{ http_DELETE()

    /**
     * DELETE method handler
     *
     * @param  void
     * @return void
     */
    function http_DELETE()
    {
        // check RFC 2518 Section 9.2, last paragraph
        if (isset($this->_SERVER["HTTP_DEPTH"])) {
            if ($this->_SERVER["HTTP_DEPTH"] != "infinity") {
                $this->http_status("400 Bad Request");
                return;
            }
        }

        // check lock status
        if ($this->_check_lock_status($this->path)) {
            // ok, proceed
            $options         = Array();
            $options["path"] = $this->path;

            $stat = $this->DELETE($options);

            $this->http_status($stat);
        } else {
            // sorry, its locked
            $this->http_status("423 Locked");
        }
    }

    // }}}

    // {{{ http_COPY()

    /**
     * COPY method handler
     *
     * @param  void
     * @return void
     */
    function http_COPY()
    {
        // no need to check source lock status here
        // destination lock status is always checked by the helper method
        $this->_copymove("copy");
    }

    // }}}

    // {{{ http_MOVE()

    /**
     * MOVE method handler
     *
     * @param  void
     * @return void
     */
    function http_MOVE()
    {
        if ($this->_check_lock_status($this->path)) {
            // destination lock status is always checked by the helper method
            $this->_copymove("move");
        } else {
            $this->http_status("423 Locked");
        }
    }

    // }}}


    // {{{ http_LOCK()

    /**
     * LOCK method handler
     *
     * @param  void
     * @return void
     */
    function http_LOCK()
    {
        $options         = Array();
        $options["path"] = $this->path;

        if (isset($this->_SERVER['HTTP_DEPTH'])) {
            $options["depth"] = $this->_SERVER["HTTP_DEPTH"];
        } else {
            $options["depth"] = "infinity";
        }

        if (isset($this->_SERVER["HTTP_TIMEOUT"])) {
            $options["timeout"] = explode(",", $this->_SERVER["HTTP_TIMEOUT"]);
        }

        if (empty($this->_SERVER['CONTENT_LENGTH']) && !empty($this->_SERVER['HTTP_IF'])) {
            // check if locking is possible
            if (!$this->_check_lock_status($this->path)) {
                $this->http_status("423 Locked");
                return;
            }

            // refresh lock
            $options["locktoken"] = substr($this->_SERVER['HTTP_IF'], 2, -2);
            $options["update"]    = $options["locktoken"];

            // setting defaults for required fields, LOCK() SHOULD overwrite these
            $options['owner']     = "unknown";
            $options['scope']     = "exclusive";
            $options['type']      = "write";


            $stat = $this->LOCK($options);
        } else {
            // extract lock request information from request XML payload
            $lockinfo = $this->_parse_lockinfo("php://input");
            if (!$lockinfo->parseSuccess) {
                $this->http_status("400 bad request");
            }

            // check if locking is possible
            if (!$this->_check_lock_status($this->path, $this->lockscope === "shared")) {
                $this->http_status("423 Locked");
                return;
            }

            // new lock
            $options["scope"]     = $this->lockscope;
            $options["type"]      = $this->locktype;
            $options["owner"]     = $this->owner;
            $options["locktoken"] = $this->_new_locktoken();

            $stat = $this->LOCK($options);
        }

        if (is_bool($stat)) {
            $http_stat = $stat ? "200 OK" : "423 Locked";
        } else {
            $http_stat = (string)$stat;
        }
        $this->http_status($http_stat);

        if ($http_stat{0} == 2) { // 2xx states are ok
            if ($options["timeout"]) {
                // if multiple timeout values were given we take the first only
                if (is_array($options["timeout"])) {
                    reset($options["timeout"]);
                    $options["timeout"] = current($options["timeout"]);
                }
                // if the timeout is numeric only we need to reformat it
                if (is_numeric($options["timeout"])) {
                    // more than a million is considered an absolute timestamp
                    // less is more likely a relative value
                    if ($options["timeout"]>1000000) {
                        $timeout = "Second-".($options['timeout']-time());
                    } else {
                        $timeout = "Second-$options[timeout]";
                    }
                } else {
                    // non-numeric values are passed on verbatim,
                    // no error checking is performed here in this case
                    // TODO: send "Infinite" on invalid timeout strings?
                    $timeout = $options["timeout"];
                }
            } else {
                $timeout = "Infinite";
            }

            header('Content-Type: text/xml; charset="utf-8"');
            header("Lock-Token: <$options[locktoken]>");
            echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
            echo "<D:prop xmlns:D=\"DAV:\">\n";
            echo " <D:lockdiscovery>\n";
            echo "  <D:activelock>\n";
            echo "   <D:lockscope><D:$options[scope]/></D:lockscope>\n";
            echo "   <D:locktype><D:$options[type]/></D:locktype>\n";
            echo "   <D:depth>$options[depth]</D:depth>\n";
            echo "   <D:owner>$options[owner]</D:owner>\n";
            echo "   <D:timeout>$timeout</D:timeout>\n";
            echo "   <D:locktoken><D:href>$options[locktoken]</D:href></D:locktoken>\n";
            echo "  </D:activelock>\n";
            echo " </D:lockdiscovery>\n";
            echo "</D:prop>\n\n";
        }
    }


    // }}}

    // {{{ http_UNLOCK()

    /**
     * UNLOCK method handler
     *
     * @param  void
     * @return void
     */
    function http_UNLOCK()
    {
        $options         = Array();
        $options["path"] = $this->path;

        if (isset($this->_SERVER['HTTP_DEPTH'])) {
            $options["depth"] = $this->_SERVER["HTTP_DEPTH"];
        } else {
            $options["depth"] = "infinity";
        }

        // strip surrounding <>
        $options["token"] = substr(trim($this->_SERVER["HTTP_LOCK_TOKEN"]), 1, -1);

        // call user method
        $stat = $this->UNLOCK($options);

        $this->http_status($stat);
    }

    // }}}

    // }}}

    // {{{ _copymove()

    function _copymove($what)
    {
        $options         = Array();
        $options["path"] = $this->path;

        if (isset($this->_SERVER["HTTP_DEPTH"])) {
            $options["depth"] = $this->_SERVER["HTTP_DEPTH"];
        } else {
            $options["depth"] = "infinity";
        }

        $http_header_host = preg_replace("/:80$/", "", $this->_SERVER["HTTP_HOST"]);

        $url  = parse_url($this->_SERVER["HTTP_DESTINATION"]);
        $path = urldecode($url["path"]);

        if (isset($url["host"])) {
            // TODO check url scheme, too
            $http_host = $url["host"];
            if (isset($url["port"]) && $url["port"] != 80)
                $http_host.= ":".$url["port"];
        } else {
            // only path given, set host to self
            $http_host == $http_header_host;
        }

        if ($http_host == $http_header_host &&
            !strncmp($this->_SERVER["SCRIPT_NAME"], $path,
                     strlen($this->_SERVER["SCRIPT_NAME"]))) {
            $options["dest"] = substr($path, strlen($this->_SERVER["SCRIPT_NAME"]));
            if (!$this->_check_lock_status($options["dest"])) {
                $this->http_status("423 Locked");
                return;
            }

        } else {
            $options["dest_url"] = $this->_SERVER["HTTP_DESTINATION"];
        }

        // see RFC 2518 Sections 9.6, 8.8.4 and 8.9.3
        if (isset($this->_SERVER["HTTP_OVERWRITE"])) {
            $options["overwrite"] = $this->_SERVER["HTTP_OVERWRITE"] == "T";
        } else {
            $options["overwrite"] = true;
        }

        $stat = $this->$what($options);
        $this->http_status($stat);
    }

    // }}}

    // {{{ _allow()

    /**
     * check for implemented HTTP methods
     *
     * @param  void
     * @return array something
     */
    function _allow()
    {
        // OPTIONS is always there
        $allow = array("OPTIONS" =>"OPTIONS");

        // all other METHODS need both a http_method() wrapper
        // and a method() implementation
        // the base class supplies wrappers only
        foreach (get_class_methods($this) as $method) {
            if (!strncmp("http_", $method, 5)) {
                $method = strtoupper(substr($method, 5));
                if (method_exists($this, $method)) {
                    $allow[$method] = $method;
                }
            }
        }

        // we can emulate a missing HEAD implemetation using GET
        if (isset($allow["GET"]))
            $allow["HEAD"] = "HEAD";

        // no LOCK without checklok()
        if (!method_exists($this, "checklock")) {
            unset($allow["LOCK"]);
            unset($allow["UNLOCK"]);
        }

        return $allow;
    }

    // }}}

    /**
     * helper for property element creation
     *
     * @param  string  XML namespace (optional)
     * @param  string  property name
     * @param  string  property value
     * @return array   property array
     */
    function mkprop()
    {
        $args = func_get_args();
        if (count($args) == 3) {
            return array("ns"   => $args[0],
                         "name" => $args[1],
                         "val"  => $args[2]);
        } else {
            return array("ns"   => "DAV:",
                         "name" => $args[0],
                         "val"  => $args[1]);
        }
    }

    // {{{ _check_auth

    /**
     * check authentication if check is implemented
     *
     * @param  void
     * @return bool  true if authentication succeded or not necessary
     */
    function _check_auth()
    {
        $auth_type = isset($this->_SERVER["AUTH_TYPE"])
            ? $this->_SERVER["AUTH_TYPE"]
            : null;

        $auth_user = isset($this->_SERVER["PHP_AUTH_USER"])
            ? $this->_SERVER["PHP_AUTH_USER"]
            : null;

        $auth_pw   = isset($this->_SERVER["PHP_AUTH_PW"])
            ? $this->_SERVER["PHP_AUTH_PW"]
            : null;

        if (method_exists($this, "checkAuth")) {
            // PEAR style method name
            return $this->checkAuth($auth_type, $auth_user, $auth_pw);
        } else if (method_exists($this, "check_auth")) {
            // old (pre 1.0) method name
            return $this->check_auth($auth_type, $auth_user, $auth_pw);
        } else {
            // no method found -> no authentication required
            return true;
        }
    }

    // }}}

    // {{{ UUID stuff

    /**
     * create a new opaque lock token as defined in RFC2518
     *
     * @param  void
     * @return string  new RFC2518 opaque lock token
     */
    function _new_locktoken()
    {
        return "opaquelocktoken:" . ((string)new Horde_Support_Uuid());
    }

    // }}}

    // {{{ WebDAV If: header parsing

    /**
     *
     *
     * @param  string  header string to parse
     * @param  int     current parsing position
     * @return array   next token (type and value)
     */
    function _if_header_lexer($string, &$pos)
    {
        // skip whitespace
        while (ctype_space($string{$pos})) {
            ++$pos;
        }

        // already at end of string?
        if (strlen($string) <= $pos) {
            return false;
        }

        // get next character
        $c = $string{$pos++};

        // now it depends on what we found
        switch ($c) {
        case "<":
            // URIs are enclosed in <...>
            $pos2 = strpos($string, ">", $pos);
            $uri  = substr($string, $pos, $pos2 - $pos);
            $pos  = $pos2 + 1;
            return array("URI", $uri);

        case "[":
            //Etags are enclosed in [...]
            if ($string{$pos} == "W") {
                $type = "ETAG_WEAK";
                $pos += 2;
            } else {
                $type = "ETAG_STRONG";
            }
            $pos2 = strpos($string, "]", $pos);
            $etag = substr($string, $pos + 1, $pos2 - $pos - 2);
            $pos  = $pos2 + 1;
            return array($type, $etag);

        case "N":
            // "N" indicates negation
            $pos += 2;
            return array("NOT", "Not");

        default:
            // anything else is passed verbatim char by char
            return array("CHAR", $c);
        }
    }

    /**
     * parse If: header
     *
     * @param  string  header string
     * @return array   URIs and their conditions
     */
    function _if_header_parser($str)
    {
        $pos  = 0;
        $len  = strlen($str);
        $uris = array();

        // parser loop
        while ($pos < $len) {
            // get next token
            $token = $this->_if_header_lexer($str, $pos);

            // check for URI
            if ($token[0] == "URI") {
                $uri   = $token[1]; // remember URI
                $token = $this->_if_header_lexer($str, $pos); // get next token
            } else {
                $uri = "";
            }

            // sanity check
            if ($token[0] != "CHAR" || $token[1] != "(") {
                return false;
            }

            $list  = array();
            $level = 1;
            $not   = "";
            while ($level) {
                $token = $this->_if_header_lexer($str, $pos);
                if ($token[0] == "NOT") {
                    $not = "!";
                    continue;
                }
                switch ($token[0]) {
                case "CHAR":
                    switch ($token[1]) {
                    case "(":
                        $level++;
                        break;
                    case ")":
                        $level--;
                        break;
                    default:
                        return false;
                    }
                    break;

                case "URI":
                    $list[] = $not."<$token[1]>";
                    break;

                case "ETAG_WEAK":
                    $list[] = $not."[W/'$token[1]']>";
                    break;

                case "ETAG_STRONG":
                    $list[] = $not."['$token[1]']>";
                    break;

                default:
                    return false;
                }
                $not = "";
            }

            if (isset($uris[$uri]) && is_array($uris[$uri])) {
                $uris[$uri] = array_merge($uris[$uri], $list);
            } else {
                $uris[$uri] = $list;
            }
        }

        return $uris;
    }

    /**
     * check if conditions from "If:" headers are meat
     *
     * the "If:" header is an extension to HTTP/1.1
     * defined in RFC 2518 section 9.4
     *
     * @param  void
     * @return void
     */
    function _check_if_header_conditions()
    {
        if (isset($this->_SERVER["HTTP_IF"])) {
            $this->_if_header_uris =
                $this->_if_header_parser($this->_SERVER["HTTP_IF"]);

            foreach ($this->_if_header_uris as $uri => $conditions) {
                if ($uri == "") {
                    $uri = $this->uri;
                }
                // all must match
                $state = true;
                foreach ($conditions as $condition) {
                    // lock tokens may be free form (RFC2518 6.3)
                    // but if opaquelocktokens are used (RFC2518 6.4)
                    // we have to check the format (litmus tests this)
                    if (!strncmp($condition, "<opaquelocktoken:", strlen("<opaquelocktoken"))) {
                        if (!preg_match('/^<opaquelocktoken:[[:xdigit:]]{8}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{12}>$/', $condition)) {
                            $this->http_status("423 Locked");
                            return false;
                        }
                    }
                    if (!$this->_check_uri_condition($uri, $condition)) {
                        $this->http_status("412 Precondition failed");
                        $state = false;
                        break;
                    }
                }

                // any match is ok
                if ($state == true) {
                    return true;
                }
            }
            return false;
        }
        return true;
    }

    /**
     * Check a single URI condition parsed from an if-header
     *
     * Check a single URI condition parsed from an if-header
     *
     * @abstract
     * @param string $uri URI to check
     * @param string $condition Condition to check for this URI
     * @returns bool Condition check result
     */
    function _check_uri_condition($uri, $condition)
    {
        // not really implemented here,
        // implementations must override

        // a lock token can never be from the DAV: scheme
        // litmus uses DAV:no-lock in some tests
        if (!strncmp("<DAV:", $condition, 5)) {
            return false;
        }

        return true;
    }


    /**
     *
     *
     * @param  string  path of resource to check
     * @param  bool    exclusive lock?
     */
    function _check_lock_status($path, $exclusive_only = false)
    {
        // FIXME depth -> ignored for now
        if (method_exists($this, "checkLock")) {
            // is locked?
            $lock = $this->checkLock($path);

            // ... and lock is not owned?
            if (is_array($lock) && count($lock)) {
                // FIXME doesn't check uri restrictions yet
                if (!isset($this->_SERVER["HTTP_IF"]) || !strstr($this->_SERVER["HTTP_IF"], $lock["token"])) {
                    if (!$exclusive_only || ($lock["scope"] !== "shared"))
                        return false;
                }
            }
        }
        return true;
    }


    // }}}


    /**
     * Generate lockdiscovery reply from checklock() result
     *
     * @param   string  resource path to check
     * @return  string  lockdiscovery response
     */
    function lockdiscovery($path)
    {
        // no lock support without checklock() method
        if (!method_exists($this, "checklock")) {
            return "";
        }

        // collect response here
        $activelocks = "";

        // get checklock() reply
        $lock = $this->checklock($path);

        // generate <activelock> block for returned data
        if (is_array($lock) && count($lock)) {
            // check for 'timeout' or 'expires'
            if (!empty($lock["expires"])) {
                $timeout = "Second-".($lock["expires"] - time());
            } else if (!empty($lock["timeout"])) {
                $timeout = "Second-$lock[timeout]";
            } else {
                $timeout = "Infinite";
            }

            // genreate response block
            $activelocks.= "
              <D:activelock>
               <D:lockscope><D:$lock[scope]/></D:lockscope>
               <D:locktype><D:$lock[type]/></D:locktype>
               <D:depth>$lock[depth]</D:depth>
               <D:owner>$lock[owner]</D:owner>
               <D:timeout>$timeout</D:timeout>
               <D:locktoken><D:href>$lock[token]</D:href></D:locktoken>
              </D:activelock>
             ";
        }

        // return generated response
        return $activelocks;
    }

    /**
     * set HTTP return status and mirror it in a private header
     *
     * @param  string  status code and message
     * @return void
     */
    function http_status($status)
    {
        // simplified success case
        if ($status === true) {
            $status = "200 OK";
        }

        // remember status
        $this->_http_status = $status;

        // generate HTTP status response
        header("HTTP/1.1 $status");
        header("X-WebDAV-Status: $status", true);
    }

    /**
     * private minimalistic version of PHP urlencode()
     *
     * only blanks, percent and XML special chars must be encoded here
     * full urlencode() encoding confuses some clients ...
     *
     * @param  string  URL to encode
     * @return string  encoded URL
     */
    function _urlencode($url)
    {
        return strtr($url, array(" "=>"%20",
                                 "%"=>"%25",
                                 "&"=>"%26",
                                 "<"=>"%3C",
                                 ">"=>"%3E",
                                 ));
    }

    /**
     * private version of PHP urldecode
     *
     * not really needed but added for completenes
     *
     * @param  string  URL to decode
     * @return string  decoded URL
     */
    function _urldecode($path)
    {
        return rawurldecode($path);
    }

    /**
     * Slashify - make sure path ends in a slash
     *
     * @param   string directory path
     * @returns string directory path wiht trailing slash
     */
    function _slashify($path)
    {
        if ($path[strlen($path)-1] != '/') {
            $path = $path."/";
        }
        return $path;
    }

    /**
     * Unslashify - make sure path doesn't in a slash
     *
     * @param   string directory path
     * @returns string directory path wihtout trailing slash
     */
    function _unslashify($path)
    {
        if ($path[strlen($path)-1] == '/') {
            $path = substr($path, 0, strlen($path) -1);
        }
        return $path;
    }

    /**
     * Merge two paths, make sure there is exactly one slash between them
     *
     * @param  string  parent path
     * @param  string  child path
     * @return string  merged path
     */
    function _mergePaths($parent, $child)
    {
        if ($child{0} == '/') {
            return $this->_unslashify($parent).$child;
        } else {
            return $this->_slashify($parent).$child;
        }
    }

    function _prop2xml($prop)
    {
        $res = array();

        // properties from namespaces != "DAV:" or without any namespace
        if ($prop["ns"]) {
            $key = $this->ns_hash[$prop['ns']] . ':' . $prop['name'];
            #$res .= "<" . $this->ns_hash[$prop["ns"]] . ":$prop[name]>";
        } else {
            $key = $prop['name'] . '#xmlns=""';
            #$res .= "<$prop[name] xmlns=\"\">";
        }

        // Check for and handle nested properties
        if (is_array($prop['val'] && isset($prop['val']['name']))) {
            // This is a single nested property
            $res[$key] = $this->_prop2xml($prop['val']);
        } elseif (is_array($prop['val'])) {
            // This nested property has multiple values
            foreach ($prop['val'] as $entry) {
                $res[$key] = $this->_prop2xml($entry);
            }
        } else {
            // This is a simple property value
            $res[$key] = $prop['val'];
        }

        return $res;
    }

    /**
     * mbstring.func_overload save strlen version: counting the bytes not the chars
     *
     * @param string $str
     * @return int
     */
    function bytes($str)
    {
    	static $func_overload;

    	if (is_null($func_overload))
    	{
    		$func_overload = @extension_loaded('mbstring') ? ini_get('mbstring.func_overload') : 0;
    	}
    	return $func_overload & 2 ? mb_strlen($str,'ascii') : strlen($str);
    }


    function _parse_propfind($path)
    {
        // success state flag
        $this->parseSuccess = true;

        // property storage array
        $this->parseProps = array();

        // internal tag depth counter
        $this->parseDepth = 0;

        // remember if any input was parsed
        $had_input = false;

        // open input stream
        $f_in = fopen($path, "r");
        if (!$f_in) {
            $this->parseSuccess = false;
            return;
        }

        // create XML parser
        $xml_parser = xml_parser_create_ns("UTF-8", " ");

        // set tag and data handlers
        xml_set_element_handler($xml_parser,
                                array(&$this, "_startPropinfoElement"),
                                array(&$this, "_endPropinfoElement"));

        // we want a case sensitive parser
        xml_parser_set_option($xml_parser,
                              XML_OPTION_CASE_FOLDING, false);


        // parse input
        while ($this->parseSuccess && !feof($f_in)) {
            $line = fgets($f_in);
            if (is_string($line)) {
                $had_input = true;
                $this->parseSuccess &= xml_parse($xml_parser, $line, false);
            }
        }

        // finish parsing
        if ($had_input) {
            $this->parseSuccess &= xml_parse($xml_parser, "", true);
        }

        // free parser
        xml_parser_free($xml_parser);

        // close input stream
        fclose($f_in);

        // if no input was parsed it was a request
        if(!count($this->parseProps)) $this->parseProps = "all"; // default
    }


    /**
     * start tag handler
     *
     * @access private
     * @param  resource  parser
     * @param  string    tag name
     * @param  array     tag attributes
     */
    function _startPropinfoElement($parser, $name, $attrs)
    {
        // name space handling
        if (strstr($name, " ")) {
            list($ns, $tag) = explode(" ", $name);
            if ($ns == "")
                $this->parseSuccess = false;
        } else {
            $ns  = "";
            $tag = $name;
        }

        // special tags at level 1: <allprop> and <propname>
        if ($this->parseDepth == 1) {
            if ($tag == "allprop")
                $this->parseProps = "all";

            if ($tag == "propname")
                $this->parseProps = "names";
        }

        // requested properties are found at level 2
        if ($this->parseDepth == 2) {
            $prop = array("name" => $tag);
            if ($ns)
                $prop["xmlns"] = $ns;
            $this->parseProps[] = $prop;
        }

        // increment depth count
        $this->parseDepth++;
    }


    /**
     * end tag handler
     *
     * @access private
     * @param  resource  parser
     * @param  string    tag name
     */
    function _endPropinfoElement($parser, $name)
    {
        // here we only need to decrement the depth count
        $this->parseDepth--;
    }

    function _parse_lockinfo($path)
    {
        // we assume success unless problems occur
        $this->parseSuccess = true;

        // remember if any input was parsed
        $had_input = false;

        // open stream
        $f_in = fopen($path, "r");
        if (!$f_in) {
            $this->parseSuccess = false;
            return;
        }

        // create namespace aware parser
        $xml_parser = xml_parser_create_ns("UTF-8", " ");

        // set tag and data handlers
        xml_set_element_handler($xml_parser,
                                array(&$this, "_startLockElement"),
                                array(&$this, "_endLockElement"));
        xml_set_character_data_handler($xml_parser,
                                       array(&$this, "_lockData"));

        // we want a case sensitive parser
        xml_parser_set_option($xml_parser,
                              XML_OPTION_CASE_FOLDING, false);

        // parse input
        while ($this->parseSuccess && !feof($f_in)) {
            $line = fgets($f_in);
            if (is_string($line)) {
                $had_input = true;
                $this->parseSuccess &= xml_parse($xml_parser, $line, false);
            }
        }

        // finish parsing
        if ($had_input) {
            $this->parseSuccess &= xml_parse($xml_parser, "", true);
        }

        // check if required tags where found
        $this->parseSuccess &= !empty($this->locktype);
        $this->parseSuccess &= !empty($this->lockscope);

        // free parser resource
        xml_parser_free($xml_parser);

        // close input stream
        fclose($f_in);
    }


    /**
     * tag start handler
     *
     * @param  resource  parser
     * @param  string    tag name
     * @param  array     tag attributes
     * @return void
     * @access private
     */
    function _startLockElement($parser, $name, $attrs)
    {
        // namespace handling
        if (strstr($name, " ")) {
            list($ns, $tag) = explode(" ", $name);
        } else {
            $ns  = "";
            $tag = $name;
        }


        if ($this->collect_owner) {
            // everything within the <owner> tag needs to be collected
            $ns_short = "";
            $ns_attr  = "";
            if ($ns) {
                if ($ns == "DAV:") {
                    $ns_short = "D:";
                } else {
                    $ns_attr = " xmlns='$ns'";
                }
            }
            $this->owner .= "<$ns_short$tag$ns_attr>";
        } else if ($ns == "DAV:") {
            // parse only the essential tags
            switch ($tag) {
            case "write":
                $this->locktype = $tag;
                break;
            case "exclusive":
            case "shared":
                $this->lockscope = $tag;
                break;
            case "owner":
                $this->collect_owner = true;
                break;
            }
        }
    }

    /**
     * data handler
     *
     * @param  resource  parser
     * @param  string    data
     * @return void
     * @access private
     */
    function _lockData($parser, $data)
    {
        // only the <owner> tag has data content
        if ($this->collect_owner) {
            $this->owner .= $data;
        }
    }

    /**
     * tag end handler
     *
     * @param  resource  parser
     * @param  string    tag name
     * @return void
     * @access private
     */
    function _endLockElement($parser, $name)
    {
        // namespace handling
        if (strstr($name, " ")) {
            list($ns, $tag) = explode(" ", $name);
        } else {
            $ns  = "";
            $tag = $name;
        }

        // <owner> finished?
        if (($ns == "DAV:") && ($tag == "owner")) {
            $this->collect_owner = false;
        }

        // within <owner> we have to collect everything
        if ($this->collect_owner) {
            $ns_short = "";
            $ns_attr  = "";
            if ($ns) {
                if ($ns == "DAV:") {
                    $ns_short = "D:";
                } else {
                    $ns_attr = " xmlns='$ns'";
                }
            }
            $this->owner .= "</$ns_short$tag$ns_attr>";
        }
    }

    function _parse_proppatch($path)
    {
        $this->parseSuccess = true;

        $this->parseDepth = 0;
        $this->parseProps = array();
        $had_input = false;

        $f_in = fopen($path, "r");
        if (!$f_in) {
            $this->parseSuccess = false;
            return;
        }

        $xml_parser = xml_parser_create_ns("UTF-8", " ");

        xml_set_element_handler($xml_parser,
                                array(&$this, "_startProppatchElement"),
                                array(&$this, "_endProppatchElement"));

        xml_set_character_data_handler($xml_parser,
                                       array(&$this, "_proppatchData"));

        xml_parser_set_option($xml_parser,
                              XML_OPTION_CASE_FOLDING, false);

        while($this->parseSuccess && !feof($f_in)) {
            $line = fgets($f_in);
            if (is_string($line)) {
                $had_input = true;
                $this->parseSuccess &= xml_parse($xml_parser, $line, false);
            }
        }

        if($had_input) {
            $this->parseSuccess &= xml_parse($xml_parser, "", true);
        }

        xml_parser_free($xml_parser);

        fclose($f_in);
    }

    /**
     * tag start handler
     *
     * @param  resource  parser
     * @param  string    tag name
     * @param  array     tag attributes
     * @return void
     * @access private
     */
    function _startProppatchElement($parser, $name, $attrs)
    {
        if (strstr($name, " ")) {
            list($ns, $tag) = explode(" ", $name);
            if ($ns == "")
                $this->parseSuccess = false;
        } else {
            $ns = "";
            $tag = $name;
        }

        if ($this->parseDepth == 1) {
            $this->mode = $tag;
        }

        if ($this->parseDepth == 3) {
            $prop = array("name" => $tag);
            $this->current = array("name" => $tag, "ns" => $ns, "status"=> 200);
            if ($this->mode == "set") {
                $this->current["val"] = "";     // default set val
            }
        }

        if ($this->parseDepth >= 4) {
            $this->current["val"] .= "<$tag";
            if (isset($attr)) {
                foreach ($attr as $key => $val) {
                    $this->current["val"] .= ' '.$key.'="'.str_replace('"','&quot;', $val).'"';
                }
            }
            $this->current["val"] .= ">";
        }



        $this->parseDepth++;
    }

    /**
     * tag end handler
     *
     * @param  resource  parser
     * @param  string    tag name
     * @return void
     * @access private
     */
    function _endProppatchElement($parser, $name)
    {
        if (strstr($name, " ")) {
            list($ns, $tag) = explode(" ", $name);
            if ($ns == "")
                $this->parseSuccess = false;
        } else {
            $ns = "";
            $tag = $name;
        }

        $this->parseDepth--;

        if ($this->parseDepth >= 4) {
            $this->current["val"] .= "</$tag>";
        }

        if ($this->parseDepth == 3) {
            if (isset($this->current)) {
                $this->parseProps[] = $this->current;
                unset($this->current);
            }
        }
    }

    /**
     * input data handler
     *
     * @param  resource  parser
     * @param  string    data
     * @return void
     * @access private
     */
    function _proppatchData($parser, $data)
    {
        if (isset($this->current)) {
            $this->current["val"] .= $data;
        }
    }

}

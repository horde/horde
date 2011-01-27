<?php
/**
 * Copyright 2010 Horde LLC
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Ben Klang <bklang@horde.org>
 * @category Horde
 * @package  Horde_Rpc
 */

class Horde_Rpc_Webdav2 extends Horde_Rpc
{

    private $_server;

    public function __construct($request, $params = array())
    {
        // PHP messages destroy XML output -> switch them off
        ini_set('display_errors', 0);

        if (strstr($_SERVER['PATH_INFO'])) {

        }
        $this->_server = $this->_getCalDAVServer();

        parent::__construct($request, $params);
    }

    private function _getCalDAVServer()
    {
        /* Get Horde objects for backends */
        $auth = $GLOBALS['injector']->getInstance('Horde_Auth');
        $registry = $GLOBALS['injector']->getInstance('Horde_Registry');

        /* Backends */
        $authBackend = new Sabre_DAV_Auth_Backend_Horde($registry);
        $calendarBackend = new Sabre_CalDAV_Backend_Horde($auth);

        /* Directory structure */
        $root = new Sabre_DAV_SimpleDirectory('root');
        $principals = new Sabre_DAV_Auth_PrincipalCollection($authBackend);
        $root->addChild($principals);
        $calendars = new Sabre_CalDAV_CalendarRootNode($authBackend, $calendarBackend);
        $root->addChild($calendars);

        $objectTree = new Sabre_DAV_ObjectTree($root);

        /* Initializing server */
        Sabre_DAV_Server::__construct($objectTree);

        /* Server Plugins */
        $authPlugin = new Sabre_DAV_Auth_Plugin($authBackend, 'Horde DAV Server');
        $this->addPlugin($authPlugin);

        $caldavPlugin = new Sabre_CalDAV_Plugin();
        $this->addPlugin($caldavPlugin);
    }
}
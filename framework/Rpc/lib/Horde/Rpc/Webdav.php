<?php
/**
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Ben Klang <bklang@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Rpc
 */

use Sabre\DAV;
use Sabre\DAVACL;
use Sabre\CalDAV;
use Sabre\CardDAV;

/**
 * @author   Ben Klang <bklang@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Rpc
 */
class Horde_Rpc_Webdav extends Horde_Rpc
{
    /**
     * Do we need an authenticated user?
     *
     * @var boolean
     */
    protected $_requireAuthorization = false;

    /**
     * The server instance.
     *
     * @var Sabre\DAV\Server
     */
    protected $_server;

    /**
     * Constructor.
     *
     * @param Horde_Controller_Request_Http $request  The request object.
     * @param array $params  A hash containing any additional configuration or
     *                       connection parameters.
     */
    public function __construct($request, $params = array())
    {
        global $conf, $injector, $registry;

        parent::__construct($request, $params);

        $principalBackend = new Horde_Dav_Principals(
            $injector->getInstance('Horde_Core_Factory_Auth')->create(),
            $injector->getInstance('Horde_Core_Factory_Identity')
        );
        $principals = new DAVACL\PrincipalCollection($principalBackend);
        $principals->disableListing = $conf['auth']['list_users'] == 'input';

        $calendarBackend = new Horde_Dav_Calendar_Backend($registry, $injector->getInstance('Horde_Dav_Storage'));
        $caldav = new CalDAV\CalendarRootNode($principalBackend, $calendarBackend);
        $contactsBackend = new Horde_Dav_Contacts_Backend($registry);
        $carddav = new CardDAV\AddressBookRoot($principalBackend, $contactsBackend);

        $this->_server = new DAV\Server(
            new Horde_Dav_RootCollection(
                $registry,
                array($principals, $caldav, $carddav),
                isset($conf['mime']['magic_db']) ? $conf['mime']['magic_db'] : null
            )
        );
        $this->_server->setBaseUri(
            $registry->get('webroot', 'horde')
            . ($GLOBALS['conf']['urls']['pretty'] == 'rewrite' ? '/rpc/' : '/rpc.php/')
        );
        $this->_server->addPlugin(
            new DAV\Auth\Plugin(
                new Horde_Dav_Auth(
                    $injector->getInstance('Horde_Core_Factory_Auth')->create()
                ),
                'Horde DAV Server'
            )
        );
        $this->_server->addPlugin(
            new CalDAV\Plugin()
        );
        $this->_server->addPlugin(
            new CardDAV\Plugin()
        );
        $this->_server->addPlugin(
            new DAV\Locks\Plugin(
                new Horde_Dav_Locks($registry, $injector->getInstance('Horde_Lock'))
            )
        );
        $this->_server->addPlugin(new DAV\Browser\Plugin());
    }

    /**
     * Implemented in Sabre\DAV\Server.
     */
    public function getInput()
    {
        return '';
    }

    /**
     * Sends an RPC request to the server and returns the result.
     *
     * @param string  The raw request string.
     *
     * @return string  The XML encoded response from the server.
     */
    public function getResponse($request)
    {
        //xdebug_break();
        $this->_server->exec();
    }

    /**
     * Implemented in Sabre\DAV\Server.
     */
    public function sendOutput($output)
    {
    }
}

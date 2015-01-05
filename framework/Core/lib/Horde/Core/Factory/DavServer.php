<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @package  Core
 */

use Sabre\DAV;
use Sabre\DAVACL;
use Sabre\CalDAV;
use Sabre\CardDAV;

/**
 * Factory for the DAV server.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @package  Core
 */
class Horde_Core_Factory_DavServer extends Horde_Core_Factory_Injector
{
    public function create(Horde_Injector $injector)
    {
        global $conf, $registry;

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

        $server = new DAV\Server(
            new Horde_Dav_RootCollection(
                $registry,
                array($principals, $caldav, $carddav),
                isset($conf['mime']['magic_db']) ? $conf['mime']['magic_db'] : null
            )
        );
        $server->debugExceptions = false;
        $server->setBaseUri(
            $registry->get('webroot', 'horde')
            . ($GLOBALS['conf']['urls']['pretty'] == 'rewrite' ? '/rpc/' : '/rpc.php/')
        );
        $server->addPlugin(
            new DAV\Auth\Plugin(
                new Horde_Dav_Auth(
                    $injector->getInstance('Horde_Core_Factory_Auth')->create()
                ),
                'Horde DAV Server'
            )
        );
        $server->addPlugin(
            new CalDAV\Plugin()
        );
        $server->addPlugin(
            new CardDAV\Plugin()
        );
        $server->addPlugin(
            new DAV\Locks\Plugin(
                new Horde_Dav_Locks($registry, $injector->getInstance('Horde_Lock'))
            )
        );
        $server->addPlugin(new DAVACL\Plugin());
        $server->addPlugin(new DAV\Browser\Plugin());

        return $server;
    }
}

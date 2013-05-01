<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Dav
 */

use Sabre\CardDAV;

/**
 * Address books collection node.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Dav
 */
class Horde_Dav_Contacts_RootNode extends CardDAV\AddressBookRoot
{
    /**
     * A registry object.
     *
     * @var Horde_Registry
     */
    protected $_registry;

    /**
     * Constructor
     *
     * @param PrincipalBackend\BackendInterface $principalBackend
     * @param Backend\BackendInterface $carddavBackend
     * @param Horde_Registry $registry  A registry object.
     */
    public function __construct(
        PrincipalBackend\BackendInterface $principalBackend,
        Backend\BackendInterface $carddavBackend,
        Horde_Registry $registry)
    {

        parent::__construct($principalBackend, $carddavBackend);
        $this->_registry = $registry;
    }
}

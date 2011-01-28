<?php
/**
 * Return the mail addresses of the KolabInetOrgPersons with the given uid or
 * mail address and include all alias and delegate addresses.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Return the mail addresses of the KolabInetOrgPersons with the given uid or
 * mail address and include all alias and delegate addresses.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Search_Operation_Addressesforuidormail
extends Horde_Kolab_Server_Search_Operation_Base
{

    /**
     * The basic attribute search.
     *
     * @var Horde_Kolab_Server_Search_Operation
     */
    private $_search;

    /**
     * Constructor
     *
     * @param Horde_Kolab_Server_Composite $composite A link to the composite
     *                                                server handler.
     */
    public function __construct(Horde_Kolab_Server_Composite $composite)
    {
        $this->_composite = $composite;
        $this->_search = new Horde_Kolab_Server_Search_Operation_Attributes(
            $this->getComposite()
        );
    }

    /**
     * Return the mail addresses of the KolabInetOrgPersons with the given uid
     * or mail address and include all alias and delegate addresses.
     *
     * @param string $uid  The uid to search for.
     * @param string $mail The mail address to search for.
     *
     * @return array The GUID(s).
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function searchAddressesForUidOrMail($uid, $mail)
    {
        $criteria = new Horde_Kolab_Server_Query_Element_And(
            new Horde_Kolab_Server_Query_Element_Equals(
                'Objectclass',
                Horde_Kolab_Server_Object_Kolabinetorgperson::OBJECTCLASS_KOLABINETORGPERSON
            ),
            new Horde_Kolab_Server_Query_Element_Or(
                new Horde_Kolab_Server_Query_Element_Equals(
                    'Uid', $uid
                ),
                new Horde_Kolab_Server_Query_Element_Equals(
                    'Mail', $mail
                )
            )
        );
        $search = new Horde_Kolab_Server_Search_Operation_Constraint_Strict(
            $this->_search
        );

        $data = $search->searchAttributes(
            $criteria, array('Mail', 'Alias')
        );

        if (empty($data)) {
            return array();
        }

        $mail = $this->getComposite()->structure->getAttributeInternal(
            'Mail'
        );
        $alias = $this->getComposite()->structure->getAttributeInternal(
            'Alias'
        );

        if (isset($result[$alias])) {
            $addrs = array_merge($data[$mail], $data[$alias]);
        } else {
            $addrs = $data[$mail];
        }

        $criteria = new Horde_Kolab_Server_Query_Element_And(
            new Horde_Kolab_Server_Query_Element_Equals(
                'Objectclass',
                Horde_Kolab_Server_Object_Kolabinetorgperson::OBJECTCLASS_KOLABINETORGPERSON
            ),
            new Horde_Kolab_Server_Query_Element_Equals(
                'Delegate',  $data[$mail][0]
            )
        );

        $data = $this->_search->searchAttributes(
            $criteria, array('Mail', 'Alias')
        );

        if (!empty($data)) {
            foreach ($data as $adr) {
                if (isset($adr[$mail])) {
                    $addrs = array_merge($addrs, $adr[$mail]);
                }
                if (isset($adr[$alias])) {
                    $addrs = array_merge($addrs, $adr[$alias]);
                }
            }
        }

        $addrs = array_map('strtolower', $addrs);

        return $addrs;
    }
}
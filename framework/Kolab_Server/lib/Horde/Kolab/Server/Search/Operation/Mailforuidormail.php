<?php
/**
 * Return the mail address of the KolabInetOrgPersons with the given uid or mail
 * address.
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
 * Return the mail address of the KolabInetOrgPersons with the given uid or mail
 * address.
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
class Horde_Kolab_Server_Search_Operation_Mailforuidormail
extends Horde_Kolab_Server_Search_Operation_Base
{

    /**
     * The base attribute search.
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
        $this->_search = new Horde_Kolab_Server_Search_Operation_Constraint_Strict(
            new Horde_Kolab_Server_Search_Operation_Attributes(
                $this->getComposite()
            )
        );
    }

    /**
     * Return the mail address of the KolabInetOrgPersons with the given uid or
     * mail address.
     *
     * @param string $uid  The uid to search for.
     * @param string $mail The mail address to search for.
     *
     * @return array The GUID(s).
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function searchMailForUidOrMail($uid, $mail)
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
        $data = $this->_search->searchAttributes($criteria, array('Mail'));

        $internal = $this->getComposite()->structure->getAttributeInternal(
            'Mail'
        );
        if (!empty($data)) {
            return $data[$internal][0];
        } else {
            return false;
        }
    }
}
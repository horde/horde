<?php
/**
 * Return all KolabInetOrgPersons with the given uid or mail address.
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
 * Return all KolabInetOrgPersons with the given uid or mail address.
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
class Horde_Kolab_Server_Search_Operation_Guidforuidormail
implements Horde_Kolab_Server_Search_Operation_Interface
{
    /**
     * A link to the search.
     *
     * @var Horde_Kolab_Server_Search
     */
    private $_search;

    /**
     * Constructor
     *
     * @param Horde_Kolab_Server_Structure_Interface $structure A link to the
     *                                                          server
     *                                                          structure.
     */
    public function __construct(
        Horde_Kolab_Server_Structure_Interface $structure
    ) {
        $this->_search = new Horde_Kolab_Server_Search_Operation_Constraint_Strict(
            new Horde_Kolab_Server_Search_Operation_Restrictkolab(
                $structure
            )
        );
    }

    /**
     * Return the reference to the server structure.
     *
     * @return Horde_Kolab_Server_Structure_Interface
     */
    public function getStructure()
    {
        return $this->_search->getStructure();
    }

    /**
     * Return all KolabInetOrgPersons with the given uid or mail address.
     *
     * @param string $id The uid or mail address to search for.
     *
     * @return array The GUID(s).
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function searchGuidForUidOrMail($id)
    {
        $criteria = new Horde_Kolab_Server_Query_Element_Or(
            array(
                new Horde_Kolab_Server_Query_Element_Equals(
                    'Uid', $id
                ),
                new Horde_Kolab_Server_Query_Element_Equals(
                    'Mail', $id
                )
            )
        );
        return $this->_search->searchRestrictKolab($criteria);
    }
}
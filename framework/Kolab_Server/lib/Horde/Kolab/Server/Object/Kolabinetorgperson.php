<?php
/**
 * A bsaic object representation.
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
 * This class provides basic methods common to all Kolab server objects.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Object_Kolabinetorgperson extends Horde_Kolab_Server_Object_Inetorgperson
{

    const ATTRIBUTE_ALIAS         = 'alias';
    const ATTRIBUTE_DELEGATE      = 'kolabDelegate';
    const ATTRIBUTE_DELETED       = 'kolabDeleteFlag';
    const ATTRIBUTE_FBFUTURE      = 'kolabFreeBusyFuture';
    const ATTRIBUTE_FOLDERTYPE    = 'kolabFolderType';
    const ATTRIBUTE_HOMESERVER    = 'kolabHomeServer';
    const ATTRIBUTE_FREEBUSYHOST  = 'kolabFreeBusyServer';
    const ATTRIBUTE_IMAPHOST      = 'kolabImapServer';
    const ATTRIBUTE_IPOLICY       = 'kolabInvitationPolicy';
    const ATTRIBUTE_SALUTATION    = 'kolabSalutation';
    const ATTRIBUTE_GENDER        = 'gender';
    const ATTRIBUTE_MARITALSTATUS = 'kolabMaritalStatus';

    const OBJECTCLASS_KOLABINETORGPERSON = 'kolabInetOrgPerson';

    /**
     * A structure to initialize the attribute structure for this class.
     *
     * @var array
     */
    static public $init_attributes = array(
        'defined' => array(
            self::ATTRIBUTE_IMAPHOST,
            self::ATTRIBUTE_HOMESERVER,
            self::ATTRIBUTE_FREEBUSYHOST,
            self::ATTRIBUTE_SALUTATION,
            self::ATTRIBUTE_GENDER,
            self::ATTRIBUTE_MARITALSTATUS,
            self::ATTRIBUTE_IPOLICY,
            self::ATTRIBUTE_ALIAS,
            self::ATTRIBUTE_DELEGATE,
            self::ATTRIBUTE_FBFUTURE,
        ),
        'locked' => array(
            self::ATTRIBUTE_MAIL,
        ),
        'object_classes' => array(
            self::OBJECTCLASS_KOLABINETORGPERSON,
        ),
    );

    /**
     * Returns the set of search operations supported by this object type.
     *
     * @return array An array of supported search operations.
     */
    static public function getSearchOperations()
    {
        $searches = array(
            'uidForSearch',
            'uidForId',
            'uidForMail',
            'uidForIdOrMail',
            'uidForAlias',
            'uidForMailOrAlias',
            'uidForIdOrMailOrAlias',
            'mailForIdOrMail',
            'addrsForIdOrMail',
        );
        return $searches;
    }

    /**
     * Identify the kolab UID for the first object found using the specified
     * search criteria.
     *
     * @param array $criteria The search parameters as array.
     * @param int   $restrict A Horde_Kolab_Server::RESULT_* result restriction.
     *
     * @return boolean|string|array The UID(s) or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    static public function uidForSearch($server, $criteria,
                                        $restrict = Horde_Kolab_Server_Object::RESULT_SINGLE)
    {
        $users = array('field' => self::ATTRIBUTE_OC,
                       'op'    => '=',
                       'test'  => self::OBJECTCLASS_KOLABINETORGPERSON);
        if (!empty($criteria)) {
            $criteria = array('AND' => array($users, $criteria));
        } else {
            $criteria = array('AND' => array($users));
        }
        return self::basicUidForSearch($server, $criteria, $restrict);
    }

    /**
     * Identify the UID for the first object found with the given ID.
     *
     * @param string $id       Search for objects with this ID.
     * @param int    $restrict A Horde_Kolab_Server::RESULT_* result restriction.
     *
     * @return mixed The UID or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    static public function uidForId($server, $id,
                                    $restrict = Horde_Kolab_Server_Object::RESULT_SINGLE)
    {
        $criteria = array('AND' => array(array('field' => self::ATTRIBUTE_SID,
                                               'op'    => '=',
                                               'test'  => $id),
                          ),
        );
        return self::uidForSearch($server, $criteria, $restrict);
    }

    /**
     * Identify the UID for the first user found with the given mail.
     *
     * @param string $mail     Search for users with this mail address.
     * @param int    $restrict A Horde_Kolab_Server::RESULT_* result restriction.
     *
     * @return mixed The UID or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    static public function uidForMail($server, $mail,
                               $restrict = Horde_Kolab_Server_Object::RESULT_SINGLE)
    {
        $criteria = array('AND' => array(array('field' => self::ATTRIBUTE_MAIL,
                                               'op'    => '=',
                                               'test'  => $mail),
                          ),
        );
        return self::uidForSearch($server, $criteria, $restrict);
    }

    /**
     * Identify the UID for the first object found with the given ID or mail.
     *
     * @param string $id Search for objects with this uid/mail.
     *
     * @return string|boolean The UID or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    static public function uidForIdOrMail($server, $id)
    {
        $criteria = array('OR' =>
                         array(
                             array('field' => self::ATTRIBUTE_SID,
                                   'op'    => '=',
                                   'test'  => $id),
                             array('field' => self::ATTRIBUTE_MAIL,
                                   'op'    => '=',
                                   'test'  => $id),
                         ),
        );
        return self::uidForSearch($server, $criteria);
    }

    /**
     * Identify the UID for the first object found with the given alias.
     *
     * @param string $mail     Search for objects with this mail alias.
     * @param int    $restrict A Horde_Kolab_Server::RESULT_* result restriction.
     *
     * @return mixed The UID or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    static public function uidForAlias($server, $mail,
                                $restrict = Horde_Kolab_Server_Object::RESULT_SINGLE)
    {
        $criteria = array('AND' => array(array('field' => self::ATTRIBUTE_ALIAS,
                                               'op'    => '=',
                                               'test'  => $mail),
                          ),
        );
        return self::uidForSearch($server, $criteria, $restrict);
    }

    /**
     * Identify the UID for the first object found with the given mail
     * address or alias.
     *
     * @param string $mail Search for objects with this mail address
     * or alias.
     *
     * @return string|boolean The UID or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    static public function uidForMailOrAlias($server, $mail)
    {
        $criteria = array('OR' =>
                         array(
                             array('field' => self::ATTRIBUTE_ALIAS,
                                   'op'    => '=',
                                   'test'  => $mail),
                             array('field' => self::ATTRIBUTE_MAIL,
                                   'op'    => '=',
                                   'test'  => $mail),
                         )
        );
        return self::uidForSearch($server, $criteria);
    }

    /**
     * Identify the UID for the first object found with the given ID,
     * mail or alias.
     *
     * @param string $id Search for objects with this ID/mail/alias.
     *
     * @return string|boolean The UID or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    static public function uidForIdOrMailOrAlias($server, $id)
    {
        $criteria = array('OR' =>
                         array(
                             array('field' => self::ATTRIBUTE_ALIAS,
                                   'op'    => '=',
                                   'test'  => $id),
                             array('field' => self::ATTRIBUTE_MAIL,
                                   'op'    => '=',
                                   'test'  => $id),
                             array('field' => self::ATTRIBUTE_SID,
                                   'op'    => '=',
                                   'test'  => $id),
                         ),
        );
        return self::uidForSearch($server, $criteria);
    }

    /**
     * Identify the primary mail attribute for the first object found
     * with the given ID or mail.
     *
     * @param string $id Search for objects with this ID/mail.
     *
     * @return mixed The mail address or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    static public function mailForIdOrMail($server, $id)
    {
        $criteria = array('AND' =>
                         array(
                             array('field' => self::ATTRIBUTE_OC,
                                   'op'    => '=',
                                   'test'  => self::OBJECTCLASS_KOLABINETORGPERSON),
                             array('OR' =>
                                   array(
                                       array('field' => self::ATTRIBUTE_SID,
                                             'op'    => '=',
                                             'test'  => $id),
                                       array('field' => self::ATTRIBUTE_MAIL,
                                             'op'    => '=',
                                             'test'  => $id),
                                   ),
                             ),
                         ),
        );

        $data = self::attrsForSearch($server, $criteria, array(self::ATTRIBUTE_MAIL),
                                     self::RESULT_STRICT);
        if (!empty($data)) {
            return $data[self::ATTRIBUTE_MAIL][0];
        } else {
            return false;
        }
    }

    /**
     * Returns a list of allowed email addresses for the given user.
     *
     * @param string $id Search for objects with this ID/mail.
     *
     * @return array An array of allowed mail addresses.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    static public function addrsForIdOrMail($server, $id)
    {
        $criteria = array('AND' =>
                         array(
                             array('field' => self::ATTRIBUTE_OC,
                                   'op'    => '=',
                                   'test'  => self::OBJECTCLASS_KOLABINETORGPERSON),
                             array('OR' =>
                                   array(
                                       array('field' => self::ATTRIBUTE_SID,
                                             'op'    => '=',
                                             'test'  => $id),
                                       array('field' => self::ATTRIBUTE_MAIL,
                                             'op'    => '=',
                                             'test'  => $id),
                                   ),
                             ),
                         ),
        );

        $result = self::attrsForSearch($server, $criteria,
                                       array(self::ATTRIBUTE_MAIL,
                                             self::ATTRIBUTE_ALIAS),
                                       self::RESULT_STRICT);
        if (isset($result[self::ATTRIBUTE_ALIAS])) {
            $addrs = array_merge((array) $result[self::ATTRIBUTE_MAIL],
                                 (array) $result[self::ATTRIBUTE_ALIAS]);
        } else {
            $addrs = $result[self::ATTRIBUTE_MAIL];
        }

        if (empty($result)) {
            return array();
        }
        $criteria = array('AND' =>
                         array(
                             array('field' => self::ATTRIBUTE_OC,
                                   'op'    => '=',
                                   'test'  => self::OBJECTCLASS_KOLABINETORGPERSON),
                             array('field' => self::ATTRIBUTE_DELEGATE,
                                   'op'    => '=',
                                   'test'  => $result[self::ATTRIBUTE_MAIL][0]),
                         ),
        );

        $result = self::attrsForSearch($server, $criteria,
                                       array(self::ATTRIBUTE_MAIL,
                                             self::ATTRIBUTE_ALIAS),
                                       self::RESULT_MANY);
        if (!empty($result)) {
            foreach ($result as $adr) {
                if (isset($adr[self::ATTRIBUTE_MAIL])) {
                    $addrs = array_merge((array) $addrs, (array) $adr[self::ATTRIBUTE_MAIL]);
                }
                if (isset($adr[self::ATTRIBUTE_ALIAS])) {
                    $addrs = array_merge((array) $addrs, (array) $adr[self::ATTRIBUTE_ALIAS]);
                }
            }
        }

        $addrs = array_map('strtolower', $addrs);

        return $addrs;
    }

}
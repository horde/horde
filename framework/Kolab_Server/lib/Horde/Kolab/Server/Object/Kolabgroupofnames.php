<?php
/**
 * Representation of a Kolab user group.
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
 * This class provides methods to deal with groups for Kolab.
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
class Horde_Kolab_Server_Object_Kolabgroupofnames extends Horde_Kolab_Server_Object_Groupofnames
{
    /** Define attributes specific to this object type */

    /** The visibility of the group */
    const ATTRIBUTE_VISIBILITY = 'visible';

    /** The ou subtree of the group */
    const ATTRIBUTE_OU = 'ou';

    /** The members of this group */
    const ATTRIBUTE_MEMBER = 'member';

    /** The mail address of this group */
    const ATTRIBUTE_MAIL = 'mail';

    /** The specific object class of this object type */
    const OBJECTCLASS_KOLABGROUPOFNAMES = 'kolabGroupOfNames';

    /**
     * A structure to initialize the attribute structure for this class.
     *
     * @var array
     */
    static public $init_attributes = array(
        'defined' => array(
            self::ATTRIBUTE_VISIBILITY,
            self::ATTRIBUTE_MAIL,
        ),
        'derived' => array(
            self::ATTRIBUTE_VISIBILITY => array(),
        ),
        'object_classes' => array(
            self::OBJECTCLASS_KOLABGROUPOFNAMES,
        ),
    );

    /**
     * Return the filter string to retrieve this object type.
     *
     * @return string The filter to retrieve this object type from the server
     *                database.
     */
    public static function getFilter()
    {
        $criteria = array('AND' => array(array('field' => self::ATTRIBUTE_OC,
                                               'op'    => '=',
                                               'test'  => self::OBJECTCLASS_KOLABGROUPOFNAMES),
                          ),
        );
        return $criteria;
    }

    /**
     * Derive an attribute value.
     *
     * @param string $attr The attribute to derive.
     *
     * @return mixed The value of the attribute.
     */
    protected function derive($attr)
    {
        switch ($attr) {
        case self::ATTRIBUTE_VISIBILITY:
            //@todo This needs structural knowledge and should be in a
            //structural class.
            return strpos($this->uid, 'cn=internal') === false;
        default:
            return parent::derive($attr);
        }
    }

    /**
     * Generates an ID for the given information.
     *
     * @param array $info The data of the object.
     *
     * @static
     *
     * @return string|PEAR_Error The ID.
     */
    public function generateId(array &$info)
    {
        if ($this->exists()) {
            if (!isset($info[self::ATTRIBUTE_MAIL])
                && !isset($info[self::ATTRIBUTE_CN])) {
                return false;
            }
            if (!isset($info[self::ATTRIBUTE_MAIL])) {
                $info[self::ATTRIBUTE_MAIL] = $this->get(self::ATTRIBUTE_MAIL);
            }
            if (!isset($info[self::ATTRIBUTE_CN])) {
                $info[self::ATTRIBUTE_CN] = $this->get(self::ATTRIBUTE_CN);
            }
        }

        if (isset($info[self::ATTRIBUTE_MAIL])) {
            $id = $info[self::ATTRIBUTE_MAIL];
        } else {
            $id = $info[self::ATTRIBUTE_CN];
        }
        if (is_array($id)) {
            $id = $id[0];
        }
        return self::ATTRIBUTE_CN . '=' . $this->server->structure->quoteForUid(trim($id, " \t\n\r\0\x0B,"));
    }

    /**
     * Distill the server side object information to save.
     *
     * @param array $info The information about the object.
     *
     * @return NULL.
     *
     * @throws Horde_Kolab_Server_Exception If the given information contains errors.
     */
    public function prepareObjectInformation(array &$info)
    {
        if (!$this->exists()) {
            if (!isset($info[self::ATTRIBUTE_CN])) {
                if (!isset($info[self::ATTRIBUTE_MAIL])) {
                    throw new Horde_Kolab_Server_Exception('Either the mail address or the common name has to be specified for a group object!');
                } else {
                    $info[self::ATTRIBUTE_CN] = $info[self::ATTRIBUTE_MAIL];
                }
            }
        }
    }

    /**
     * Returns the set of search operations supported by this object type.
     *
     * @return array An array of supported search operations.
     */
    static public function getSearchOperations()
    {
        $searches = array(
/*             'gidForMail', */
/*             'memberOfGroupAddress', */
/*             'getGroupAddresses', */
        );
        return $searches;
    }

    /**
     * Identify the GID for the first group found with the given mail.
     *
     * @param string $mail     Search for groups with this mail address.
     * @param int    $restrict A Horde_Kolab_Server::RESULT_* result restriction.
     *
     * @return mixed The GID or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    static public function gidForMail($server, $mail,
                                      $restrict = 0)
    {
        $criteria = array('AND' => array(array('field' => self::ATTRIBUTE_MAIL,
                                               'op'    => '=',
                                               'test'  => $mail),
                         ),
        );
        return self::gidForSearch($server, $criteria, $restrict);
    }

    /**
     * Is the given UID member of the group with the given mail address?
     *
     * @param string $uid  UID of the user.
     * @param string $mail Search the group with this mail address.
     *
     * @return boolean True in case the user is in the group, false otherwise.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    static public function memberOfGroupAddress($server, $uid, $mail)
    {
        $criteria = array('AND' =>
                          array(
                              array('field' => self::ATTRIBUTE_MAIL,
                                    'op'    => '=',
                                    'test'  => $mail),
                              array('field' => self::ATTRIBUTE_MEMBER,
                                    'op'    => '=',
                                    'test'  => $uid),
                          ),
        );

        $result = self::gidForSearch($server, $criteria,
                                      self::RESULT_SINGLE);
        return !empty($result);
    }


    /**
     * Get the mail addresses for the group of this object.
     *
     * @param string $uid The UID of the object to fetch.
     *
     * @return array An array of mail addresses.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    static public function getGroupAddresses($server, $uid)
    {
        $criteria = array('AND' =>
                          array(
                              array('field' => self::ATTRIBUTE_OC,
                                    'op'    => '=',
                                    'test'  => self::OBJECTCLASS_GROUPOFNAMES),
                              array('field' => self::ATTRIBUTE_MEMBER,
                                    'op'    => '=',
                                    'test'  => $uid),
                          ),
        );

        $data = self::attrsForSearch($server, $criteria, array(self::ATTRIBUTE_MAIL),
                                     self::RESULT_MANY);

        if (empty($data)) {
            return array();
        }

        $mails = array();
        foreach ($data as $element) {
            if (isset($element[self::ATTRIBUTE_MAIL])) {
                if (is_array($element[self::ATTRIBUTE_MAIL])) {
                    $mails = array_merge($mails, $element[self::ATTRIBUTE_MAIL]);
                } else {
                    $mails[] = $element[self::ATTRIBUTE_MAIL];
                }
            }
        }
        return $mails;
    }
}

<?php
/**
 * Implementation for contacts in the Kolab XML format.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */

/**
 * Kolab XML handler for contact groupware objects
 *
 * Copyright 2007-2009 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */
class Horde_Kolab_Format_Xml_Contact extends Horde_Kolab_Format_Xml
{
    /**
     * Specific data fields for the contact object
     *
     * @var array
     */
    protected $_fields_specific;

    /**
     * Structure of the name field
     *
     * @var array
     */
    protected $_fields_name = array(
        'given-name' => array (
            'type'    => self::TYPE_STRING,
            'value'   => self::VALUE_MAYBE_MISSING,
        ),
        'middle-names' => array (
            'type'    => self::TYPE_STRING,
            'value'   => self::VALUE_MAYBE_MISSING,
        ),
        'last-name' => array (
            'type'    => self::TYPE_STRING,
            'value'   => self::VALUE_MAYBE_MISSING,
        ),
        'full-name' => array (
            'type'    => self::TYPE_STRING,
            'value'   => self::VALUE_MAYBE_MISSING,
        ),
        'initials' => array (
            'type'    => self::TYPE_STRING,
            'value'   => self::VALUE_MAYBE_MISSING,
        ),
        'prefix' => array (
            'type'    => self::TYPE_STRING,
            'value'   => self::VALUE_MAYBE_MISSING,
        ),
        'suffix' => array (
            'type'    => self::TYPE_STRING,
            'value'   => self::VALUE_MAYBE_MISSING,
        )
    );

    /**
     * Structure of an address field
     *
     * @var array
     */
    protected $_fields_address = array(
        'type'    => self::TYPE_COMPOSITE,
        'value'   => self::VALUE_MAYBE_MISSING,
        'array'   => array(
            'type' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_DEFAULT,
                'default' => 'home',
            ),
            'street' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'locality' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'region' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'postal-code' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'country' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
        )
    );

    /**
     * Structure of a phone field
     *
     * @var array
     */
    protected $_fields_phone = array(
        'type'    => self::TYPE_COMPOSITE,
        'value'   => self::VALUE_MAYBE_MISSING,
        'array'   => array(
            'type' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_DEFAULT,
                'default' => '',
            ),
            'number' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
        ),
    );

    /**
     * Address types
     *
     * @var array
     */
    protected $_address_types = array(
        'business',
        'home',
        'other',
    );

    /**
     * Phone types
     *
     * @var array
     */
    protected $_phone_types = array(
        'business1',
        'business2',
        'businessfax',
        'callback',
        'car',
        'company',
        'home1',
        'home2',
        'homefax',
        'isdn',
        'mobile',
        'pager',
        'primary',
        'radio',
        'telex',
        'ttytdd',
        'assistant',
        'other',
    );

    /**
     * Constructor
     */
    public function __construct($parser, $params = array())
    {
        $this->_root_name = 'contact';

        /** Specific task fields, in kolab format specification order
         */
        $this->_fields_specific = array(
            'name' => array (
                'type'    => self::TYPE_COMPOSITE,
                'value'   => self::VALUE_MAYBE_MISSING,
                'array'   => $this->_fields_name,
            ),
            'free-busy-url' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'organization' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'web-page' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'im-address' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'department' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'office-location' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'profession' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'job-title' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'manager-name' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'assistant' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'nick-name' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'spouse-name' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'birthday' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'anniversary' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'picture' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'children' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'gender' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'language' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'address' => array(
                'type'    => self::TYPE_MULTIPLE,
                'value'   => self::VALUE_MAYBE_MISSING,
                'array'   => $this->_fields_address,
            ),
            'email' => array (
                'type'    => self::TYPE_MULTIPLE,
                'value'   => self::VALUE_MAYBE_MISSING,
                'array'   => $this->_fields_simple_person,
            ),
            'phone' => array(
                'type'    => self::TYPE_MULTIPLE,
                'value'   => self::VALUE_MAYBE_MISSING,
                'array'   => $this->_fields_phone,
            ),
            'preferred-address' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'latitude' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'longitude' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            // Horde specific fields
            'pgp-publickey' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            // Support for broken clients
            'website' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
            'im-adress' => array (
                'type'    => self::TYPE_STRING,
                'value'   => self::VALUE_MAYBE_MISSING,
            ),
        );

        parent::__construct($parser, $params);
    }
}

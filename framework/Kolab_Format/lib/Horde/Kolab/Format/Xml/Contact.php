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
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
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
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Xml_Contact extends Horde_Kolab_Format_Xml
{
    /**
     * The name of the root element.
     *
     * @var string
     */
    protected $_root_name = 'contact';

    /**
     * Specific data fields for the contact object
     *
     * @var array
     */
    protected $_fields_specific = array(
        'name'              => 'Horde_Kolab_Format_Xml_Type_Composite_Name',
        'free-busy-url'     => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'organization'      => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'web-page'          => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'im-address'        => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'department'        => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'office-location'   => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'profession'        => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'job-title'         => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'manager-name'      => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'assistant'         => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'nick-name'         => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'spouse-name'       => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'birthday'          => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'anniversary'       => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'picture'           => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'children'          => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'gender'            => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'language'          => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'address'           => 'Horde_Kolab_Format_Xml_Type_Multiple_Address',
        'email'             => 'Horde_Kolab_Format_Xml_Type_Multiple_SimplePerson',
        'phone'             => 'Horde_Kolab_Format_Xml_Type_Multiple_Phone',
        'preferred-address' => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'latitude'          => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'longitude'         => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        // Horde specific fields
        'pgp-publickey'     => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        // Support for broken clients
        'website'           => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
        'im-adress'         => 'Horde_Kolab_Format_Xml_Type_String_MaybeMissing',
    );
}

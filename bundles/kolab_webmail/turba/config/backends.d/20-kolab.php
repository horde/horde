<?php

$cfgSources = array();

/* A global address book for a Kolab Server. This is typically a
 * read-only public directory, stored in the default Kolab LDAP server.
 * The user accessing this should have read permissions to the shared
 * directory in LDAP. */
$cfgSources['kolab_global'] = array(
    // ENABLED if LDAP functionality is present
    'disabled' => !function_exists('ldap_connect'),
    'title' => _("Global Address Book"),
    'type' => 'ldap',
    'params' => array(
        'server' => $GLOBALS['conf']['kolab']['ldap']['server'],
        'port' => $GLOBALS['conf']['kolab']['ldap']['port'],
        'tls' => false,
        'root' => $GLOBALS['conf']['kolab']['ldap']['basedn'],
        'sizelimit' => 200,
        'dn' => array('cn'),
        'objectclass' => array(
            'inetOrgPerson'
        ),
        'scope' => 'sub',
        'charset' => 'utf-8',
        'version' => 3,
        'bind_dn' => '',
        'bind_password' => '',
    ),
    'map' => array(
        '__key'             => 'dn',
        'name'              => 'cn',
        'firstname'         => 'givenName',
        'lastname'          => 'sn',
        'email'             => 'mail',
        'alias'             => 'alias',
        'title'             => 'title',
        'company'           => 'o',
        'department'        => 'ou',
        'workStreet'        => 'street',
        'workCity'          => 'l',
        'workProvince'      => 'st',
        'workPostalCode'    => 'postalCode',
        'workCountry'       => 'c',
        'homePhone'         => 'homePhone',
        'workPhone'         => 'telephoneNumber',
        'cellPhone'         => 'mobile',
        'fax'               => 'fax',
        'notes'             => 'description',
        'kolabHomeServer'   => 'kolabHomeServer',
        'freebusyUrl'       => array(
            'fields' => array('kolabHomeServer', 'email'),
            'format' => 'https://%s/freebusy/%s.ifb'
        ),
    ),
    'search' => array(
        'name',
        'firstname',
        'lastname',
        'email',
        'title',
        'company',
        'workAddress',
        'workCity',
        'workProvince',
        'workPostalCode',
        'workCountry',
        'homePhone',
        'workPhone',
        'cellPhone',
        'fax',
        'notes',
    ),
    'strict' => array(
        'dn',
    ),
    'export' => true,
    'browse' => true,
);

/**
 * A local address book on a Kolab or IMAP server. This implements a private
 * per-user address book. Sharing of this source with other users is
 * accomplished by IMAP ACLs and by setting 'use_shares' => true.
 *
 * Still missing attributes are:
 *
 *   picture, sensitivity
 */
$cfgSources['kolab'] = array(
    // ENABLED by default
    'disabled' => false,
    'title' => _("Shared Address Books"),
    'type' => 'kolab',
    'params' => array(
    ),
    'map' => array(
        '__key' => 'uid',
        '__uid' => 'uid',
        '__type' => '__type',
        '__members' => '__members',
        /* Personal */
        'name' => array('fields' => array('namePrefix', 'firstname',
                                          'middlenames', 'lastname',
                                          'nameSuffix'),
                        'format' => '%s %s %s %s %s',
                        'parse' => array(
                            array('fields' => array('firstname', 'middlenames',
                                                    'lastname'),
                                  'format' => '%s %s %s'),
                            array('fields' => array('lastname', 'firstname'),
                                  'format' => '%s, %s'),
                            array('fields' => array('firstname', 'lastname'),
                                  'format' => '%s %s'))),
        'firstname'         => 'given-name',
        'lastname'          => 'last-name',
        'middlenames'       => 'middle-names',
        'namePrefix'        => 'prefix',
        'nameSuffix'        => 'suffix',
        'initials'          => 'initials',
        'nickname'          => 'nick-name',
        'photo'             => 'photo',
        'phototype'         => 'phototype',
        'gender'            => 'gender',
        'birthday'          => 'birthday',
        'spouse'            => 'spouse-name',
        'anniversary'       => 'anniversary',
        'children'          => 'children',
        /* Location */
        'workStreet'        => 'addr-business-street',
        'workCity'          => 'addr-business-locality',
        'workProvince'      => 'addr-business-region',
        'workPostalCode'    => 'addr-business-postal-code',
        'workCountry'       => 'addr-business-country',
        'homeStreet'        => 'addr-home-street',
        'homeCity'          => 'addr-home-locality',
        'homeProvince'      => 'addr-home-region',
        'homePostalCode'    => 'addr-home-postal-code',
        'homeCountry'       => 'addr-home-country',
        /* Communications */
        'emails'            => 'emails',
        'homePhone'         => 'phone-home1',
        'workPhone'         => 'phone-business1',
        'cellPhone'         => 'phone-mobile',
        'fax'               => 'phone-businessfax',
        'instantMessenger'  => 'im-address',
        /* Organization */
        'title'             => 'job-title',
        'role'              => 'profession',
        'company'           => 'organization',
        'department'        => 'department',
        'office'            => 'office-location',
        'manager'           => 'manager-name',
        'assistant'         => 'assistant',
        /* Other */
        'category'          => 'categories',
        'notes'             => 'body',
        'website'           => 'web-page',
        'freebusyUrl'       => 'free-busy-url',
        'language'          => 'language',
        'latitude'          => 'latitude',
        'longitude'         => 'longitude',
        /* Invisible */
        'email'             => 'email',
        'pgpPublicKey'      => 'pgp-publickey',
    ),
    'tabs' => array(
        _("Personal") => array('firstname', 'lastname', 'middlenames',
                               'namePrefix', 'nameSuffix', 'name', 'initials',
                               'nickname', 'gender', 'birthday', 'spouse',
                               'anniversary', 'children', 'photo'),
        _("Location") => array('workStreet', 'workCity', 'workProvince',
                               'workPostalCode', 'workCountry',
                               'homeStreet', 'homeCity', 'homeProvince',
                               'homePostalCode', 'homeCountry'),
        _("Communications") => array('emails', 'homePhone', 'workPhone',
                                     'cellPhone', 'fax', 'instantMessenger'),
        _("Organization") => array('title', 'role', 'company', 'department',
                                   'office', 'manager', 'assistant'),
        _("Other") => array('category', 'notes', 'website', 'freebusyUrl',
                            'language', 'latitude', 'longitude'),
    ),
    'search' => array(
        'name',
        'emails'
    ),
    'strict' => array(
        'uid',
    ),
    'export' => true,
    'browse' => true,
    'list_name_field' => 'lastname',
    'use_shares' => true,
    'all_shares' => true,
);

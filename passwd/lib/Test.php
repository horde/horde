<?php
/**
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/gpl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */
class Passwd_Test extends Horde_Test
{
    /**
     * The module list
     *
     * @var array
     */
    protected $_moduleList = array(
        'ctype' => 'Ctype Support',
        'ldap' => array(
            'descrip' => 'LDAP Support',
            'error' => 'If you will be using the any of the LDAP drivers for password changes, PHP must have ldap support. Compile PHP <code>--with-ldap</code> before continuing.'
        ),
        'mcrypt' => array(
            'descrip' => 'Mcrypt Support',
            'error' => 'If you will be using the smbldap driver for password changes, PHP must have mcrypt support. Compile PHP <code>--with-mcrypt</code> before continuing.'
        ),
        'mhash' => array(
            'descrip' => 'Mhash Support',
            'error' => 'If you will be using the smbldap driver for password changes, PHP must have mhash support. Compile PHP <code>--with-mhash</code> before continuing.'
        ),
        'soap' => array(
            'descrip' => 'SOAP Support',
            'error' => 'If you will be using the SOAP driver for password changes, PHP must have soap support. Compile PHP with <code>--enable-soap</code> before continuing.'
        )
    );

    /**
     * PHP settings list.
     *
     * @var array
     */
    protected $_settingsList = array();

    /**
     * PEAR modules list.
     *
     * @var array
     */
    protected $_pearList = array(
        'Crypt_CHAP' => array(
            'path' => 'Crypt/CHAP.php',
            'error' => 'If you will be using the smbldap driver for password changes, then you must install the PEAR Crypt_CHAP module.',
            'required' => false,
        ),
        'HTTP_Request' => array(
            'path' => 'HTTP/Request.php',
            'error' => 'If you will be using the http driver for password changes, then you must install the PEAR HTTP_Request module.',
            'required' => false,
        )
    );

    /**
     * Required configuration files.
     *
     * @var array
     */
    protected $_fileList = array(
        'config/conf.php' => null,
    );

    /**
     * Inter-Horde application dependencies.
     *
     * @var array
     */
    protected $_appList = array();

    /**
     * Any application specific tests that need to be done.
     *
     * @return string  HTML output.
     */
    public function appTests()
    {
    }
}
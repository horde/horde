<?php
/**
 * The Horde_Kolab_Storage_Namespace_Fixed:: implements the default IMAP
 * namespaces on the Kolab server.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * The Horde_Kolab_Storage_Namespace_Fixed:: implements the default IMAP
 * namespaces on the Kolab server.
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Namespace_Fixed
extends  Horde_Kolab_Storage_Namespace
{
    /**
     * The namespaces.
     *
     * @var array
     */
    protected $_namespaces = array(
        self::PRIV => array(
            'INBOX' => '/',
        ),
        self::OTHER => array(
            'user' => '/',
        ),
        self::SHARED => array(
            '' => '/',
        ),
    );

    /**
     * A prefix in the shared namespaces that will be ignored/removed.
     *
     * @var string
     */
    protected $_sharedPrefix = 'shared.';

    /**
     * Indicates the personal namespace that the class will use to create new
     * folders.
     *
     * @var string
     */
    protected $_primaryPersonalNamespace = 'INBOX';

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_charset = Horde_Nls::getCharset();
    }
}
<?php
/**
 * The Horde_Kolab_Storage_Namespace_Config:: allows to use the information from
 * the IMAP NAMESPACE command to identify the IMAP namespaces on the Kolab
 * server.
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
 * The Horde_Kolab_Storage_Namespace_Config:: allows to use the information from
 * the IMAP NAMESPACE command to identify the IMAP namespaces on the Kolab
 * server.
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
class Horde_Kolab_Storage_Namespace_Imap
extends  Horde_Kolab_Storage_Namespace
{
    /**
     * The namespaces.
     *
     * @var array
     */
    protected $_namespaces = array();

    /**
     * A prefix in the shared namespaces that will be ignored/removed.
     *
     * @var string
     */
    protected $_sharedPrefix = '';

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
    public function __construct(array $namespaces, array $configuration)
    {
        foreach ($namespaces as $namespace) {
            $this->_namespaces[$namespace['type']][$namespace['name']] = $namespace['delimiter'];
        }
        if (isset($configuration['shared_prefix'])) {
            $this->_sharedPrefix = $configuration['shared_prefix'];
        }
        if (isset($configuration['add_namespace'])) {
            $this->_primaryPersonalNamespace = $configuration['add_namespace'];
        }
        $this->_charset = Horde_Nls::getCharset();
    }
}
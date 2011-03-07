<?php
/**
 * An item returned from a folder list.
 *
 * Copyright 2002-2007 Jon Wood <jon@jellybob.co.uk>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jon Wood <jon@jellybob.co.uk>
 * @package VFS
 */
class VFS_ListItem
{
    /**
     * VFS path.
     *
     * @var string
     */
    protected $_path;

    /**
     * Filename.
     *
     * @var string
     */
    protected $_name;

    /**
     * File permissions (*nix format: drwxrwxrwx).
     *
     * @var string
     */
    protected $_perms;

    /**
     * Owner user.
     *
     * @var string
     */
    protected $_owner;

    /**
     * Owner group.
     *
     * @var string
     */
    protected $_group;

    /**
     * Size.
     *
     * @var string
     */
    protected $_size;

    /**
     * Last modified date.
     *
     * @var string
     */
    protected $_date;

    /**
     * Type.
     * <pre>
     * .*     - File extension
     * **none - Unrecognized type
     * **sym  - Symlink
     * **dir  - Directory
     * </pre>
     *
     * @var string
     */
    protected $_type;

    /**
     * Type of target if type is '**sym'.
     * NB. Not all backends are capable of distinguishing all of these.
     * <pre>
     * .*       - File extension
     * **none   - Unrecognized type
     * **sym    - Symlink to a symlink
     * **dir    - Directory
     * **broken - Target not found - broken link
     * </pre>
     *
     * @var string
     */
    protected $_linktype;

    /**
     * Constructor
     *
     * Requires the path to the file, and it's array of properties,
     * returned from a standard VFS::listFolder() call.
     *
     * @param string $path      The path to the file.
     * @param array $fileArray  An array of file properties.
     */
    public function __construct($path, $fileArray)
    {
        $this->_path = $path . '/' . $fileArray['name'];
        $this->_name = $fileArray['name'];
        $this->_dirname = $path;
        $this->_perms = $fileArray['perms'];
        $this->_owner = $fileArray['owner'];
        $this->_group = $fileArray['group'];
        $this->_size = $fileArray['size'];
        $this->_date = $fileArray['date'];
        $this->_type = $fileArray['type'];
        $this->_linktype = $fileArray['linktype'];
    }

}

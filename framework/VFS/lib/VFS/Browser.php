<?php
/**
 * Class for providing a generic UI for any VFS instance.
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package VFS
 */
class VFS_Browser
{
    /**
     * The VFS instance that we are browsing.
     *
     * @var VFS
     */
    protected $_vfs;

    /**
     * The directory where the templates to use are.
     *
     * @var string
     */
    protected $_templates;

    /**
     * Constructor
     *
     * @param VFS $vfs           A VFS object.
     * @param string $templates  Template directory.
     */
    public function __construct($vfs, $templates)
    {
        $this->setVFSObject($vfs);
        $this->_templates = $templates;
    }

    /**
     * Set the VFS object in the local object.
     *
     * @param VFS $vfs  A VFS object.
     */
    public function setVFSObject($vfs)
    {
        $this->_vfs = $vfs;
    }

    /**
     * TODO
     *
     * @param string $path       TODO
     * @param boolean $dotfiles  TODO
     * @param boolean $dironly   TODO
     *
     * @throws VFS_Exception
     */
    public function getUI($path, $dotfiles = false, $dironly = false)
    {
        $this->_vfs->listFolder($path, $dotfiles, $dironly);
    }

}

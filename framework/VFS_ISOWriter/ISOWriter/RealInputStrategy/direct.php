<?php
/**
 * Strategy for directly accessing input tree in a 'file' VFS
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jason M. Felice <jason.m.felice@gmail.com>
 * @package VFS_ISO
 */
class VFS_ISOWriter_RealInputStrategy_direct extends VFS_ISOWriter_RealInputStrategy {

    function getRealPath()
    {
        return $this->_sourceVfs->_getNativePath($this->_sourceRoot);
    }

    function finished()
    {
    }

}

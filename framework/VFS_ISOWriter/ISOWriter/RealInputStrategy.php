<?php
/**
 * Encapsulate strategies for getting a real, local filesystem structure from
 * a VFS.
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jason M. Felice <jason.m.felice@gmail.com>
 * @package VFS_ISO
 */
abstract class VFS_ISOWriter_RealInputStrategy {

    /**
     * A reference to the source VFS we want to read.
     *
     * @var VFS
     */
    var $_sourceVfs = null;

    /**
     * Translation provider.
     *
     * @var Horde_Translation
     */
    protected $_dict;

    /**
     * The root directory within the source VFS
     *
     * @var string
     */
    var $_sourceRoot;

    function VFS_ISOWriter_RealInputStrategy(&$sourceVfs, $sourceRoot, $params = array())
    {
        $this->_sourceVfs = &$sourceVfs;
        $this->_sourceRoot = &$sourceRoot;
        if (isset($params['translation'])) {
            $this->_dict = $params['translation'];
        } else {
            $this->_dict = new Horde_Translation_Gettext('VFS_ISOWriter', dirname(__FILE__) . '/../locale');
        }
    }

    /**
     * Get a real path to the input tree.
     *
     * @return mixed    A string with the real path, or PEAR_Error on failure.
     */
    abstract public function getRealPath();

    /**
     * Indicate we are finished with this input strategy.
     *
     * @return mixed        Null or PEAR_Error on failure.
     */
    abstract public function finished();

    /**
     * Decide which strategy to use to get a real FS and create it.
     *
     * @static
     *
     * @param object &$sourceVfs        The VFS we want to read from.
     * @param string $sourceRoot        The root directory in that VFS.
     * @return object   A concrete strategy or PEAR_Error if no strategy is
     *                  available.
     */
    function &factory(&$sourceVfs, $sourceRoot)
    {
        if (strtolower(get_class($sourceVfs)) == 'vfs_file') {
            $method = 'direct';
        } else {
            $method = 'copy';
        }

        include_once dirname(__FILE__) . '/RealInputStrategy/' . $method . '.php';
        $class = 'VFS_ISOWriter_RealInputStrategy_' . $method;
        if (class_exists($class)) {
            $strategy = new $class($sourceVfs, $sourceRoot);
        } else {
            $strategy = PEAR::raiseError(sprintf($this->_dict->t("Could not load strategy \"%s\"."),
                                                 $method));
        }

        return $strategy;
    }

}

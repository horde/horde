<?php
/**
 * Encapsulate strategies for ability to write output to real file.
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jason M. Felice <jason.m.felice@gmail.com>
 * @package VFS_ISO
 */
abstract class VFS_ISOWriter_RealOutputStrategy {

    /**
     * The VFS to which we will write the file.
     *
     * @var VFS
     */
    var $_targetVfs;

    /**
     * Where to store the file in the VFS.
     *
     * @var string
     */
    var $_targetFile;

    /**
     * Constructor
     *
     * @param object &$targetVfs        The VFS to which we will write the
     *                                  file.
     * @param string $targetFile        The path and name of file to write.
     */
    function VFS_ISOWriter_RealOutputStrategy(&$targetVfs, $targetFile)
    {
        $this->_targetVfs = &$targetVfs;
        $this->_targetFile = $targetFile;
    }

    /**
     * Select and create a concrete strategy for using a real output file.
     *
     * @param object &$targetVfs        The VFS to which we will write the
     *                                  result.
     * @param string $targetFile        The path and filename of the target
     *                                  file within the VFS.
     * @return object   A concrete output strategy object or PEAR_Error on
     *                  failure.
     */
    function &factory(&$targetVfs, $targetFile)
    {
        if (strtolower(get_class($targetVfs)) == 'vfs_file') {
            $method = 'direct';
        } else {
            $method = 'copy';
        }

        include_once dirname(__FILE__) . '/RealOutputStrategy/' . $method . '.php';
        $class = 'VFS_ISOWriter_RealOutputStrategy_' . $method;
        if (class_exists($class)) {
            $strategy = new $class($targetVfs, $targetFile);
        } else {
            $strategy = PEAR::raiseError(sprintf(Horde_VFS_ISOWriter_Translation::t("Could not load strategy \"%s\"."),
                                                 $method));
        }

        return $strategy;
    }

    /**
     * Get a real filesystem filename we can write to.
     *
     * @return string   The filename or PEAR_Error on failure.
     */
    abstract public function getRealFilename();

    /**
     * Indicate that we're done writing to the real file.
     *
     * @return mixed    Null or PEAR_Error on failure.
     */
    abstract public function finished();
}

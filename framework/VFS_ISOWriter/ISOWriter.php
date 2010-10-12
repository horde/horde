<?php
/**
 * VFS API for abstracted creation of ISO (CD-ROM) filesystems.
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jason M. Felice <jason.m.felice@gmail.com>
 * @package VFS_ISO
 */
abstract class VFS_ISOWriter
{

    /**
     * A VFS object used for reading the source files
     *
     * @var VFS
     */
    var $_sourceVfs = null;

    /**
     * A VFS object used for writing the ISO image
     *
     * @var VFS
     */
    var $_targetVfs = null;

    /**
     * Hash containing connection parameters.
     *
     * @var array
     */
    var $_params = array();

    /**
     * Translation provider.
     *
     * @var Horde_Translation
     */
    protected $_dict;

    /**
     * Constructs a new VFS_ISOWriter object
     *
     * @param array $params  A hash containing parameters.
     */
    function VFS_ISOWriter(&$sourceVfs, &$targetVfs, $params)
    {
        $this->_sourceVfs = &$sourceVfs;
        $this->_targetVfs = &$targetVfs;
        $this->_params = $params;
        if (isset($params['translation'])) {
            $this->_dict = $params['translation'];
        } else {
            $this->_dict = new Horde_Translation_Gettext('VFS_ISOWriter', dirname(__FILE__) . '/locale');
        }
    }

    /**
     * Create the ISO image
     *
     * @return mixed  Null or PEAR_Error on failure.
     */
    abstract public function process();

    /**
     * Attempt to create a concrete VFS_ISOWriter subclass.
     *
     * This method uses its parameters and checks the system to determine
     * the most appropriate subclass to use for building ISO images.  If
     * none is found, an error is raised.
     *
     * @param object &$sourceVfs      Reference to the VFS object from which
     *                                the files will be read to create the
     *                                ISO image.
     * @param object &$targetVfs      Reference to the VFS object to which the
     *                                ISO image will be written.
     * @param array $params           Hash of parameters for creating the
     *                                image:
     *              'sourceRoot' =>     A directory in the source VFS for
     *                                  files to be read from for the image.
     *              'targetFile' =>     Path and filename of the ISO file to
     *                                  write into the target VFS.
     *
     * @return object                 A newly created concrete VFS_ISOWriter
     *                                subclass, or a PEAR_Error on an error.
     */
    function &factory(&$sourceVfs, &$targetVfs, $params)
    {
        if (empty($params['targetFile'])) {
            return PEAR::raiseError($this->_dict->t("Cannot proceed without 'targetFile' parameter."));
        }
        if (empty($params['sourceRoot'])) {
            $params['sourceRoot'] = '/';
        }

        /* Right now, mkisofs is the only driver, but make sure we can
         * support it. */
        require_once dirname(__FILE__) . '/ISOWriter/mkisofs.php';
        if (VFS_ISOWriter_mkisofs::strategyAvailable()) {
            $isowriter = new VFS_ISOWriter_mkisofs($sourceVfs, $targetVfs,
                                                    $params);
            return $isowriter;
        }

        return PEAR::raiseError($this->_dict->t("No available strategy for making ISO images."));
    }

}

<?php
/**
 * File based temporary storage place for incoming messages.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Filter
 */

/**
 * File based temporary storage place for incoming messages.
 *
 * Copyright 2004-2010 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Filter
 */
class Horde_Kolab_Filter_Temporary_File
implements Horde_Kolab_Filter_Temporary
{
    /**
     * A temporary buffer file for storing the message.
     *
     * @var string
     */
    var $_tmpfile;

    /**
     * The file handle for the temporary file.
     *
     * @var int
     */
    var $_tmpfh;

    /**
     * Configuration.
     *
     * @param Horde_Kolab_Filter_Configuration 
     */
    private $_config;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Filter_Configuration $config The configuration.
     */
    public function __construct(
        Horde_Kolab_Filter_Configuration $config
    ) {
        $this->_config = $config;
    }

    
    /**
     * Creates a buffer for temporary storage of the message.
     *
     * @return NULL
     */
    public function init()
    {
        $conf = $this->_config->getConf();

        if (isset($conf['kolab']['filter']['tempdir'])) {
            $tmpdir = $conf['kolab']['filter']['tempdir'];
        } else {
            $tmpdir = Horde_Util::getTempDir();
        }

        $this->_tmpfile = @tempnam($tmpdir, 'IN.' . get_class($this) . '.');
        $this->_tmpfh = @fopen($this->_tmpfile, 'w');
        if (!$this->_tmpfh) {
            throw new Horde_Kolab_Filter_Exception_IoError(
                sprintf(
                    "Error: Could not open %s for writing: %s",
                    $this->_tmpfile,
                    $php_errormsg
                )
            );
        }

        register_shutdown_function(array($this, 'cleanup'));
    }

    /**
     * Return the file handle for writing data.
     *
     * @return resource The file handle.
     */
    public function getHandle()
    {
        return $this->_tmpfh;
    }

    /**
     * Return the file handle for reading data.
     *
     * @return resource The file handle.
     */
    public function getReadHandle()
    {
        return @fopen($this->_tmpfile, 'r');;
    }

    /**
     * A shutdown function for removing the temporary file.
     *
     * @return NULL
     */
    public function cleanup()
    {
        if (@file_exists($this->_tmpfile)) {
            @unlink($this->_tmpfile);
        }
    }
}
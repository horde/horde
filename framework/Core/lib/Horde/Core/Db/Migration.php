<?php
/**
 * Horde_Core_Db_Migration provides a wrapper for all migration scripts
 * distributed through Horde applications or libraries.
 *
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Core
 */

/**
 * @author  Jan Schneider <jan@horde.org>
 * @package Core
 */
class Horde_Core_Db_Migration
{
    /**
     * List of all migration directories.
     *
     * @var array
     */
    public $dirs = array();

    /**
     * List of all module names matching the directories in $dirs.
     *
     * @var array
     */
    public $apps = array();

    /**
     * List of all lower case module names matching the directories in $dirs.
     *
     * @var array
     */
    protected $_lower = array();

    /**
     * Constructor.
     *
     * Searches all installed applications and libraries for migration
     * directories and builds lists of migrateable modules and directories.
     *
     * @param string $basedir   Base directory of a Git checkout. If provided
     *                          a framework/ sub directory is searched for
     *                          migration scripts too.
     * @param string $pearconf  Path to a PEAR configuration file.
     */
    public function __construct($basedir = null, $pearconf = null)
    {
        // Loop through all applications.
        foreach ($GLOBALS['registry']->listAllApps() as $app) {
            $dir = $GLOBALS['registry']->get('fileroot', $app) . '/migration';
            if (is_dir($dir)) {
                $this->apps[] = $app;
                $this->_lower[] = Horde_String::lower($app);
                $this->dirs[] = realpath($dir);
            }
        }

        // Silence PEAR errors.
        $old_error_reporting = error_reporting();
        error_reporting($old_error_reporting & ~E_DEPRECATED);
        $pear = new PEAR_Config($pearconf);

        // Loop through local framework checkout.
        if ($basedir) {
            $packageFile = new PEAR_PackageFile($pear);
            foreach (glob($basedir . '/framework/*/migration') as $dir) {
                $package = $packageFile->fromPackageFile(
                    dirname($dir) . '/package.xml', PEAR_VALIDATE_NORMAL
                );
                if ($package instanceof PEAR_Error) {
                    Horde::log($package->getMessage(), Horde_Log::ERR);
                    continue;
                }
                $this->apps[] = $package->getName();
                $this->_lower[] = Horde_String::lower($package->getName());
                $this->dirs[] = realpath($dir);
            }
        }

        // Loop through installed PEAR packages.
        $registry = $pear->getRegistry();
        foreach (glob($pear->get('data_dir') . '/*/migration') as $dir) {
            $package = $registry->getPackage(
                basename(dirname($dir)), 'pear.horde.org');
            if ($package == false) {
                Horde::log("Ignoring package in directory $dir", Horde_Log::WARN);
                continue;
            }

            $app = $package->getName();
            if (!in_array($app, $this->apps)) {
                $this->apps[] = $app;
                $this->_lower[] = Horde_String::lower($app);
                $this->dirs[] = realpath($dir);
            }
        }
        error_reporting($old_error_reporting);
    }

    /**
     * Returns a migrator for a module.
     *
     * @param string $app               An application or library name.
     * @param Horde_Log_Logger $logger  A logger instance.
     *
     * @return Horde_Db_Migration_Migrator  A migrator for the specified module.
     */
    public function getMigrator($app, Horde_Log_Logger $logger = null)
    {
        $app = Horde_String::lower($app);
        $db = $GLOBALS['injector']->getInstance('Horde_Db_Adapter');
        return new Horde_Db_Migration_Migrator(
            $db,
            $logger,
            array(
                'migrationsPath' => $this->dirs[array_search($app, $this->_lower)],
                'schemaTableName' => $db->tableAliasFor($app . '_schema_info'))
            );
    }
}

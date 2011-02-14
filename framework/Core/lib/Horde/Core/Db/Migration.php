<?php
/**
 * Horde_Core_Db_Migration provides a wrapper for all migration scripts
 * distributed through Horde applications or libraries.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
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
     * Constructor.
     *
     * Searches all installed applications and libraries for migration
     * directories and builds lists of migrateable modules and directories.
     *
     * @param string $basedir  Base directory of a Git checkout. If provided
     *                         a framework/ sub directory is searched for
     *                         migration scripts too.
     */
    public function __construct($basedir = null)
    {
        // Loop through all applications.
        foreach ($GLOBALS['registry']->listApps(array('hidden', 'notoolbar', 'admin', 'active'), false, null) as $app) {
            $dir = $GLOBALS['registry']->get('fileroot', $app) . '/migration';
            if (is_dir($dir)) {
                $this->apps[] = $app;
                $this->dirs[] = $dir;
            }
        }

        // Loop through local framework checkout.
        if ($basedir) {
            foreach (glob($basedir . '/framework/*/migration') as $dir) {
                $this->apps[] = 'horde_' . Horde_String::lower(basename(dirname($dir)));
                $this->dirs[] = $dir;
            }
        }

        // Loop through installed PEAR packages.
        $pear = new PEAR_Config();
        foreach (glob($pear->get('data_dir') . '/*/migration') as $dir) {
            $app = 'horde_' . Horde_String::lower(basename(dirname($dir)));;
            if (!in_array($app, $apps)) {
                $this->apps[] = $app;
                $this->dirs[] = $dir;
            }
        }
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
        return new Horde_Db_Migration_Migrator(
            $GLOBALS['injector']->getInstance('Horde_Db_Adapter'),
            $logger,
            array('migrationsPath' => $this->dirs[array_search($app, $this->apps)],
                  'schemaTableName' => $app . '_schema_info'));
    }
}

<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2006-2009 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 * @subpackage Migration
 */

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 * @subpackage Migration
 */
class Horde_Db_Migration_Migrator
{
    /**
     * @var string
     */
    protected $_direction = null;

    /**
     * @var string
     */
    protected $_migrationsPath = null;

    /**
     * @var int
     */
    protected $_targetVersion = null;


    /*##########################################################################
    # Constructor
    ##########################################################################*/

    /**
     * @param   string  $direction
     * @param   string  $migrationsPath
     * @param   int     $targetVersion
     */
    public function __construct($connection, $migrationsPath)
    {
        if (!$connection->supportsMigrations()) {
            $msg = 'This database does not yet support migrations';
            throw new Horde_Db_Migration_Exception($msg);
        }

        $this->_connection     = $connection;
        $this->_migrationsPath = $migrationsPath;
        /* @TODO */
        //$this->_logger         = $logger;
        //$this->_inflector      = $inflector;
        $this->_logger         = new Horde_Support_Stub();
        $this->_inflector      = new Horde_Support_Inflector();

        $this->_connection->initializeSchemaInformation();
    }


    /*##########################################################################
    # Public
    ##########################################################################*/

    /**
     * @param   string            $targetVersion
     */
    public function migrate($targetVersion = null)
    {
        $currentVersion = $this->getCurrentVersion();

        if ($targetVersion == null || $currentVersion < $targetVersion) {
            $this->up($targetVersion);

        // migrate down
        } elseif ($currentVersion > $targetVersion) {
            $this->down($targetVersion);

        // You're on the right version
        } elseif ($currentVersion == $targetVersion) {
            return;
        }
    }

    /**
     * @param   string  $targetVersion
     */
    public function up($targetVersion = null)
    {
        $this->_targetVersion = $targetVersion;
        $this->_direction = 'up';
        $this->_doMigrate();
    }

    /**
     * @param   string  $targetVersion
     */
    public function down($targetVersion = null)
    {
        $this->_targetVersion = $targetVersion;
        $this->_direction = 'down';
        $this->_doMigrate();
    }

    /**
     * @return  int
     */
    public function getCurrentVersion()
    {
        $sql = 'SELECT version FROM schema_info';
        return $this->_connection->selectValue($sql);
    }


    /*##########################################################################
    # Protected
    ##########################################################################*/

    /**
     * Perform migration
     */
    protected function _doMigrate()
    {
        foreach ($this->_getMigrationClasses() as $migration) {
            if ($this->_hasReachedTargetVersion($migration->version)) {
                $msg = "Reached target version: $this->_targetVersion";
                $this->_logger->info($msg);
                return;
            }
            if ($this->_isIrrelevantMigration($migration->version)) { continue; }

            // log
            $msg = "Migrating to ".get_class($migration)." (".$migration->version.")";
            $this->_logger->info($msg);

            // migrate
            $migration->migrate($this->_direction);
            $this->_setSchemaVersion($migration->version);
        }
    }

    /**
     * @return  array
     */
    protected function _getMigrationClasses()
    {
        $migrations = array();
        foreach ($this->_getMigrationFiles() as $migrationFile) {
            require_once $migrationFile;
            list($version, $name) = $this->_getMigrationVersionAndName($migrationFile);
            $this->_assertUniqueMigrationVersion($migrations, $version);
            $migrations[$version] = $this->_getMigrationClass($name, $version);
        }

        // sort by version
        ksort($migrations);
        $sorted = array_values($migrations);
        return $this->_isDown() ? array_reverse($sorted) : $sorted;
    }

    /**
     * @param   array   $migrations
     * @param   integer $version
     */
    protected function _assertUniqueMigrationVersion($migrations, $version)
    {
        if (isset($migrations[$version])) {
            $msg = "Multiple migrations have the version number $version";
            throw new Horde_Db_Migration_Exception($msg);
        }
    }

    /**
     * Get the list of migration files
     * @return  array
     */
    protected function _getMigrationFiles()
    {
        $files = glob("$this->_migrationsPath/[0-9]*_*.php");
        return $this->_isDown() ? array_reverse($files) : $files;
    }

    /**
     * Actually return object, and not class
     *
     * @param   string  $migrationName
     * @param   int     $version
     * @return  Horde_Db_Migration_Base
     */
    protected function _getMigrationClass($migrationName, $version)
    {
        $className = $this->_inflector->camelize($migrationName);
        return new $className($this->_connection, $version);
    }

    /**
     * @param   string  $migrationFile
     * @return  array   ($version, $name)
     */
    protected function _getMigrationVersionAndName($migrationFile)
    {
        preg_match_all('/([0-9]+)_([_a-z0-9]*).php/', $migrationFile, $matches);
        return array($matches[1][0], $matches[2][0]);
    }

    /**
     * @param   integer $version
     */
    protected function _setSchemaVersion($version)
    {
        $version = $this->_isDown() ? $version - 1 : $version;
        $sql = "UPDATE schema_info SET version = " . (int)$version;
        $this->_connection->update($sql);
    }

    /**
     * @return  boolean
     */
    protected function _isUp()
    {
        return $this->_direction == 'up';
    }

    /**
     * @return  boolean
     */
    protected function _isDown()
    {
        return $this->_direction == 'down';
    }

    /**
     * @return  boolean
     */
    protected function _hasReachedTargetVersion($version)
    {
        if ($this->_targetVersion === null) { return false; }

        return ($this->_isUp()   && $version-1 >= $this->_targetVersion) ||
               ($this->_isDown() && $version   <= $this->_targetVersion);
    }

    /**
     * @param   integer $version
     * @return  boolean
     */
    protected function _isIrrelevantMigration($version)
    {
        return ($this->_isUp()   && $version <= self::getCurrentVersion()) ||
               ($this->_isDown() && $version >  self::getCurrentVersion());
    }
}

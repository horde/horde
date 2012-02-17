<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2006-2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
 * @subpackage Migration
 */

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    Db
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
     * @var integer
     */
    protected $_targetVersion = null;

    /**
     * @var string
     */
    protected $_schemaTableName = 'schema_info';

    /**
     * Constructor.
     *
     * @param Horde_Db_Adapter $connection  A DB connection object.
     * @param Horde_Log_Logger $logger      A logger object.
     * @param array $options                Additional options for the migrator:
     *                                      - migrationsPath: directory with the
     *                                        migration files.
     *                                      - schemaTableName: table for storing
     *                                        the schema version.
     *
     * @throws Horde_Db_Migration_Exception
     */
    public function __construct(Horde_Db_Adapter $connection,
                                Horde_Log_Logger $logger = null,
                                array $options = array())
    {
        if (!$connection->supportsMigrations()) {
            throw new Horde_Db_Migration_Exception('This database does not yet support migrations');
        }

        $this->_connection = $connection;
        $this->_logger = $logger ? $logger : new Horde_Support_Stub();
        $this->_inflector = new Horde_Support_Inflector();
        if (isset($options['migrationsPath'])) {
            $this->_migrationsPath = $options['migrationsPath'];
        }
        if (isset($options['schemaTableName'])) {
            $this->_schemaTableName = $options['schemaTableName'];
        }

        $this->_initializeSchemaInformation();
    }

    /**
     * @param string $targetVersion
     */
    public function migrate($targetVersion = null)
    {
        $currentVersion = $this->getCurrentVersion();

        if ($targetVersion == null || $currentVersion < $targetVersion) {
            $this->up($targetVersion);
        } elseif ($currentVersion > $targetVersion) {
            // migrate down
            $this->down($targetVersion);
        }
    }

    /**
     * @param string $targetVersion
     */
    public function up($targetVersion = null)
    {
        $this->_targetVersion = $targetVersion;
        $this->_direction = 'up';
        $this->_doMigrate();
    }

    /**
     * @param string $targetVersion
     */
    public function down($targetVersion = null)
    {
        $this->_targetVersion = $targetVersion;
        $this->_direction = 'down';
        $this->_doMigrate();
    }

    /**
     * @return integer
     */
    public function getCurrentVersion()
    {
        return in_array($this->_schemaTableName, $this->_connection->tables())
            ? $this->_connection->selectValue('SELECT version FROM ' . $this->_schemaTableName)
            : 0;
    }

    /**
     * @return integer
     */
    public function getTargetVersion()
    {
        $migrations = array();
        foreach ($this->_getMigrationFiles() as $migrationFile) {
            list($version, $name) = $this->_getMigrationVersionAndName($migrationFile);
            $this->_assertUniqueMigrationVersion($migrations, $version);
            $migrations[$version] = $name;
        }

        // Sort by version.
        uksort($migrations, 'strnatcmp');

        return key(array_reverse($migrations, true));
    }

    /**
     * @param string $migrationsPath  Path to migration files.
     */
    public function setMigrationsPath($migrationsPath)
    {
        $this->_migrationsPath = $migrationsPath;
    }

    /**
     * @param Horde_Log_Logger $logger
     */
    public function setLogger(Horde_Log_Logger $logger)
    {
        $this->_logger = $logger;
    }

    /**
     * @param Horde_Support_Inflector $inflector
     */
    public function setInflector(Horde_Support_Inflector $inflector)
    {
        $this->_inflector = $inflector;
    }

    /**
     * Performs the migration.
     */
    protected function _doMigrate()
    {
        foreach ($this->_getMigrationClasses() as $migration) {
            if ($this->_hasReachedTargetVersion($migration->version)) {
                $this->_logger->info('Reached target version: ' . $this->_targetVersion);
                return;
            }
            if ($this->_isIrrelevantMigration($migration->version)) {
                continue;
            }

            $this->_logger->info('Migrating ' . ($this->_direction == 'up' ? 'to ' : 'from ') . get_class($migration) . ' (' . $migration->version . ')');
            $migration->migrate($this->_direction);
            $this->_setSchemaVersion($migration->version);
        }
    }

    /**
     * @return array
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

        // Sort by version.
        uksort($migrations, 'strnatcmp');
        $sorted = array_values($migrations);

        return $this->_isDown() ? array_reverse($sorted) : $sorted;
    }

    /**
     * @param array   $migrations
     * @param integer $version
     *
     * @throws Horde_Db_Migration_Exception
     */
    protected function _assertUniqueMigrationVersion($migrations, $version)
    {
        if (isset($migrations[$version])) {
            throw new Horde_Db_Migration_Exception('Multiple migrations have the version number ' . $version);
        }
    }

    /**
     * Returns the list of migration files.
     *
     * @return array
     */
    protected function _getMigrationFiles()
    {
        return array_keys(
            iterator_to_array(
                new RegexIterator(
                    new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator(
                            $this->_migrationsPath
                        )
                    ),
                    '/' . preg_quote(DIRECTORY_SEPARATOR, '/') . '\d+_.*\.php$/',
                    RecursiveRegexIterator::MATCH,
                    RegexIterator::USE_KEY
                )
            )
        );
    }

    /**
     * Actually returns object, and not class.
     *
     * @param string  $migrationName
     * @param integer $version
     *
     * @return  Horde_Db_Migration_Base
     */
    protected function _getMigrationClass($migrationName, $version)
    {
        $className = $this->_inflector->camelize($migrationName);
        $class = new $className($this->_connection, $version);
        $class->setLogger($this->_logger);

        return $class;
    }

    /**
     * @param string $migrationFile
     *
     * @return array  ($version, $name)
     */
    protected function _getMigrationVersionAndName($migrationFile)
    {
        preg_match_all('/([0-9]+)_([_a-z0-9]*).php/', $migrationFile, $matches);
        return array($matches[1][0], $matches[2][0]);
    }

    /**
     * @TODO
     */
    protected function _initializeSchemaInformation()
    {
        if (in_array($this->_schemaTableName, $this->_connection->tables())) {
            return;
        }
        $schemaTable = $this->_connection->createTable($this->_schemaTableName, array('autoincrementKey' => false));
        $schemaTable->column('version', 'integer');
        $schemaTable->end();
        $this->_connection->insert('INSERT INTO ' . $this->_schemaTableName . ' (version) VALUES (0)');
    }

    /**
     * @param integer $version
     */
    protected function _setSchemaVersion($version)
    {
        $version = $this->_isDown() ? $version - 1 : $version;
        if ($version) {
            $sql = 'UPDATE ' . $this->_schemaTableName . ' SET version = ' . (int)$version;
            $this->_connection->update($sql);
        } else {
            $this->_connection->dropTable($this->_schemaTableName);
        }
    }

    /**
     * @return boolean
     */
    protected function _isUp()
    {
        return $this->_direction == 'up';
    }

    /**
     * @return boolean
     */
    protected function _isDown()
    {
        return $this->_direction == 'down';
    }

    /**
     * @return boolean
     */
    protected function _hasReachedTargetVersion($version)
    {
        if ($this->_targetVersion === null) {
            return false;
        }

        return ($this->_isUp()   && $version - 1 >= $this->_targetVersion) ||
               ($this->_isDown() && $version     <= $this->_targetVersion);
    }

    /**
     * @param integer $version
     *
     * @return  boolean
     */
    protected function _isIrrelevantMigration($version)
    {
        return ($this->_isUp()   && $version <= self::getCurrentVersion()) ||
               ($this->_isDown() && $version >  self::getCurrentVersion());
    }
}

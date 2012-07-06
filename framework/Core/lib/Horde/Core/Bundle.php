<?php
/**
 * Base class for the Horde bundle API.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Core
 */
abstract class Horde_Core_Bundle
{
    /**
     * @var Horde_Core_Cli
     */
    protected $_cli;

    /**
     * @var Horde_Config
     */
    protected $_config;

    /**
     * Path to the PEAR configuration file.
     *
     * @var string
     */
    protected $_pearconf;

    /**
     * Constructor.
     */
    public function __construct(Horde_Core_Cli $cli, $pearconf = null)
    {
        $this->_cli = $cli;
        $this->_pearconf = $pearconf;
    }

    /**
     * Creates and loads a basic conf.php configuration file.
     */
    public function init()
    {
        // Check if conf.php is writeable.
        if ((file_exists(HORDE_BASE . '/config/conf.php') &&
             !is_writable(HORDE_BASE . '/config/conf.php')) ||
            !is_writable(HORDE_BASE . '/config')) {
            $this->_cli->message(Horde_Util::realPath(HORDE_BASE . '/config/conf.php') . ' is not writable.', 'cli.error');
        }

        // We need a valid conf.php to instantiate the registry.
        if (!file_exists(HORDE_BASE . '/config/conf.php')) {
            copy(HORDE_BASE . '/config/conf.php.dist', HORDE_BASE . '/config/conf.php');
        }

        // Initialization
        $umask = umask();
        Horde_Registry::appInit('horde', array('nocompress' => true, 'authentication' => 'none'));
        $this->_config = new Horde_Config();
        umask($umask);
    }

    /**
     * Asks for the database settings and creates the SQL configuration.
     */
    public function configDb()
    {
        $this->_cli->writeln();
        $this->_cli->writeln($this->_cli->bold('Configuring database settings'));
        $this->_cli->writeln();

        $sql_config = $this->_config->configSQL('');
        $vars = new Horde_Variables();
        new Horde_Config_Form($vars, 'horde', true);
        $this->_cli->question(
            $vars,
            'sql',
            'phptype',
            $sql_config['switch']['custom']['fields']['phptype']);

        $this->writeConfig($vars);
    }

    /**
     * Creates or updates the database tables.
     *
     * @throws Horde_Db_Exception
     */
    public function migrateDb()
    {
        $this->_cli->writeln();
        echo 'Creating and updating database tables...';

        $migration = new Horde_Core_Db_Migration(null, $this->_pearconf);

        // Try twice to work around unresolved migration dependencies.
        for ($i = 0; $i < 2; $i++) {
            $error = null;
            foreach ($migration->apps as $app) {
                $migrator = $migration->getMigrator($app);
                if ($migrator->getTargetVersion() <= $migrator->getCurrentVersion()) {
                    continue;
                }
                try {
                    $migrator->up();
                } catch (Exception $error) {
                    if ($i) {
                        throw $error;
                    }
                }
            }
            if (!$error) {
                break;
            }
        }

        $this->_cli->writeln($this->_cli->green(' done.'));
    }

    /**
     * Asks for the administrator settings and creates the authentication
     * configuration.
     */
    public function configAuth()
    {
        $vars = new Horde_Variables();
        new Horde_Config_Form($vars, 'horde', true);
        $this->_cli->writeln();
        $this->_cli->writeln($this->_cli->bold('Configuring administrator settings'));
        $admin_user = $this->_configAuth($vars);
        $vars->auth__admins = $admin_user;
        $this->writeConfig($vars);
    }

    /**
     * Asks for the administrator settings.
     */
    abstract protected function _configAuth(Horde_Variables $vars);

    /**
     * Writes the current configuration to the conf.php file.
     *
     * @throws Horde_Exception
     */
    public function writeConfig(Horde_Variables $vars)
    {
        $this->_cli->writeln();
        echo 'Writing main configuration file...';

        $php_config = $this->_config->generatePHPConfig($vars, $GLOBALS['conf']);
        $configFile = $this->_config->configFile();
        $fp = fopen($configFile, 'w');
        if (!$fp) {
            throw new Horde_Exception('Cannot write configuration file ' . Horde_Util::realPath($configFile));
        }
        fwrite($fp, $php_config);
        fclose($fp);

        // Reload configuration.
        include $configFile;
        $GLOBALS['conf'] = $conf;

        $this->_cli->writeln($this->_cli->green(' done.'));
    }

    /**
     * Creates default configuration files for all installed applications.
     *
     * @throws Horde_Exception
     */
    public function writeAllConfigs()
    {
        foreach ($GLOBALS['registry']->listAllApps() as $app) {
            if ($app == 'horde' ||
                !file_exists($GLOBALS['registry']->get('fileroot', $app) . '/config/conf.xml')) {
                continue;
            }
            $config = new Horde_Config($app);
            $configFile = $config->configFile();
            if (!$config->writePHPConfig(new Horde_Variables())) {
                throw new Horde_Exception('Cannot write configuration file ' . Horde_Util::realPath($configFile));
            }
        }
    }
}

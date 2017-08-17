<?php
/**
 * Copyright 2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
 */

use Horde_Cli as Cli;
use Horde\Cli\Application;
use Horde\Backup;
use Horde\Backup\Reader;
use Horde\Backup\Writer;

/**
 * Command line application for horde-backup script.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl LGPL-2
 * @package   Horde
 */
class Horde_Cli_Backup extends Application
{
    /**
     * The Horde registry.
     *
     * @var Horde_Registry
     */
    protected $_registry;

    /**
     * Constructor.
     *
     * @param Horde_Cli $cli            A Horde_Cli instance.
     * @param Horde_Registry $registry  The Horde registry.
     */
    public function __construct(Cli $cli, Horde_Registry $registry)
    {
        $this->_registry = $registry;

        parent::__construct(
            $cli,
            array(
                'description' => _("Horde backup and restore tool"),
                'usage' => '%prog [-a|--app=APP ...] [-u|--user=USER ...] -d|--dir=DIR --backup|--restore [-c|--clear]',
            )
        );

        $this->addOption(
            '--backup',
            array(
                'action' => 'store_const',
                'dest' => 'action',
                'const' => 'backup',
                'help' => _("Backup user data."),
            )
        );
        $this->addOption(
            '--restore',
            array(
                'action' => 'store_const',
                'dest' => 'action',
                'const' => 'restore',
                'help' => _("Restore user data."),
            )
        );
        $this->addOption(
            '--list',
            array(
                'action' => 'store_const',
                'dest' => 'action',
                'const' => 'list',
                'help' => _("List backups."),
            )
        );
        $this->addOption(
            '-d', '--dir',
            array(
                'help' => _("Target directory for backups."),
            )
        );
        $this->addOption(
            '-a', '--app',
            array(
                'action' => 'append',
                'help' => _("List of applications to backup/restore."),
            )
        );
        $this->addOption(
            '-u', '--user',
            array(
                'action' => 'append',
                'help' => _("List of users to backup/restore."),
            )
        );
        $this->addOption(
            '-c', '--clear',
            array(
                'action' => 'store_true',
                'help' => _("Clear all existing user data before restoring a user?"),
            )
        );
    }

    /**
     * Excecutes the actual application logic.
     */
    protected function _doRun()
    {
        $this->_checkArguments();
        switch ($this->values->action) {
        case 'backup':
            $this->_backup(
                $this->values->dir,
                $this->values->app ?: array(),
                $this->values->user ?: array()
            );
            break;
        case 'restore':
            $this->_restore(
                $this->values->dir,
                $this->values->app ?: array(),
                $this->values->user ?: array(),
                $this->values->clear
            );
            break;
        case 'list':
            $this->_list($this->values->dir);
            break;
        }
    }

    /**
     * Creates the backups.
     *
     * @param string $directory      Backup directory.
     * @param string[] $application  Application names.
     * @param string[] $users        User names.
     */
    protected function _backup($dir, $apps, $users)
    {
        $writer = new Writer($dir);
        foreach ($this->_registry->listApps() as $app) {
            if ($apps && !in_array($app, $apps)) {
                continue;
            }
            $writer->backup(
                $app,
                $this->_registry->callAppMethod(
                    $app, 'backup', array('args' => array($users))
                )
            );
        }
        $count = $writer->save();
        $this->message(
            sprintf(_("Saved %d items."), $count),
            'cli.success'
        );
    }

    /**
     * Restores the backups.
     *
     * @param string $directory      Backup directory.
     * @param string[] $application  Application names.
     * @param string[] $users        User names.
     * @param boolean $clear         Clear user data before restore?
     */
    protected function _restore($dir, $apps, $users, $clear)
    {
        global $registry;

        $reader = new Reader($dir);
        $cleared = $resolved = array();
        $continue = false;
        while (true) {
            foreach ($reader->restore($apps, $users) as $app => $collections) {
                $deps = $this->_registry->callAppMethod(
                    $app, 'restoreDependencies'
                );
                foreach ($collections as $collection) {
                    $type = $collection->getType();
                    if (in_array($type, $resolved)) {
                        continue;
                    }
                    if (isset($deps[$type]) &&
                        array_diff($deps[$type], $resolved)) {
                        $continue = true;
                        continue;
                    }
                    $resolved[] = $type;
                    $user = $collection->getUser();
                    if ($clear && !isset($cleared[$user])) {
                        try {
                            $this->_registry->removeUserData($user, $app);
                            $this->message(
                                sprintf(_("Removed user data from %s."), $type),
                                'cli.success'
                            );
                        } catch (Horde_Exception $e) {
                            $this->message($e->getMessage(), 'cli.warning');
                        }
                        $cleared[$user] = true;
                    }
                    $count = $this->_registry->callAppMethod(
                        $app, 'restore', array('args' => array($collection))
                    );
                    $this->message(
                        sprintf(_("Restored %d items from %s."), $count, $type),
                        'cli.success'
                    );
                }
            }
            if (!$continue) {
                break;
            }
            $continue = false;
        }
    }

    /**
     * Lists the backups.
     *
     * @param string $directory      Backup directory.
     */
    protected function _list($dir)
    {
        $reader = new Reader($dir);
        $this->header(_("Existings backups"));
        $count = 0;
        foreach ($reader->listBackups() as $file) {
            $this->writeln($file);
            $count++;
        }
        $this->writeln(sprintf(_("%d backups"), $count));
    }

    /**
     * Checks that all required arguments are set and correct.
     */
    protected function _checkArguments()
    {
        if (empty($this->values->action)) {
            $this->parserError(
                _("You must specify either --backup, --restore, or --list")
            );
        }
        if (empty($this->values->dir)) {
            $this->parserError(
                _("You must specify a target directory with --dir")
            );
        }
        if (!file_exists($this->values->dir)) {
            $this->parserError(
                sprintf(_("%s doesn't exist"), $this->values->dir)
            );
        }
        if (!is_dir($this->values->dir)) {
            $this->parserError(
                sprintf(_("%s is not a directory"), $this->values->dir)
            );
        }
        if (!is_readable($this->values->dir)) {
            $this->parserError(
                sprintf(_("%s is not readable"), $this->values->dir)
            );
        }
        if (!is_writable($this->values->dir)) {
            $this->parserError(
                sprintf(_("%s is not writable"), $this->values->dir)
            );
        }
    }
}

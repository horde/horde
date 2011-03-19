<?php
/**
 * Components_Runner_Change:: adds a new change log entry.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Components_Runner_Change:: adds a new change log entry.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */
class Components_Runner_Change
{
    /** Path to the CHANGES file. */
    const CHANGES = '/docs/CHANGES';

    /**
     * The configuration for the current job.
     *
     * @var Components_Config
     */
    private $_config;

    /**
     * The factory for PEAR dependencies.
     *
     * @var Components_Pear_Factory
     */
    private $_factory;

    /**
     * The output handler.
     *
     * @param Component_Output
     */
    private $_output;

    /**
     * Constructor.
     *
     * @param Components_Config       $config  The configuration for the current
     *                                         job.
     * @param Components_Pear_Factory $factory The factory for PEAR
     *                                         dependencies.
     * @param Component_Output        $output  The output handler.
     */
    public function __construct(
        Components_Config $config,
        Components_Pear_Factory $factory,
        Components_Output $output
    ) {
        $this->_config = $config;
        $this->_factory = $factory;
        $this->_output = $output;
    }

    public function run()
    {
        $options = $this->_config->getOptions();

        $package_xml = $this->_config->getComponentPackageXml();
        if (!isset($options['pearrc'])) {
            $package = $this->_factory->createPackageForDefaultLocation(
                $package_xml
            );
        } else {
            $package = $this->_factory->createPackageForInstallLocation(
                $package_xml,
                $options['pearrc']
            );
        }

        $arguments = $this->_config->getArguments();
        if (count($arguments) > 1 && $arguments[0] == 'changed') {
            $options['changed'] = $arguments[1];
        }

        if (empty($options['pretend'])) {
            $package->addNote($options['changed']);
        } else {
            $this->_output->info(
                sprintf(
                    'Would add change log entry to %s now.',
                    $package->getPackageXml()
                )
            );
        }

        $changes = false;
        if ($changes = $this->changesFileExists($package->getComponentDirectory())) {
            if (empty($options['pretend'])) {
                $this->addChange($options['changed'], $changes);
                $this->_output->ok(
                    sprintf(
                        'Added new note to %s.',
                        $changes
                    )
                );
            } else {
                $this->_output->info(
                    sprintf(
                        'Would add change log entry to %s now.',
                        $changes
                    )
                );
            }
        }

        if (!empty($options['commit'])) {
            $this->systemInDirectory(
                'git add ' . $package->getPackageXml(),
                $package->getComponentDirectory(),
                $options
            );
            if ($changes) {
                $this->systemInDirectory(
                    'git add ' . $changes,
                    $package->getComponentDirectory(),
                    $options
                );
            }
            $this->systemInDirectory(
                'git commit -m "' . $options['changed'] . '"',
                $package->getComponentDirectory(),
                $options
            );
        }
    }

    /**
     * Run a system call.
     *
     * @param string $call       The system call to execute.
     * @param string $target_dir Run the command in the provided target path.
     * @param array  $options    Additional options.
     *
     * @return string The command output.
     */
    protected function systemInDirectory($call, $target_dir, $options)
    {
        $old_dir = getcwd();
        chdir($target_dir);
        $result = $this->system($call, $options);
        chdir($old_dir);
        return $result;
    }

    /**
     * Run a system call.
     *
     * @param string $call    The system call to execute.
     * @param array  $options Additional options.
     *
     * @return string The command output.
     */
    protected function system($call, $options)
    {
        if (empty($options['pretend'])) {
            //@todo Error handling
            return system($call);
        } else {
            $this->_output->info(sprintf('Would run "%s" now.', $call));
        }
    }

    /**
     * Indicates if there is a CHANGES file for this component.
     *
     * @param string $dir The basic component directory.
     *
     * @return string|boolean The path to the CHANGES file if it exists, false
     *                        otherwise.
     */
    public function changesFileExists($dir)
    {
        $changes = $dir . self::CHANGES;
        if (file_exists($changes)) {
            return $changes;
        }
        return false;
    }

    /**
     * Add a change log entry to CHANGES
     *
     * @param string $entry   Change log entry to add.
     * @param string $changes Path to the CHANGES file.
     *
     * @return NULL
     */
    public function addChange($entry, $changes)
    {
        $tmp = Horde_Util::getTempFile();

        $oldfp = fopen($changes, 'r');
        $newfp = fopen($tmp, 'w');
        $counter = 0;
        while ($line = fgets($oldfp)) {
            if ($counter == 4) {
                fwrite($newfp, $entry . "\n");
            }
            $counter++;
            fwrite($newfp, $line);
        }
        fclose($oldfp);
        fclose($newfp);
        system("mv -f $tmp $changes");
    }

}

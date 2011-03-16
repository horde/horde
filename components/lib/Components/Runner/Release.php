<?php
/**
 * Components_Runner_Release:: releases a new version for a package.
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
 * Components_Runner_Release:: releases a new version for a package.
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
class Components_Runner_Release
{
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
     * The release tasks handler.
     *
     * @param Component_Release_Tasks
     */
    private $_release;

    /**
     * Populated when the RELEASE_NOTES file is included.
     * Should probably be refactored to use a setter for each
     * property the RELEASE_NOTES file sets...
     *
     * @var array
     */
    public $notes = array();

    /**
     * Constructor.
     *
     * @param Components_Config       $config  The configuration for the current
     *                                         job.
     * @param Components_Pear_Factory $factory The factory for PEAR
     *                                         dependencies.
     * @param Component_Output        $output  The output handler.
     * @param Component_Release_Tasks $release The tasks handler.
     */
    public function __construct(
        Components_Config $config,
        Components_Pear_Factory $factory,
        Components_Output $output,
        Components_Release_Tasks $release
    ) {
        $this->_config = $config;
        $this->_factory = $factory;
        $this->_output = $output;
        $this->_release = $release;
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

        $this->_loadNotes(dirname($package_xml) . '/docs');

        $sequence = array();

        if ($this->_doTask('timestamp')) {
            $sequence[] = 'Timestamp';
        }

        if ($this->_doTask('sentinel')) {
            $sequence[] = 'CurrentSentinel';
        }

        if ($this->_doTask('commit')) {
            $sequence[] = 'CommitPreRelease';
        }

        if (!empty($sequence)) {
            $this->_release->run($sequence, $package, $options);
        } else {
            $this->_output->warn('Huh?! No tasks selected... All done!');
        }

        if ($this->_doTask('package')) {
            $path = $package->generateRelease();
            if ($this->_doTask('upload')) {
                print system('scp ' . $path . ' ' . $options['releaseserver'] . ':~/');
                print system('ssh '. $options['releaseserver'] . ' "pirum add ' . $options['releasedir'] . ' ~/' . basename($path) . ' && rm ' . basename($path) . '"') . "\n";
                unlink($path);
            }

        }

        $release = $package->getName() . '-' . $package->getVersion();

        if ($this->_doTask('announce')) {
            if (!class_exists('Horde_Release')) {
                throw new Components_Exception('The release package is missing!');
            }
            $mailer = new Horde_Release_MailingList(
                $package->getName(),
                isset($this->notes['name']) ? $this->notes['name'] : $package->name(),
                $this->notes['branch'],
                $options['from'],
                isset($this->notes['list']) ? $this->notes['list'] : null,
                Components_Helper_Version::pearToHorde($package->getVersion()),
                $this->notes['tag_list']
            );
            if (isset($this->notes['ml']['changes'])) {
                $mailer->append($this->notes['ml']['changes']);
            }

            if ($this->_doTask('send')) {
                $class = 'Horde_Mail_Transport_' . ucfirst($this->_options['mailer']['type']);
                $mailer->getMail()->send(new $class($this->_options['mailer']['params']));
            } else {
                print "Message headers:\n";
                print_r($mailer->getHeaders());
                print "Message body:\n" . $mailer->getBody() . "\n";
            }
        }

        if ($options['next']) {
            if ($this->_doTask('sentinel')) {
                if (!class_exists('Horde_Release')) {
                    throw new Components_Exception('The release package is missing!');
                }
                $sentinel = new Horde_Release_Sentinel(dirname($package_xml));
                $sentinel->updateChanges(
                    Components_Helper_Version::pearToHorde($options['next'])
                );
                $sentinel->updateApplication(
                    Components_Helper_Version::pearToHordeWithBranch(
                        $options['next'],
                        $this->notes['branch']
                    )
                );
                if ($this->_doTask('commit')) {
                    if ($changes = $sentinel->changesFileExists()) {
                        system('git add ' . $changes);
                    }
                    if ($application = $sentinel->applicationFileExists()) {
                        system('git add ' . $application);
                    }
                }
            }

            if ($this->_doTask('commit')) {
                system('git commit -m "Development mode for ' . $package->getName() . '-' . $options['next'] . '."');
            }
        }
    }

    /**
     * Did the user activate the given task?
     *
     * @param string $task The task name.
     *
     * @return boolean True if the task is active.
     */
    private function _doTask($task)
    {
        $arguments = $this->_config->getArguments();
        if ((count($arguments) == 1 && $arguments[0] == 'release')
            || in_array($task, $arguments)) {
            return true;
        }
        return false;
    }

    private function _loadNotes($directory)
    {
        if (file_exists("$directory/RELEASE_NOTES")) {
            include "$directory/RELEASE_NOTES";
            if (strlen($this->notes['fm']['changes']) > 600) {
                print "WARNING: freshmeat release notes are longer than 600 characters!\n";
            }
        }
        if (isset($this->notes['fm']['focus'])) {
            if (is_array($this->notes['fm']['focus'])) {
                $this->notes['tag_list'] = $this->notes['fm']['focus'];
            } else {
                $this->notes['tag_list'] = array($this->notes['fm']['focus']);
            }
        } else {
            $this->notes['tag_list'] = array();
        }
        if (!empty($this->notes['fm']['branch'])) {
            if ($this->notes['name'] == 'Horde') {
                $this->notes['branch'] = '';
            } else {
                $this->notes['branch'] = strtr(
                    $this->notes['fm']['branch'],
                    array('Horde ' => 'H')
                );
            }
        } else {
            $this->notes['branch'] = '';
        }
    }
}

<?php
/**
 * Components_Helper_ChangeLog:: helps with adding entries to the change log(s).
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Components_Helper_ChangeLog:: helps with adding entries to the change log(s).
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Components
 */
class Components_Helper_ChangeLog
{
    /** Path to the CHANGES file. */
    const CHANGES = '/docs/CHANGES';

    /**
     * The output handler.
     *
     * @param Component_Output
     */
    private $_output;

    /**
     * Constructor.
     *
     * @param Component_Output  $output  The output handler.
     */
    public function __construct(Components_Output $output)
    {
        $this->_output = $output;
    }

    /**
     * Update package.xml file.
     *
     * @param string                 $log     The log entry.
     * @param Horde_Pear_Package_Xml $xml     The package xml handler.
     * @param string                 $file    Path to the package.xml.
     * @param array                  $options Additional options.
     *
     * @return NULL
     */
    public function packageXml($log, $xml, $file, $options)
    {
        if (file_exists($file)) {
            if (empty($options['pretend'])) {
                $xml->addNote($log);
                file_put_contents($file, (string) $xml);
                $this->_output->ok(
                    'Added new note to version ' . $xml->getVersion() . ' of ' . $file . '.'
                );
            } else {
                $this->_output->info(
                    sprintf(
                        'Would add change log entry to %s now.',
                        $file
                    )
                );
            }
            return $file;
        }
    }

    /**
     * Update CHANGES file.
     *
     * @param string $log         The log entry.
     * @param string $directory   The path to the component directory.
     * @param array  $options     Additional options.
     *
     * @return NULL
     */
    public function changes($log, $directory, $options)
    {
        $changes = false;
        if ($changes = $this->changesFileExists($directory)) {
            if (empty($options['pretend'])) {
                $this->addChange($log, $changes);
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
            return $changes;
        }
    }

    /**
     * Returns the link to the change log.
     *
     * @param string $root      The root of the component in the repository.
     * @param string $directory The working directory.
     *
     * @return string|null The link to the change log.
     */
    public function getChangelog($root, $directory)
    {
        if ($changes = $this->changesFileExists($directory)) {
            $blob = trim(
                $this->systemInDirectory(
                    'git log --format="%H" HEAD^..HEAD',
                    $directory,
                    array()
                )
            );
            $changes = preg_replace('#^' . $directory . '#', '', $changes);
            return 'https://github.com/horde/horde/blob/' . $blob . $root . $changes;
        }
        return '';
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
            return exec($call);
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
        $entry = Horde_String::wrap($entry, 79, "\n      ");

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

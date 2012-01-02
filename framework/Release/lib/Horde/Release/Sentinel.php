<?php
/**
 * Update the sentinel in CHANGES.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Release
 * @author   Mike Hardy
 * @author   Jan Schneider <jan@horde.org>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Release
 */

/**
 * Update the sentinel in CHANGES.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Release
 * @author   Mike Hardy
 * @author   Jan Schneider <jan@horde.org>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Release
 */
class Horde_Release_Sentinel
{
    /** Path to the CHANGES file. */
    const CHANGES = '/docs/CHANGES';

    /** Path to the Application.php file. */
    const APPLICATION = '/lib/Application.php';

    /** Path to the Bundle.php file. */
    const BUNDLE = '/lib/Bundle.php';

    /**
     * Base component path.
     *
     * @var string
     */
    private $_component;

    /**
     * Constructor.
     *
     * @param string $component Base component path.
     */
    public function __construct($component)
    {
        $this->_component = $component;
    }

    /**
     * Update the CHANGES file in case it exists.
     *
     * @param string $version Version string that should be added.
     *
     * @return NULL
     */
    public function updateChanges($version)
    {
        if ($changes = $this->changesFileExists()) {
            $tmp = Horde_Util::getTempFile();

            $oldfp = fopen($changes, 'r');
            $newfp = fopen($tmp, 'w');
            $version = 'v' . $version;
            fwrite(
                $newfp,
                str_repeat('-', strlen($version)) . "\n$version\n" .
                str_repeat('-', strlen($version)) . "\n\n\n\n"
            );
            while ($line = fgets($oldfp)) {
                fwrite($newfp, $line);
            }
            fclose($oldfp);
            fclose($newfp);

            system("mv -f $tmp $changes");
        }

    }

    /**
     * Replace the current sentinel in the CHANGES file in case it exists.
     *
     * @param string $version Version string that should be added.
     *
     * @return NULL
     */
    public function replaceChanges($version)
    {
        if ($changes = $this->changesFileExists()) {
            $tmp = Horde_Util::getTempFile();

            $oldfp = fopen($changes, 'r');
            $newfp = fopen($tmp, 'w');
            $version = 'v' . $version;
            $counter = 0;
            while ($line = fgets($oldfp)) {
                if ($counter < 2) {
                    $counter++;
                } else if ($counter == 2) {
                    fwrite(
                        $newfp,
                        str_repeat('-', strlen($version)) . "\n$version\n" .
                        str_repeat('-', strlen($version)) . "\n"
                    );
                    $counter++;
                } else {
                    fwrite($newfp, $line);
                }
            }
            fclose($oldfp);
            fclose($newfp);

            system("mv -f $tmp $changes");
        }

    }

    /**
     * Update the Application.php or Bundle.php file in case it exists.
     *
     * @param string $new_version Version string that should be added.
     *
     * @return NULL
     */
    public function updateApplication($version)
    {
        if ($application = $this->applicationFileExists()) {
            $tmp = Horde_Util::getTempFile();

            $oldmode = fileperms($application);
            $oldfp = fopen($application, 'r');
            $newfp = fopen($tmp, 'w');
            while ($line = fgets($oldfp)) {
                $line = preg_replace(
                    '/public \$version = \'[^\']*\';/',
                    'public \$version = \'' . $version . '\';',
                    $line
                );
                fwrite($newfp, $line);
            }
            fclose($oldfp);
            fclose($newfp);
            chmod($tmp, $oldmode);

            system("mv -f $tmp $application");
        } elseif ($bundle = $this->bundleFileExists()) {
            $tmp = Horde_Util::getTempFile();

            $oldmode = fileperms($bundle);
            $oldfp = fopen($bundle, 'r');
            $newfp = fopen($tmp, 'w');
            while ($line = fgets($oldfp)) {
                $line = preg_replace(
                    '/const VERSION = \'[^\']*\';/',
                    'const VERSION = \'' . $version . '\';',
                    $line
                );
                fwrite($newfp, $line);
            }
            fclose($oldfp);
            fclose($newfp);
            chmod($tmp, $oldmode);

            system("mv -f $tmp $bundle");
        }
    }

    /**
     * Indicates if there is a CHANGES file for this component.
     *
     * @return string|boolean The path to the CHANGES file if it exists, false
     *                        otherwise.
     */
    public function changesFileExists()
    {
        $changes = $this->_component . self::CHANGES;
        if (file_exists($changes)) {
            return $changes;
        }
        return false;
    }

    /**
     * Indicates if there is a Application.php file for this component.
     *
     * @return string|boolean The path to the Application.php file if it exists,
     *                        false otherwise.
     */
    public function applicationFileExists()
    {
        $application = $this->_component . self::APPLICATION;
        if (file_exists($application)) {
            return $application;
        }
        return false;
    }

    /**
     * Indicates if there is a Bundle.php file for this component.
     *
     * @return string|boolean The path to the Bundle.php file if it exists,
     *                        false otherwise.
     */
    public function bundleFileExists()
    {
        $bundle = $this->_component . self::BUNDLE;
        if (file_exists($bundle)) {
            return $bundle;
        }
        return false;
    }

    /**
     * Returns the current version from Application.php or Bundle.php.
     *
     * @return string Version string.
     */
    public function getVersion()
    {
        if ($application = $this->applicationFileExists()) {
            $oldfp = fopen($application, 'r');
            while ($line = fgets($oldfp)) {
                if (preg_match('/public \$version = \'([^\']*)\';/', $line, $match)) {
                    return $match[1];
                }
            }
            fclose($oldfp);
        }
        if ($bundle = $this->bundleFileExists()) {
            $oldfp = fopen($bundle, 'r');
            while ($line = fgets($oldfp)) {
                if (preg_match('/const VERSION = \'([^\']*)\';/', $line, $match)) {
                    return $match[1];
                }
            }
            fclose($oldfp);
        }
    }
}

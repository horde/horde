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
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Release
 */

/**
 * Update the sentinel in CHANGES.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Release
 * @author   Mike Hardy
 * @author   Jan Schneider <jan@horde.org>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Release
 */
class Horde_Release_Sentinel
{
    /** Path to the CHANGES file. */
    const CHANGES = '/docs/CHANGES';

    /** Path to the Application.php file. */
    const APPLICATION = '/lib/Application.php';

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
                str_repeat('-', strlen($version)) . "\n\n\n\n\n"
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
     * Update the Application.php file in case it exists.
     *
     * @param string $new_version Version string that should be added.
     *
     * @return NULL
     */
    public function updateApplication($version)
    {
        if ($application = $this->applicationFileExists()) {
            $tmp = Horde_Util::getTempFile();

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

            system("mv -f $tmp $application");
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
}

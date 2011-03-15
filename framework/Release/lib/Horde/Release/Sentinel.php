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
    /**
     * Path to the CHANGES file.
     *
     * @var string
     */
    private $_path;

    /**
     * Temporary update location.
     *
     * @var string
     */
    private $_new_path;

    /**
     * Version string that should be added.
     *
     * @var string
     */
    private $_new_version;

    /**
     * Constructor.
     *
     * @param string $path        Path to the CHANGES file.
     * @param string $new_version Version string that should be added.
     */
    public function __construct($path, $new_version)
    {
        $this->_path = $path;
        $this->_new_path = $this->_path . '.new';
        $this->_new_version = $new_version;
    }

    /**
     * Update the CHANGES file.
     *
     * @return NULL
     */
    public function update()
    {
        $version = 'v' . $this->_new_version;
        $oldfp = fopen($this->_path, 'r');
        $newfp = fopen($this->_new_path, 'w');
        fwrite($newfp, str_repeat('-', strlen($version)) . "\n$version\n" .
               str_repeat('-', strlen($version)) . "\n\n\n\n\n");
        while ($line = fgets($oldfp)) {
            fwrite($newfp, $line);
        }
        fclose($oldfp);
        fclose($newfp);

        system("mv -f $this->_new_path $this->_path");
    }
}

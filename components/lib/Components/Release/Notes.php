<?php
/**
 * Components_Release_Notes:: deals with the information associated to a
 * release.
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
 * Components_Release_Notes:: deals with the information associated to a
 * release.
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
class Components_Release_Notes
{
    /**
     * The release information.
     *
     * @var array
     */
    private $notes = array();

    /**
     * The package that should be released
     *
     * @var Components_Pear_Package
     */
    private $_package;

    /**
     * The task output.
     *
     * @var Components_Output
     */
    private $_output;

    /**
     * Constructor.
     *
     * @param Components_Output $output Accepts output.
     */
    public function __construct(Components_Output $output)
    {
        $this->_output = $output;
    }

    /**
     * Set the package this task should act upon.
     *
     * @param Components_Pear_Package $package The package to be released.
     *
     * @return NULL
     */
    public function setPackage(Components_Pear_Package $package)
    {
        $this->_package = $package;
        $notes = $package->getComponentDirectory() . '/docs/RELEASE_NOTES';
        if (file_exists($notes)) {
            include $notes;
            if (strlen($this->notes['fm']['changes']) > 600) {
                $this->_output->warn(
                    'freshmeat release notes are longer than 600 characters!'
                );
            }
        }
    }

    /**
     * The branch information for this package. This is empty for framework
     * components and the Horde base application and has a value like "H3",
     * "H4", etc. for applications.
     *
     * @return string The branch name.
     */
    public function getBranch()
    {
        if (!empty($this->notes['fm']['branch'])
            && $this->notes['name'] != 'Horde') {
            return strtr($this->notes['fm']['branch'], array('Horde ' => 'H'));
        } else {
            return '';
        }
    }

    /**
     * Returns the link to the change log.
     *
     * @return string|null The link to the change log.
     */
    public function getChangelog()
    {
        $dir = $this->_package->getComponentDirectory();
        if (basename(dirname($dir)) == 'framework') {
            $root = '/framework/' . basename($dir);
        } else {
            $root = '/' . basename($dir);
        }
        if (file_exists($dir . '/docs/CHANGES')) {
            $old_dir = getcwd();
            chdir($dir);
            $blob = trim(system('git log --format="%H" HEAD^..HEAD'));
            chdir($old_dir);
            return 'https://github.com/horde/horde/blob/' . $blob . $root . '/docs/CHANGES';
        }
        return '';
    }

    /**
     * Returns the release name.
     *
     * @return string The release name.
     */
    public function getName()
    {
        if (isset($this->notes['name'])) {
            return $this->notes['name'];
        } else {
            return $this->_package->getName();
        }
    }

    /**
     * Returns the specific mailing list that the release announcement for this
     * package should be sent to.
     *
     * @return string|null The mailing list.
     */
    public function getList()
    {
        if (isset($this->notes['list'])) {
            return $this->notes['list'];
        }
    }

    /**
     * Return the list of release foci.
     *
     * @return array The main topics of the release.
     */
    public function getFocusList()
    {
        if (isset($this->notes['fm']['focus'])) {
            if (is_array($this->notes['fm']['focus'])) {
                return $this->notes['fm']['focus'];
            } else {
                return array($this->notes['fm']['focus']);
            }
        } else {
            return array();
        }
    }

    /**
     * Return the announcement text.
     *
     * @return string The text.
     */
    public function getAnnouncement()
    {
        if (isset($this->notes['ml']['changes'])) {
            return $this->notes['ml']['changes'];
        }
        return '';
    }

    /**
     * Return the freshmeat project name.
     *
     * @return string The project name.
     */
    public function getFmProject()
    {
        if (isset($this->notes['fm']['project'])) {
            return $this->notes['fm']['project'];
        }
        return '';
    }

    /**
     * Return the freshmeat change log.
     *
     * @return string The change log.
     */
    public function getFmChanges()
    {
        if (isset($this->notes['fm']['changes'])) {
            return $this->notes['fm']['changes'];
        }
        return '';
    }

    /**
     * Does the current component come with release notes?
     *
     * @return boolean True if release notes are available.
     */
    public function hasNotes()
    {
        return !empty($this->notes);
    }

    /**
     * Does the current component come with freshmeat information?
     *
     * @return boolean True if freshmeat information is available.
     */
    public function hasFreshmeat()
    {
        return !empty($this->notes['fm']);
    }
}
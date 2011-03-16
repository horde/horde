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
}
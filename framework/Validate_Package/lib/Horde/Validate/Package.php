<?php
/**
 * This file contains the Horde_Validate_Package class for validating Horde
 * PEAR packages.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @category Horde
 * @package  Validate_Package
 */

/**
 * This class extends PEAR's validation class for validating PEAR packages,
 * especially package.xml files.
 *
 * Horde has different application naming and versioning rules that are
 * validated by this class.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Validate_Package
 */
class Horde_Validate_Package extends PEAR_Validate
{
    /**
     * Regular expression for valid package names.
     *
     * @var string
     */
    public $packageregex = '(Horde_[A-Z][a-zA-Z0-9_]+)|([a-z][a-z0-9-]+)';

    /**
     * Regular expression vor valid version names.
     *
     * @var string
     */
    protected $_versionregex = '/^(h\d-)?(\d+)\.(\d+)(.\d+)?(-(alpha|beta|rc\d+))?$/';

    /**
     * Determines whether a version is a properly formatted version number that
     * can be used by version_compare.
     *
     * @param string $ver
     *
     * @return boolean
     */
    public function validVersion($ver)
    {
        return (bool)preg_match($this->_versionregex, $ver);
    }

    /**
     */
    public function validateVersion()
    {
        $version = $this->_packagexml->getVersion();

        if ($this->_state != PEAR_VALIDATE_PACKAGING) {
            if (!$this->validVersion($version)) {
                $this->_addFailure('version',
                    'Invalid version number "' . $version . '"');
            }
            return false;
        }

        $name = $this->_packagexml->getPackage();
        preg_match($this->_versionregex, $version, $match);
        $framework = strpos($name, 'Horde_') === 0;

        /* Validate framework packages. */
        if ($framework && !preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            $this->_addFailure('version',
                               'A framework package version number should have 3 decimals (x.y.z)');
            return false;
        }

        /* Validate applications. */
        if (!$framework) {
            if ($name == 'horde' && !empty($match[1])) {
                $this->_addFailure('version',
                                   'Horde releases must not have a hN- prefix.');
                return false;
            }
            if ($name != 'horde' && empty($match[1])) {
                $this->_addFailure('version',
                                   'Application releases must have a hN- prefix.');
                return false;
            }
            if (!empty($match[4]) && $match[4] === '.0') {
                $this->_addFailure('version',
                                   'An initial minor version number should have 2 decimals (x.0)');
                return false;
            }
        }

        /* Version checks based on state. */
        $state = $this->_packagexml->getState();

        if (!empty($match[6]) && $match[6] == 'alpha' && $state != 'alpha') {
            $this->_addFailure('version',
                               'Alpha versions must have the state alpha');
            return false;
        }
        if (!$framework && $state == 'alpha' &&
            (empty($match[6]) || $match[6] != 'alpha')) {
            $this->_addFailure('version',
                               'Application releases with alpha state must have an -alpha suffix');
            return false;
        }
        if (!empty($match[6]) &&
            ($match[6] == 'beta' || substr($match[6], 0, 2) == 'rc') &&
            $state != 'beta') {
            $this->_addFailure('version',
                               'Beta versions and release candidates must have the state beta');
            return false;
        }
        if (!$framework && $state == 'beta' &&
            (empty($match[6]) ||
             ($match[6] != 'beta' && substr($match[6], 0, 2) != 'rc'))) {
            $this->_addFailure('version',
                               'Application releases with beta state must have an -beta or -rcN suffix');
            return false;
        }
        if ($state == 'stable' && !empty($match[5])) {
            $this->_addFailure('version',
                               'Stables releases must not have a suffix');
            return false;
        }
        if ($state == 'stable' && $match[2] == 0) {
            $this->_addFailure('version',
                               'Stables releases must have a version number of at least 1.0');
            return false;
        }
    }
}

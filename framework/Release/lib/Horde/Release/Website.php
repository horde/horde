<?php
/**
 * Copyright 2014-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Release
 * @author   Jan Schneider <jan@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Release
 */

/**
 * Class for interfacing with the version DB of the Horde website.
 *
 * @category Horde
 * @package  Release
 * @author   Jan Schneider <jan@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Release
 */
class Horde_Release_Website
{
    /**
     * Database handle.
     *
     * @var PDO
     */
    protected $_db;

    /**
     * Constructor.
     *
     * @param PDO $db  Database handle.
     */
    public function __construct(PDO $db)
    {
        $this->_db = $db;
    }

    /**
     * Adds a new version to an application.
     *
     * @param array $info  Hash with the version information. Possible keys:
     *                     - application: (string) The name of the application.
     *                     - version: (string) The version string.
     *                     - state: (string) The version state. One of
     *                              "stable", "dev", "three". By default
     *                              automatically detected from "version.
     *                     - date: (DateTime) The release date. Defaults to
     *                             today.
     *                     - pear: (boolean) A PEAR release? Defaults to true.
     *                     - dir: (string) Optional website directory, if not
     *                            "application".
     *
     * @throws Horde_Exception
     */
    public function addNewVersion(array $info = array())
    {
        if (!isset($info['application']) || !isset($info['version'])) {
            throw new LogicException('Missing parameter');
        }
        $info = array_merge(
            array('state' => $this->_stateFromVersion($info['version']),
                  'date' => new DateTime(),
                  'pear' => true,
                  'dir' => null),
            $info
        );
        if (!in_array($info['state'], array('stable', 'dev', 'three'))) {
            throw new LogicException('Invalid state ' . $info['state']);
        }

        $info['date'] = $info['date']->format('Y-m-d');
        $bind = array();
        foreach ($info as $key => $value) {
            $bind[':' . $key] = $value;
        }

        try {
            $stmt = $this->_db
                ->prepare('SELECT 1 FROM versions WHERE application = :application AND state = :state');
            $stmt->execute(array(':application' => $info['application'], ':state' => $info['state']));
            $stmt = $stmt->fetchColumn()
                ? $this->_db->prepare('UPDATE versions SET version = :version, date = :date, pear = :pear, dir = :dir WHERE application = :application AND state = :state')
                : $this->_db->prepare('INSERT INTO versions (application, state, version, date, pear, dir) VALUES (:application, :state, :version, :date, :pear, :dir)');
            if (!$stmt->execute($bind)) {
                $error = $stmt->errorInfo();
                throw new Horde_Exception($error[2], $error[1]);
            }
        } catch (PDOException $e) {
            throw new Horde_Exception($e);
        }
    }

    /**
     * Returns the release stability of a version.
     *
     * @param string $version  A version string.
     *
     * @return string Release stability information.
     * @throws Horde_Exception on invalid version string.
     */
    protected function _stateFromVersion($version)
    {
        if (!preg_match('/^(\d+\.\d+\.\d+)(alpha|beta|RC|dev)?\d*$/', $version, $match)) {
            throw new Horde_Exception('Invalid version ' . $version);
        }
        return isset($match[2]) ? 'dev' : 'stable';
    }
}

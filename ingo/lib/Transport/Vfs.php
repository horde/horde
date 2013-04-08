<?php
/**
 * Copyright 2003-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Brent J. Nordquist <bjn@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

/**
 * Ingo_Transport_Vfs implements an Ingo transport driver using Horde VFS.
 *
 * @author   Brent J. Nordquist <bjn@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Ingo_Transport_Vfs extends Ingo_Transport_Base
{
    /**
     * Constructs a new VFS-based storage driver.
     *
     * @param array $params  A hash containing driver parameters.
     */
    public function __construct(array $params = array())
    {
        $default_params = array(
            'hostspec' => 'localhost',
            'port'     => 21,
            'filename' => '.ingo_filter',
            'vfstype'  => 'ftp',
            'vfs_path' => '',
            'vfs_forward_path' => '',
        );

        $this->_supportShares = true;

        parent::__construct(array_merge($default_params, $params));
    }

    /**
     * Sets a script running on the backend.
     *
     * @param array $script  The filter script information. Passed elements:
     *                       - 'name': (string) the script name.
     *                       - 'recipes': (array) the filter recipe objects.
     *                       - 'script': (string) the filter script.
     *
     * @throws Ingo_Exception
     */
    public function setScriptActive($script)
    {
        $this->_connect();

        try {
            if (!empty($script['script'])) {
                $this->_vfs->writeData($this->_params['vfs_path'], $script['name'], $script['script'], true);
            } elseif ($this->_vfs->exists($this->_params['vfs_path'], $script['name'])) {
                $this->_vfs->deleteFile($this->_params['vfs_path'], $script['name']);
            }
        } catch (Horde_Vfs_Exception $e) {
            throw new Ingo_Exception($e);
        }

        if (isset($this->_params['file_perms'])) {
            try {
                if (!empty($script['script'])) {
                    $this->_vfs->changePermissions($this->_params['vfs_path'], $script['name'], $this->_params['file_perms']);
                }
            } catch (Horde_Vfs_Exception $e) {
                throw new Ingo_Exception($e);
            }
        }
    }

    /**
     * Returns the content of the currently active script.
     *
     * @return array  The complete ruleset of the specified user.
     * @throws Ingo_Exception
     * @throws Horde_Exception_NotFound
     */
    public function getScript()
    {
        $this->_connect();
        try {
            if (!$this->_vfs->exists($this->_params['vfs_path'], $this->_params['filename'])) {
                throw new Horde_Exception_NotFound();
            }
            return array(
                'name' => $this->_params['filename'],
                'script' => $this->_vfs->read($this->_params['vfs_path'], $this->_params['filename'])
            );
        } catch (Horde_Vfs_Exception $e) {
            throw new Ingo_Exception($e);
        }
    }

    /**
     * Connect to the VFS server.
     *
     * @throws Ingo_Exception
     */
    protected function _connect()
    {
        /* Do variable substitution. */
        if (!empty($this->_params['vfs_path'])) {
            $this->_params['vfs_path'] = str_replace(
                array('%u', '%d', '%U'),
                array(Ingo::getUser(), Ingo::getDomain(), $this->_params['username']),
                $this->_params['vfs_path']);
        }

        if (!empty($this->_vfs)) {
            return;
        }

        try {
            $this->_vfs = $GLOBALS['injector']
                ->getInstance('Horde_Core_Factory_Vfs')
                ->create('ingo', array('type'   => $this->_params['vfstype'],
                                       'params' => $this->_params));
        } catch (Horde_Exception $e) {
            throw new Ingo_Exception($e);
        }
    }
}

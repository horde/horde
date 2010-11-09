<?php
/**
 * Ingo_Transport_Vfs implements an Ingo storage driver using Horde VFS.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Brent J. Nordquist <bjn@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Ingo
 */
class Ingo_Transport_Vfs extends Ingo_Transport
{
    /**
     * Constructs a new VFS-based storage driver.
     *
     * @param array $params  A hash containing driver parameters.
     */
    public function __construct($params = array())
    {
        $this->_support_shares = true;

        $default_params = array(
            'hostspec' => 'localhost',
            'port'     => 21,
            'filename' => '.ingo_filter',
            'vfstype'  => 'ftp',
            'vfs_path' => '',
            'vfs_forward_path' => '',
        );

        parent::__construct(array_merge($default_params, $params));
    }

    /**
     * Sets a script running on the backend.
     *
     * @param string $script  The filter script.
     *
     * @return mixed  True on success.
     * @throws Ingo_Exception
     */
    public function setScriptActive($script)
    {
        $this->_connect();

        try {
            if (empty($script)) {
                $this->_vfs->deleteFile($this->_params['vfs_path'], $this->_params['filename']);
            } else {
                $this->_vfs->writeData($this->_params['vfs_path'], $this->_params['filename'], $script, true);
            }
        } catch (VFS_Exception $e) {
            throw new Ingo_Exception($e);
        }

        if (isset($this->_params['file_perms']) && !empty($script)) {
            try {
                $this->_vfs->changePermissions($this->_params['vfs_path'], $this->_params['filename'], $this->_params['file_perms']);
            } catch (VFS_Exception $e) {
                throw new Ingo_Exception($e);
            }
        }

        // Get the backend; necessary if a .forward is needed for
        // procmail.
        $backend = Ingo::getBackend();
        if (($backend['script'] == 'procmail') &&
            isset($backend['params']['forward_file']) &&
            isset($backend['params']['forward_string'])) {
            try {
                if (empty($script)) {
                    $this->_vfs->deleteFile($this->_params['vfs_forward_path'], $backend['params']['forward_file']);
                } else {
                    $this->_vfs->writeData($this->_params['vfs_forward_path'], $backend['params']['forward_file'], $backend['params']['forward_string'], true);
                }
            } catch (VFS_Exception $e) {
                throw new Ingo_Exception($e);
            }

            if (isset($this->_params['file_perms']) && !empty($script)) {
                try {
                    $this->_vfs->changePermissions($this->_params['vfs_forward_path'], $backend['params']['forward_file'], $this->_params['file_perms']);
                } catch (VFS_Exception $e) {
                    throw new Ingo_Exception($e);
                }
            }
        }

        return true;
    }

    /**
     * Returns the content of the currently active script.
     *
     * @return string  The complete ruleset of the specified user.
     * @throws Ingo_Exception
     */
    public function getScript()
    {
        $this->_connect();
        return $this->_vfs->read($this->_params['vfs_path'], $this->_params['filename']);
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
            $user = Ingo::getUser();
            $domain = Ingo::getDomain();
            if ($GLOBALS['session']->get('ingo', 'backend/hordeauth') !== 'full') {
                $pos = strpos($user, '@');
                if ($pos !== false) {
                    $domain = substr($user, $pos + 1);
                    $user = substr($user, 0, $pos);
                }
            }
            $this->_params['vfs_path'] = str_replace(
                array('%u', '%d', '%U'),
                array($user, $domain, $this->_params['username']),
                $this->_params['vfs_path']);
        }

        if (!empty($this->_vfs)) {
            return true;
        }

        try {
            $this->_vfs = VFS::singleton($this->_params['vfstype'], $this->_params);
        } catch (VFS_Exception $e) {
            $error = new Ingo_Exception($this->_vfs);
            unset($this->_vfs);
            throw $error;
        }
    }

}

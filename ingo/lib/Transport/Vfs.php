<?php
/**
 * Ingo_Transport_Vfs implements an Ingo storage driver using Horde VFS.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
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
     * @param string $script     The filter script.
     * @param array $additional  Any additional scripts that need to uploaded.
     *
     * @throws Ingo_Exception
     */
    public function setScriptActive($script, $additional = array())
    {
        $this->_connect();

        try {
            if (!empty($script)) {
                $this->_vfs->writeData($this->_params['vfs_path'], $this->_params['filename'], $script, true);
            } elseif ($this->_vfs->exists($this->_params['vfs_path'], $this->_params['filename'])) {
                $this->_vfs->deleteFile($this->_params['vfs_path'], $this->_params['filename']);
            }
            foreach ($additional as $filename => $content) {
                if (strlen($content)) {
                    $this->_vfs->writeData($this->_params['vfs_path'], $filename, $content, true);
                } elseif ($this->_vfs->exists($this->_params['vfs_path'], $filename)) {
                    $this->_vfs->deleteFile($this->_params['vfs_path'], $filename);
                }
            }
        } catch (Horde_Vfs_Exception $e) {
            throw new Ingo_Exception($e);
        }

        if (isset($this->_params['file_perms'])) {
            try {
                if (!empty($script)) {
                    $this->_vfs->changePermissions($this->_params['vfs_path'], $this->_params['filename'], $this->_params['file_perms']);
                }
                foreach ($additional as $filename => $content) {
                    if (strlen($content)) {
                        $this->_vfs->changePermissions($this->_params['vfs_path'], $filename, $this->_params['file_perms']);
                    }
                }
            } catch (Horde_Vfs_Exception $e) {
                throw new Ingo_Exception($e);
            }
        }
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
        try {
            return $this->_vfs->read($this->_params['vfs_path'], $this->_params['filename']);
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
            $this->_vfs = $GLOBALS['injector']
                ->getInstance('Horde_Core_Factory_Vfs')
                ->create('ingo', array('type'   => $this->_params['vfstype'],
                                       'params' => $this->_params));
        } catch (Horde_Exception $e) {
            throw new Ingo_Exception($e);
        }
    }
}

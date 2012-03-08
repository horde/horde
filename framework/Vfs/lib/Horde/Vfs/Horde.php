<?php
/**
 * VFS implementation for the Horde Application Framework.
 *
 * Required parameters:<pre>
 *   'horde_base'  Filesystem location of a local Horde installation.</pre>
 *
 * Optional parameters:<pre>
 *   'user'      A valid Horde user name.
 *   'password'  The user's password.</pre>
 *
 * Copyright 2006-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Vfs
 */
class Horde_Vfs_Horde extends Horde_Vfs_Base
{
    /**
     * Reference to a Horde Registry instance.
     *
     * @var Horde_Registry
     */
    protected $_registry;

    /**
     * Constructor.
     *
     * @param array $params  A hash containing connection parameters.
     * @throws Horde_Vfs_Exception
     */
    public function __construct($params = array())
    {
        parent::__construct($params);

        if (!isset($this->_params['horde_base'])) {
            throw new Horde_Vfs_Exception('Required "horde_base" not specified in VFS configuration.');
        }

        require_once $this->_params['horde_base'] . '/lib/Application.php';
        Horde_Registry::appInit('horde');

        // Create the Registry object.
        $this->_registry = $GLOBALS['registry'];
    }

    /**
     */
    protected function _connect()
    {
        if (!empty($this->_params['user']) &&
            !empty($this->_params['password'])) {
            $GLOBALS['registry']->setAuth($this->_params['user'], array('password' => $this->_params['password']));
        }
    }

    /**
     * Retrieves a file from the VFS.
     *
     * @param string $path  The pathname to the file.
     * @param string $name  The filename to retrieve.
     *
     * @return string  The file data.
     */
    public function read($path, $name)
    {
        if (substr($path, 0, 1) == '/') {
            $path = substr($path, 1);
        }
        $pieces = explode('/', $path);

        try {
            $data = $this->_registry->callByPackage($pieces[0], 'browse', array('path' => $path . '/' . $name));
        } catch (Horde_Exception $e) {
            return '';
        }

        return is_object($data) ? $data : $data['data'];
    }

    /**
     * Returns an unsorted file list of the specified directory.
     *
     * @param string $path          The path of the directory.
     * @param string|array $filter  Regular expression(s) to filter
     *                              file/directory name on.
     * @param boolean $dotfiles     Show dotfiles?
     * @param boolean $dironly      Show only directories?
     *
     * @return array  File list.
     * @throws Horde_Vfs_Exception
     */
    protected function _listFolder($path, $filter = null, $dotfiles = true,
                                   $dironly = false)
    {
        $list = array();
        if ($path == '/') {
            try {
                $apps = $this->_registry->listApps(null, false, Horde_Perms::READ);
            } catch (Horde_Exception $e) {
                throw new Horde_Vfs_Exception($e->getMessage());
            }

            foreach ($apps as $app) {
                if ($this->_registry->hasMethod('browse', $app)) {
                    $file = array(
                        //'name' => $this->_registry->get('name', $app),
                        'name' => $app,
                        'date' => time(),
                        'type' => '**dir',
                        'size' => -1
                    );
                    $list[] = $file;
                }
            }
            return $list;
        }

        if (substr($path, 0, 1) == '/') {
            $path = substr($path, 1);
        }
        $pieces = explode('/', $path);

        try {
            $items = $this->_registry->callByPackage($pieces[0], 'browse', array('path' => $path, 'properties' => array('name', 'browseable', 'contenttype', 'contentlength', 'modified')));
        } catch (Horde_Exception $e) {
            throw new Horde_Vfs_Exception($e->getMessage());
        }

        if (!is_array(reset($items))) {
            /* We return an object's content. */
            throw new Horde_Vfs_Exception('Unknown error');
        }

        foreach ($items as $sub_path => $i) {
            if ($dironly && !$i['browseable']) {
                continue;
            }

            $name = basename($sub_path);
            if ($this->_filterMatch($filter, $name)) {
                continue;
            }

            $type = class_exists('Horde_Mime_Magic')
                ? Horde_Mime_Magic::mimeToExt(empty($i['contenttype']) ? 'application/octet-stream' : $i['contenttype'])
                : '**none';

            $file = array(
                //'name' => $i['name'],
                'name' => $name,
                'date' => empty($i['modified']) ? 0 : $i['modified'],
                'type' => $i['browseable'] ? '**dir' : $type,
                'size' => empty($i['contentlength']) ? 0 : $i['contentlength']
            );
            $list[] = $file;
        }

        return $list;
    }

}

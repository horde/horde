<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Vfs
 */

/**
 * MongoDB driver for VFS storage backend.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Vfs
 */
class Horde_Vfs_Mongo extends Horde_Vfs_Base
{
    /* Metadata subdocument identifier. */
    const MD = 'metadata';

    /* Field (metadata) names. */
    const FNAME = 'vfile';
    const OWNER = 'owner';
    const PATH = 'vpath';

    /* Field (folders) names. */
    const FOLDER_OWNER = 'owner';
    const FOLDER_PATH = 'path';
    const FOLDER_TS = 'ts';

    /**
     * The MongoDB GridFS object for the VFS data.
     *
     * @var MongoGridFS
     */
    protected $_files;

    /**
     * The MongoDB collection for the VFS folder data.
     *
     * @var MongoGridCollection
     */
    protected $_folders;

    /**
     * Constructor.
     *
     * @param array $params  Additional parameters:
     * <pre>
     *   - collection: (string) The collection name for the folders data.
     *   - gridfs: (string) The GridFS name.
     *   - mongo_db: [REQUIRED] (Horde_Mongo_Client) A MongoDB client object.
     * </pre>
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['mongo_db'])) {
            throw new InvalidArgumentException('Missing mongo_db parameter.');
        }

        parent::__construct(array_merge(array(
            'collection' => 'horde_vfs_folders',
            'gridfs' => 'horde_vfs'
        ), $params));

        $this->_files = $this->_params['mongo_db']->selectDB(null)->getGridFS($this->_params['gridfs']);
        $this->_folders = $this->_params['mongo_db']->selectDB(null)->selectCollection($this->_params['collection']);
    }

    /**
     */
    public function size($path, $name)
    {
        if ($res = $this->_getFile($path, $name)) {
            return $res->getSize();
        }

        throw new Horde_Vfs_Exception(sprintf('Unable to retrieve file size of "%s/%s".', $path, $name));
    }

    /**
     */
    public function getFolderSize($path = null)
    {
        $query = array();
        if (!is_null($path)) {
            $query[$this->_mdKey(self::PATH)] = array(
                '$regex' => '^' . $this->_convertPath($path)
            );
        }

        $size = 0;

        try {
            foreach ($this->_files->find($query) as $val) {
                $size += $val->getSize();
            }
        } catch (MongoException $e) {
            throw new Horde_Vfs_Exception($e);
        }

        return $size;
    }

    /**
     */
    public function read($path, $name)
    {
        if ($res = $this->_getFile($path, $name)) {
            return $res->getBytes();
        }

        throw new Horde_Vfs_Exception(sprintf('Unable to read file "%s/%s".', $path, $name));
    }

    /**
     */
    public function readByteRange($path, $name, &$offset, $length, &$remaining)
    {
        if (!($res = $this->_getFile($path, $name))) {
            throw new Horde_Vfs_Exception(sprintf('Unable to read file "%s/%s".', $path, $name));
        }

        $stream = $res->getResource();

        $data = stream_get_contents($stream, $length, $offset);
        $curr = ftell($stream);
        fclose($stream);

        $offset = min($curr, $offset + $length);
        $remaining = max(0, $res->getSize() - $curr);

        return $data;
    }

    /**
     * Open a read-only stream to a file in the VFS.
     *
     * @param string $path  The pathname to the file.
     * @param string $name  The filename to retrieve.
     *
     * @return resource  The stream.
     * @throws Horde_Vfs_Exception
     */
    public function readStream($path, $name)
    {
        if ($res = $this->_getFile($path, $name)) {
            return $res->getResource();
        }

        throw new Horde_Vfs_Exception(sprintf('Unable to read file "%s/%s".', $path, $name));
    }

    /**
     */
    public function write($path, $name, $tmpFile, $autocreate = false)
    {
        $this->_write('file', $path, $name, $tmpFile, $autocreate);
    }

    /**
     */
    public function writeData($path, $name, $data, $autocreate = false)
    {
        $this->_write('string', $path, $name, $data, $autocreate);
    }

    /**
     */
    protected function _write($type, $path, $name, $data, $autocreate)
    {
        $this->_checkQuotaWrite($type, $data);

        if ($autocreate) {
            $this->autocreatePath($path);
        } elseif (!$this->_isFolder($path)) {
            throw new Horde_Vfs_Exception(sprintf('Folder "%s" does not exist', $path));
        }

        $orig = $this->_getFile($path, $name);
        $mdata = array(
            self::MD => array(
                self::FNAME => $name,
                self::OWNER => $this->_params['user'],
                self::PATH => $this->_convertPath($path)
            )
        );

        try {
            switch ($type) {
            case 'file':
                $this->_files->storeFile($data, $mdata);
                break;

            case 'string':
                $this->_files->storeBytes($data, $mdata);
                break;
            }
        } catch (MongoException $e) {
            throw new Horde_Vfs_Exception('Unable to write file data.');
        }

        if ($orig) {
            $this->_files->delete($orig->file['_id']);
        }
    }

    /**
     */
    public function deleteFile($path, $name)
    {
        if ($orig = $this->_getFile($path, $name)) {
            $this->_checkQuotaDelete($path, $name);
            $this->_files->delete($orig->file['_id']);
        } else {
            throw new Horde_Vfs_Exception('Unable to delete VFS file.');
        }
    }

    /**
     */
    public function rename($oldpath, $oldname, $newpath, $newname)
    {
        if (!($res = $this->_getFile($oldpath, $oldname))) {
            throw new Horde_Vfs_Exception('Unable to rename VFS file.');
        }

        $this->autocreatePath($newpath);

        $res->file[self::MD][self::FNAME] = $newname;
        $res->file[self::MD][self::PATH] = $newpath;

        try {
            $this->_files->save($res->file);
        } catch (MongoException $e) {
            throw new Horde_Vfs_Exception(sprintf('Unable to rename VFS file %s/%s.', $oldpath, $oldname));
        }
    }

    /**
     */
    public function createFolder($path, $name)
    {
        $query = array(
            self::FOLDER_OWNER => $this->_params['user'],
            self::FOLDER_PATH => $this->_convertPath($path . '/' . $name),
            self::FOLDER_TS => new MongoDate()
        );

        try {
            $this->_folders->insert($query);
        } catch (MongoException $e) {}
    }

    /**
     */
    public function isFolder($path, $name)
    {
        return $this->_isFolder($path . '/' . $name);
    }

    /**
     */
    protected function _isFolder($path)
    {
        $path = $this->_convertPath($path);
        if (!strlen($path)) {
            return true;
        }

        $query = array(
            self::FOLDER_PATH => $path
        );

        try {
            return (bool) $this->_folders->find($query)->limit(1)->count();
        } catch (MongoException $e) {
            return false;
        }
    }

    /**
     */
    public function deleteFolder($path, $name, $recursive = false)
    {
        $fullpath = $path . '/' . $name;

        if ($recursive) {
            $this->emptyFolder($fullpath);
        } else {
            $query = array(
                $this->_mdKey(self::PATH) => array(
                    '$regex' => '^' . $this->_convertPath($fullpath)
                )
            );

            if ($this->_files->find($query)->limit(1)->count()) {
                throw new Horde_Vfs_Exception(sprintf('Unable to delete %s/%s; the directory is not empty.', $path, $name));
            }
        }

        try {
            $this->_folders->remove(array(
                self::FOLDER_PATH => $this->_convertPath($fullpath)
            ));
        } catch (MongoException $e) {
            throw new Horde_Vfs_Exception($e);
        }
    }

    /**
     */
    public function emptyFolder($path)
    {
        $query = array(
            $this->_mdKey(self::PATH) => array(
                '$regex' => '^' . $this->_convertPath($path)
            )
        );
        $size = null;

        try {
            if (!is_null($this->_vfsSize)) {
                $files = $this->_files->find($query);
                $ids = array();

                foreach ($files as $val) {
                    $ids[] = $val->file['_id'];
                    $size += $val->getSize();
                }

                $query = array(
                    '_id' => array(
                        '$in' => $ids
                    )
                );
            }

            $this->_files->remove($query);
            $this->_folders->remove(array(
                self::FOLDER_PATH => array(
                    '$regex' => '^' . $this->_convertPath($path) . '/'
                )
            ));
        } catch (MongoException $e) {
            throw new Horde_Vfs_Exception($e);
        }

        if (!is_null($size)) {
            $this->_vfsSize -= $size;
        }
    }

    /**
     * Returns an an unsorted file list of the specified directory.
     *
     * @param string $path       The path of the directory.
     * @param mixed $filter      String/hash to filter file/dirname on.
     * @param boolean $dotfiles  Show dotfiles?
     * @param boolean $dironly   Show only directories?
     *
     * @return array  File list.
     * @throws Horde_Vfs_Exception
     */
    protected function _listFolder($path, $filter = null, $dotfiles = true,
                                   $dironly = false)
    {
        if (!$this->_isFolder($path)) {
            throw new Horde_Vfs_Exception(sprintf('Folder "%s" does not exist', $path));
        }

        $out = array();
        $path = $this->_convertPath($path);

        if (!$dironly) {
            try {
                $files = $this->_files->find(array(
                    $this->_mdKey(self::PATH) => $this->_convertPath($path)
                ));
            } catch (MongoException $e) {
                throw new Horde_Vfs_Exception($e);
            }

            foreach ($files as $val) {
                $name = $val->file[self::MD][self::FNAME];

                // Filter out dotfiles if they aren't wanted.
                if (!$dotfiles && ($name[0] == '.')) {
                    continue;
                }

                // Filtering.
                if ($this->_filterMatch($filter, $name)) {
                    continue;
                }

                $tmp = array(
                    'date' => $val->file['uploadDate']->sec,
                    'group' => '',
                    'name' => $name,
                    'owner' => $val->file[self::MD][self::OWNER],
                    'perms' => '',
                    'size' => $val->getSize()
                );

                $type = explode('.', $name);
                $tmp['type'] = (count($type) == 1)
                    ? '**none'
                    : Horde_String::lower(array_pop($type));

                $out[$name] = $tmp;
            }
        }

        try {
            $folders = $this->_folders->find(array(
                self::FOLDER_PATH => array(
                    '$regex' => '^' . (strlen($path) ? $path . '/' : '') . '[^\/]+$'
                )
            ));
        } catch (MongoException $e) {
            throw new Horde_Vfs_Exception($e);
        }

        foreach ($folders as $val) {
            $tmp = explode('/', $val[self::FOLDER_PATH]);
            $path = array_pop($tmp);

            if (isset($out[$path]) ||
                $this->_filterMatch($filter, $path)) {
                continue;
            }

            $out[$path] = array(
                'date' => $val[self::FOLDER_TS]->sec,
                'group' => '',
                'name' => $path,
                'owner' => $val[self::FOLDER_OWNER],
                'perms' => '',
                'size' => -1,
                'type' => '**dir'
            );
        }

        return $out;
    }

    /**
     */
    public function gc($path, $secs = 345600)
    {
        $query = array(
            $this->_mdKey(self::PATH) => array(
                '$regex' => '^' . $this->_convertPath($path)
            ),
            'uploadDate' => array(
                '$lt' => new MongoDate(time() - $secs)
            )
        );

        try {
            $this->_files->remove($query);
        } catch (MongoException $e) {
            throw new Horde_Vfs_Exception($e);
        }
    }

    /**
     * Converts the path name from regular filesystem form to the internal
     * format needed to access the file in the database.
     *
     * Namely, we will treat '/' as a base directory as this is pretty much
     * the standard way to access base directories over most filesystems.
     *
     * @param string $path  A VFS path.
     *
     * @return string  The path with any surrouding slashes stripped off.
     */
    protected function _convertPath($path)
    {
        return trim($path, '/');
    }

    /**
     */
    protected function _getFile($path, $name)
    {
        $query = array(
            $this->_mdKey(self::FNAME) => $name,
            $this->_mdKey(self::PATH) => $this->_convertPath($path)
        );

        try {
            return $this->_files->findOne($query);
        } catch (MongoException $e) {
            throw new Horde_Vfs_Exception($e);
        }
    }

    /**
     */
    protected function _mdKey($key)
    {
        return self::MD . '.' . $key;
    }

}

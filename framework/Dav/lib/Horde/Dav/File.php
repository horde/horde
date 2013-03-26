<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Dav
 */

use \Sabre\DAV;

/**
 * A file object.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Dav
 */
class Horde_Dav_File extends Sabre\DAV\File
{
    /**
     * A registry object.
     *
     * @var Horde_Registry
     */
    protected $_registry;

    /**
     * The path to the current file.
     *
     * @var string
     */
    protected $_path;

    /**
     * File details.
     *
     * @var array
     */
    protected $_item;

    /**
     * Constructor.
     *
     * @param Horde_Registry $registry  A registry object.
     * @param string $path              The path to this file.
     * @param array $item               File details.
     */
    public function __construct(Horde_Registry $registry, $path = null,
                                array $item = array())
    {
        $this->_registry = $registry;
        $this->_path = $path;
        $this->_item = $item;
    }

    /**
     * Returns the name of the node.
     *
     * This is used to generate the url.
     *
     * @return string
     */
    public function getName()
    {
        list($dir, $base) = DAV\URLUtil::splitPath($this->_path);
        return $base;
    }

    /**
     * Returns the last modification time, as a unix timestamp
     *
     * @return int
     */
    public function getLastModified()
    {
        if (!empty($this->_item['modified'])) {
            return $this->_item['modified'];
        }
        if (!empty($this->_item['created'])) {
            return $this->_item['created'];
        }
        return parent::getLastModified();
    }

    /**
     * Returns the data
     *
     * This method may either return a string or a readable stream resource
     *
     * @return mixed
     */
    public function get()
    {
        list($base) = explode('/', $this->_path);
        try {
          $items = $this->_registry->callByPackage($base, 'browse', array('path' => $this->_path));
        } catch (Horde_Exception_NotFound $e) {
            throw new DAV\Exception\NotFound($this->_path . ' not found');
        } catch (Horde_Exception $e) {
            throw new DAV\Exception($e);
        }

        if (!$items) {
            throw new DAV\Exception\NotFound($this->_path . ' not found');
        }

        return reset($items);
    }

    /**
     * Returns the size of the file, in bytes.
     *
     * @return int
     */
    public function getSize()
    {
        return isset($this->_item['contentlength'])
            ? $this->_item['contentlength']
            : null;
    }

    /**
     * Returns the mime-type for a file
     *
     * If null is returned, we'll assume application/octet-stream
     *
     * @return string|null
     */
    public function getContentType()
    {
        return isset($this->_item['contenttype'])
            ? $this->_item['contenttype']
            : null;
    }
}

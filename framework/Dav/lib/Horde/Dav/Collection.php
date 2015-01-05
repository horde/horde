<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Dav
 */

use Sabre\DAV;
use Sabre\DAVACL;
use Sabre\CalDAV;

/**
 * A collection (directory) object.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Dav
 */
class Horde_Dav_Collection extends DAV\Collection implements DAV\IProperties
{
    /**
     * The path to the current collection.
     *
     * @var string
     */
    protected $_path;

    /**
     * Collection details.
     *
     * @var array
     */
    protected $_item;

    /**
     * A registry object.
     *
     * @var Horde_Registry
     */
    protected $_registry;

    /**
     * The path to a MIME magic database.
     *
     * @var string
     */
    protected $_mimedb;

    /**
     * Mapping of WebDAV property names to Horde API's browse() properties.
     *
     * @var array
     */
    protected static $_propertyMap = array(
        '{DAV:}getcontentlength'            => 'contentlength',
        '{DAV:}getcontenttype'              => 'contentype',
        '{DAV:}getetag'                     => 'etag',
        '{DAV:}owner'                       => 'owner',
        '{http://sabredav.org/ns}read-only' => 'read-only',
    );

    /**
     * Constructor.
     *
     * @param string $path              The path to this collection.
     * @param array $item               Collection details.
     * @param Horde_Registry $registry  A registry object.
     * @param string $mimedb            Location of a MIME magic database.
     */
    public function __construct($path = null,
                                array $item = array(),
                                Horde_Registry $registry,
                                $mimedb)
    {
        $this->_path = $path;
        $this->_item = $item;
        $this->_registry = $registry;
        $this->_mimedb = $mimedb;
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
     * Returns an array with all the child nodes
     *
     * @return DAV\INode[]
     */
    public function getChildren()
    {
        list($app) = explode('/', $this->_path);
        try {
            $items = $this->_registry->callByPackage(
                $app,
                'browse',
                array(
                    'path' => $this->_path,
                    'properties' => array(
                        'name', 'browseable', 'contenttype', 'contentlength',
                        'created', 'modified', 'etag', 'owner', 'read-only',
                        'displayname'
                    )
                )
            );
        } catch (Horde_Exception $e) {
            throw new DAV\Exception($e);
        }

        if ($items === false) {
            throw new DAV\Exception\NotFound($this->_path . ' not found');
        }

        if (empty($items)) {
            // No content exists at this level.
            return array();
        }

        /* A directory full of objects has been returned. */
        $list = array();
        foreach ($items as $path => $item) {
            if ($item['browseable']) {
                $list[] = new Horde_Dav_Collection(
                    $path,
                    $item,
                    $this->_registry,
                    $this->_mimedb
                );
            } else {
                $list[] = new Horde_Dav_File($this->_registry, $path, $item);
            }
        }
        return $list;
    }

    /**
     * Creates a new file in the directory
     *
     * @param string $name Name of the file
     * @param resource|string $data Initial payload
     * @return null|string
     */
    public function createFile($name, $data = null)
    {
        list($app) = explode('/', $this->_path);
        if (is_resource($data)) {
            rewind($data);
            $content = stream_get_contents($data);
            $type = Horde_Mime_Magic::analyzeData(
                $content, $this->_mimedb
            );
        } else {
            $content = $data;
            $type = Horde_Mime_Magic::analyzeData($content, $this->_mimedb);
        }
        if (!$type) {
            $type = Horde_Mime_Magic::filenameToMime($name);
        }

        try {
            $this->_registry->callByPackage(
                $app,
                'put',
                array(
                    $this->_path . '/' . $name,
                    $content,
                    $type
                )
            );
        } catch (Horde_Exception $e) {
            throw new DAV\Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Updates properties on this node.
     *
     * @param array $mutations
     * @return bool|array
     */
    public function updateProperties($mutations)
    {
        return false;
    }

    /**
     * Returns a list of properties for this nodes.
     *
     * @param array $properties
     * @return void
     */
    public function getProperties($properties)
    {
        $response = array();
        foreach (self::$_propertyMap as $property => $apiProperty) {
            if (isset($this->_item[$apiProperty])) {
                $response[$property] = $this->_item[$apiProperty];
            }
        }
        if (isset($this->_item['modified'])) {
            $response['{DAV:}getlastmodified'] = new DAV\Property\GetLastModified(
                $this->_item['modified']
            );
        }
        if (isset($this->_item['displayname'])) {
            $response['{DAV:}displayname'] = $this->_item['displayname'];
        } elseif (isset($this->_item['name'])) {
            $response['{DAV:}displayname'] = $this->_item['name'];
        }
        return $response;
    }
}

<?php
/**
 * The base driver definition for accessing Kolab storage drivers.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * The base driver definition for accessing Kolab storage drivers.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
abstract class Horde_Kolab_Storage_Driver_Base
implements Horde_Kolab_Storage_Driver
{
    /**
     * Factory for generating helper objects.
     *
     * @var Horde_Kolab_Storage_Factory
     */
    private $_factory;

    /**
     * Additional connection parameters.
     *
     * @var array
     */
    private $_params;

    /**
     * Memory cache for the namespace of this driver.
     *
     * @var Horde_Kolab_Storage_Folder_Namespace
     */
    protected $_namespace;

    /**
     * The backend to use.
     *
     * @var mixed
     */
    private $_backend;

    /**
     * Charset used by this driver.
     *
     * @var string
     */
    protected $charset = 'UTF7-IMAP';

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Factory $factory A factory for helper objects.
     * @param array $params                        Connection parameters.
     */
    public function __construct(Horde_Kolab_Storage_Factory $factory,
                                $params = array())
    {
        $this->_factory = $factory;
        if (isset($params['backend'])) {
            $this->setBackend($params['backend']);
        }
        $this->_params  = $params;
    }

    /**
     * Returns the actual backend driver.
     *
     * If there is no driver set the driver should be constructed within this
     * method.
     *
     * @return mixed The backend driver.
     */
    public function getBackend()
    {
        if ($this->_backend === null) {
            $this->_backend = $this->createBackend();
        }
        return $this->_backend;
    }

    /**
     * Set the backend driver.
     *
     * @param mixed $backend The driver that should be used.
     *
     * @return NULL
     */
    public function setBackend($backend)
    {
        $this->_backend = $backend;
    }

    /**
     * Return all parameter settings for this connection.
     *
     * @return array The parameters.
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * Return a parameter setting for this connection.
     *
     * @param string $key     The parameter key.
     * @param mixed  $default An optional default value.
     *
     * @return mixed The parameter value.
     */
    public function getParam($key, $default = null)
    {
        return isset($this->_params[$key]) ? $this->_params[$key] : $default;
    }

    /**
     * Set a parameter setting for this connection.
     *
     * @param string $key    The parameter key.
     * @param mixed  $value  The parameter value.
     *
     * @return NULL
     */
    public function setParam($key, $value)
    {
        $this->_params[$key] = $value;
    }

    /**
     * Return the id of the user currently authenticated.
     *
     * @return string The id of the user that opened the IMAP connection.
     */
    public function getAuth()
    {
        return $this->getParam('username');
    }

    /**
     * Return the unique connection id.
     *
     * @return string The connection id.
     */
    public function getId()
    {
        return $this->getAuth() . '@'
            . $this->getParam('host') . ':'
            . $this->getParam('port');
    }

    /**
     * Return the connection parameters.
     *
     * @return array The connection parameters.
     */
    public function getParameters()
    {
        return array(
            'user' => $this->getAuth(),
            'host' => $this->getParam('host'),
            'port' => $this->getParam('port')
        );
    }

    /**
     * Return the factory.
     *
     * @return Horde_Kolab_Storage_Factory The factory.
     */
    protected function getFactory()
    {
        return $this->_factory;
    }

    /**
     * Encode IMAP path names from  UTF-8 to the driver charset.
     *
     * @param string $path The UTF-8 encoded path name.
     *
     * @return string The path name in the driver charset.
     */
    protected function encodePath($path)
    {
        return Horde_String::convertCharset($path, 'UTF-8', $this->charset);
    }

    /**
     * Decode IMAP path names from the driver charset to UTF-8.
     *
     * @param string $path The the driver charset encoded path name.
     *
     * @return string The path name in UTF-8.
     */
    protected function decodePath($path)
    {
        return Horde_String::convertCharset($path,  $this->charset, 'UTF-8');
    }

    /**
     * Decode a list of IMAP path names from the driver charset to UTF-8.
     *
     * @param array $list The the driver charset encoded path names.
     *
     * @return array The path names in UTF-8.
     */
    protected function decodeList(array $list)
    {
        return array_map(array($this, 'decodePath'), $list);
    }

    /**
     * Decode the keys of a list of IMAP path names from the driver charset to
     * UTF-8.
     *
     * @param array $list The list with the driver charset encoded path names as
     *                    keys.
     *
     * @return array The list with path names in UTF-8 as keys.
     */
    protected function decodeListKeys(array $list)
    {
        $result = array();
        foreach ($list as $key => $value) {
            $result[$this->decodePath($key)] = $value;
        }
        return $result;
    }

    /**
     * Checks if the backend supports CATENATE.
     *
     * @return boolean True if the backend supports CATENATE.
     */
    public function hasCatenateSupport()
    {
        return false;
    }

    /**
     * Return a modifiable message object.
     *
     * @param string $folder The folder to access.
     * @param string $obid   The backend ID of the object to retrieve from the folder.
     * @param array  $object The object data.
     *
     * @return Horde_Kolab_Storage_Driver_Modifiable The modifiable message object.
     */
    public function getModifiable($folder, $obid, $object)
    {
        return new Horde_Kolab_Storage_Data_Modifiable(
            $this, $folder, $this->fetchComplete($folder, $obid)
        );
    }

    /**
     * Retrieve the namespace information for this connection.
     *
     * @return Horde_Kolab_Storage_Folder_Namespace The initialized namespace handler.
     */
    public function getNamespace()
    {
        if ($this->_namespace === null) {
            if (isset($this->_params['namespaces'])) {
                $this->_namespace = $this->_factory->createNamespace(
                    'config', $this->getAuth(), $this->_params['namespaces']
                );
            } else {
                $this->_namespace = $this->_factory->createNamespace(
                    'fixed', $this->getAuth()
                );
            }
        }
        return $this->_namespace;
    }

    /**
     * Returns a stamp for the current folder status. This stamp can be used to
     * identify changes in the folder data.
     *
     * @param string $folder Return the stamp for this folder.
     *
     * @return Horde_Kolab_Storage_Folder_Stamp A stamp indicating the current
     *                                          folder status.
     */
    public function getStamp($folder)
    {
        return new Horde_Kolab_Storage_Folder_Stamp_Uids(
            $this->status($folder),
            $this->getUids($folder)
        );
    }

    /**
     * Retrieves the messages for the given message ids.
     *
     * @param string $mailbox The mailbox to fetch the messages from.
     * @param array  $uids    The message UIDs.
     *
     * @return Horde_Mime_Part The message structure parsed into a
     *                         Horde_Mime_Part instance.
     */
    public function fetchStructure($mailbox, $uids)
    {
        throw new Horde_Kolab_Storage_Exception('"fetchStructure() not supported by this driver!');
    }

    /**
     * Retrieves a bodypart for the given message ID and mime part ID.
     *
     * @param string $mailbox The mailbox to fetch the messages from.
     * @param array  $uid     The message UID.
     * @param array  $id      The mime part ID.
     *
     * @return resource  The body part, in a stream resource.
     */
    public function fetchBodypart($mailbox, $uid, $id)
    {
        throw new Horde_Kolab_Storage_Exception('"fetchBodypart() not supported by this driver!');
    }

    /**
     * Retrieves a complete message.
     *
     * @param string $folder The folder to fetch the messages from.
     * @param array  $uid    The message UID.
     *
     * @return array The message encapsuled as an array that contains a
     *               Horde_Mime_Headers and a Horde_Mime_Part object.
     */
    public function fetchComplete($folder, $uid)
    {
        throw new Horde_Kolab_Storage_Exception('"fetchComplete() not supported by this driver!');
    }

    /**
     * Retrieves the message headers.
     *
     * @param string $folder The folder to fetch the message from.
     * @param array  $uid    The message UID.
     *
     * @return Horde_Mime_Headers The message headers.
     */
    public function fetchHeaders($folder, $uid)
    {
        $result = $this->fetchComplete($folder, $uid);
        return $result[0];
    }

    /**
     * Split a name for the METADATA extension into the correct syntax for the
     * older ANNOTATEMORE version.
     *
     * @param string $name  A name for a metadata entry.
     *
     * @return array  A list of two elements: The entry name and the value
     *                type.
     * @throws Horde_Imap_Client_Exception
     */
    protected function _getAnnotateMoreEntry($name)
    {
        if (substr($name, 0, 7) == '/shared') {
            return array(substr($name, 7), 'value.shared');
        } else if (substr($name, 0, 8) == '/private') {
            return array(substr($name, 8), 'value.priv');
        }

        $this->_exception('Invalid METADATA entry: ' . $name);
    }

}
<?php

abstract class Horde_Kolab_Storage_Driver_Namespace_Element
{
    /**
     * The prefix identifying this namespace.
     *
     * @var string
     */
    protected $_name;

    /**
     * The delimiter used for this namespace.
     *
     * @var string
     */
    protected $_delimiter;

    /**
     * Constructor.
     *
     * @param string $name      The prefix identifying this namespace.
     * @param string $delimiter The delimiter used for this namespace.
     */
    public function __construct($name, $delimiter)
    {
        if (substr($name, -1) == $delimiter) {
            $name = substr($name, 0, -1);
        }
        $this->_name = $name;
        $this->_delimiter = $delimiter;
    }

    /**
     * Return the type of this namespace (personal, other, or shared).
     *
     * @return string The type.
     */
    abstract public function getType();

    /**
     * Return the name of this namespace.
     *
     * @return string The name/prefix.
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Return the delimiter for this namespace.
     *
     * @return string The delimiter.
     */
    public function getDelimiter()
    {
        return $this->_delimiter;
    }

    /**
     * Does the folder name lie in this namespace?
     *
     * @param string $name The name of the folder.
     *
     * @return boolean True if the folder is element of this namespace.
     */
    public function matches($name)
    {
        return (strpos($name, $this->_name) === 0);
    }

    /**
     * Return the owner of a folder.
     *
     * @param string $name The name of the folder.
     *
     * @return string The owner of the folder.
     */
    abstract public function getOwner($name);

    /**
     * Return the title of a folder.
     *
     * @param string $name The name of the folder.
     *
     * @return string The title of the folder.
     */
    public function getTitle($name)
    {
        return join($this->_subpath($name), ':');
    }

    /**
     * Get the sub path for the given folder name.
     *
     * @param string $name The folder name.
     *
     * @return string The sub path.
     */
    public function getSubpath($name)
    {
        return join($this->_subpath($name), $this->_delimiter);
    }

    /**
     * Return an array describing the path elements of the folder.
     *
     * @param string $name The name of the folder.
     *
     * @return array The path elements.
     */
    protected function _subpath($name)
    {
        $path = explode($this->_delimiter, $name);
        if ($path[0] == $this->_name) {
            array_shift($path);
        }
        //@todo: What about the potential trailing domain?
        return $path;
    }

    /**
     * Generate a folder path for the given path in this namespace.
     *
     * @param array $path The path of the folder.
     *
     * @return string The name of the folder.
     */
    public function generateName($path)
    {
        return join($path, $this->_delimiter);
    }

}
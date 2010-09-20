<?php
/**
 * This class provides the framework for a search query element.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
abstract class IMP_Search_Element implements Serializable
{
    /* Serialized version. */
    const VERSION = 1;

    /**
     * Allow NOT search on this element?
     *
     * @var boolean
     */
    public $not = true;

    /**
     * Data for this element.
     *
     * @var object
     */
    protected $_data;

    /**
     * Adds the current query item to the query object.
     *
     * @param Horde_Imap_Client_Search_Query  The query object.
     */
    abstract public function createQuery($queryob);

    /**
     * Return search query text representation.
     *
     * @return array  The textual description of this search element.
     */
    abstract public function queryText();

    /**
     * Returns the criteria data for the element.
     *
     * @return object  The criteria (see each class for the available
     *                 properties).
     */
    public function getCriteria()
    {
        return $this->_data;
    }

    /* Serializable methods. */

    /**
     * Serialization.
     *
     * @return string  Serialized data.
     */
    public function serialize()
    {
        return empty($this->_data)
            ? null
            : json_encode(array(
                  self::VERSION,
                  $this->_data
              ));
    }

    /**
     * Unserialization.
     *
     * @param string $data  Serialized data.
     *
     * @throws Exception
     */
    public function unserialize($data)
    {
        if (empty($data)) {
            return;
        }

        $data = json_decode($data);
        if (!is_array($data) ||
            !isset($data[0]) ||
            ($data[0] != self::VERSION)) {
            throw new Exception('Cache version change');
        }

        $this->_data = $data[1];
    }

}

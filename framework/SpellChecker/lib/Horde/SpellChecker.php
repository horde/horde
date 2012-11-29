<?php
/**
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2005-2012 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   SpellChecker
 */

/**
 * Provides a unified spellchecker API.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2005-2012 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   SpellChecker
 */
abstract class Horde_SpellChecker
{
    const SUGGEST_FAST = 1;
    const SUGGEST_NORMAL = 2;
    const SUGGEST_SLOW = 3;

    /**
     * Configuration parameters.
     *
     * @var array
     */
    protected $_params = array(
        'html' => false,
        'locale' => 'en',
        'localDict' => array(),
        'maxSuggestions' => 10,
        'minLength' => 3,
        'suggestMode' => self::SUGGEST_FAST
    );

    /**
     * Attempts to return a concrete Horde_SpellChecker instance based on
     * $driver.
     *
     * @param string $driver  The type of concrete subclass to return.
     * @param array $params   A hash containing any additional configuration
     *                        or connection parameters a subclass might need.
     *
     * @return Horde_SpellChecker  The newly created instance.
     * @throws Horde_Exception
     */
    static public function factory($driver, $params = array())
    {
        $class = 'Horde_SpellChecker_' . Horde_String::ucfirst(basename($driver));
        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Exception('Driver ' . $driver . ' not found');
    }

    /**
     * Constructor.
     *
     * @param array $params  TODO
     */
    public function __construct(array $params = array())
    {
        $this->setParams($params);
    }

    /**
     * Set configuration parmeters.
     *
     * @param array $params  Parameters to set.
     */
    public function setParams($params)
    {
        $this->_params = array_merge($this->_params, $params);
    }

    /**
     * Perform spellcheck.
     *
     * @param string $text  Text to spellcheck.
     *
     * @return array  TODO
     * @throws Horde_SpellChecker_Exception
     */
    abstract public function spellCheck($text);

    /**
     * TODO
     *
     * @param string $text  TODO
     *
     * @return array  TODO
     */
    protected function _getWords($text)
    {
        return array_keys(array_flip(preg_split('/[\s\[\]]+/s', $text, -1, PREG_SPLIT_NO_EMPTY)));
    }

    /**
     * Determine if a word exists in the local dictionary.
     *
     * @param string $word  The word to check.
     *
     * @return boolean  True if the word appears in the local dictionary.
     */
    protected function _inLocalDictionary($word)
    {
        return empty($this->_params['localDict'])
            ? false
            : in_array(Horde_String::lower($word, true, 'UTF-8'), $this->_params['localDict']);
    }

}

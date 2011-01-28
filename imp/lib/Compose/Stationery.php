<?php
/**
 * This class provides access to IMP stationery data.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Compose_Stationery implements ArrayAccess, Countable, Iterator
{
    /**
     * Stationery list.
     * Each entry has the following properties:
     * <pre>
     * 'c' => (string) Content.
     * 'n' => (string) Name.
     * 't' => (string) Type.
     * </pre>
     *
     * @var array
     */
    protected $_stationery;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $slist = @unserialize($GLOBALS['prefs']->getValue('stationery'));
        $this->_stationery = is_array($slist)
            ? $slist
            : array();
    }

    /**
     * Substitute variables in stationery content.
     *
     * @param integer $id                   The stationery ID.
     * @param IMP_Prefs_Identity $identity  The identity object.
     * @param string $msg                   The message text.
     * @param boolean $html                 Output HTML code?
     *
     * @return string  Stationery content.
     */
    public function getContent($id, IMP_Prefs_Identity $identity, $msg,
                               $html = false)
    {
        $s_content = $this[$id]['c'];

        if (strpos($s_content, '%s') !== false) {
            $sig = $identity->getSignature($html ? 'html' : 'text');

            switch ($this[$id]['t']) {
            case 'html':
                if (!$html) {
                    $s_content = $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($s_content, 'Html2text', array('charset' => 'UTF-8'));
                }
                break;

            case 'text':
                if ($html) {
                    $s_content = IMP_Compose::text2html($s_content);
                }
                break;
            }

            $msg = str_replace(array("\r\n", $sig), array("\n", ''), $msg);
            $s_content = str_replace('%s', $sig, $s_content);
        }

        return (strpos($s_content, '%c') === false)
            ? $s_content
            : str_replace('%c', $msg, $s_content);
    }

    /**
     * Save the current stationery list to preferences.
     */
    protected function _save()
    {
        $GLOBALS['prefs']->setValue('stationery', serialize($this->_stationery));
    }

    /* ArrayAccess methods. */

    public function offsetExists($offset)
    {
        return isset($this->_stationery[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->_stationery[$offset])
            ? $this->_stationery[$offset]
            : null;
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->_stationery[] = $value;
        } else {
            $this->_stationery[$offset] = $value;
        }

        $this->_save();
    }

    /* NOTE: this function reindexes the internal array. */
    public function offsetUnset($offset)
    {
        if (isset($this->_stationery[$offset])) {
            unset($this->_stationery[$offset]);
            $this->_stationery = array_values($this->_stationery);
            $this->_save();
        }
    }

    /* Countable methods. */

    public function count()
    {
        return count($this->_stationery);
    }

    /* Iterator methods. */

    public function current()
    {
        return current($this->_stationery);
    }

    public function key()
    {
        return key($this->_stationery);
    }

    public function next()
    {
        next($this->_stationery);
    }

    public function rewind()
    {
        reset($this->_stationery);
    }

    public function valid()
    {
        return (key($this->_stationery) !== null);
    }

}

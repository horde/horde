<?php
/**
 * This class provides the data structure for a user-defined message flag.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Flag_User extends IMP_Flag_Imap
{
    /**
     */
    protected $_canset = true;

    /**
     */
    protected $_css = 'flagUser';

    /**
     * The flag label.
     *
     * @var string
     */
    protected $_label;

    /**
     * Constructor.
     *
     * @param string $label    The label.
     * @param string $flag     The IMAP flag.
     * @param string $bgcolor  The background color.
     */
    public function __construct($label, $flag = null, $bgcolor = null)
    {
        $this->label = $label;
        $this->imapflag = is_null($flag)
            ? $label
            : $flag;
        if (isset($bgcolor)) {
            $this->bgcolor = $bgcolor;
        }
    }

    /**
     */
    public function __set($name, $value)
    {
        switch ($name) {
        case 'imapflag':
            /* IMAP keywords must conform to RFC 3501 [9] (flag-keyword).
             * Convert whitespace to underscore. */
            $atom = new Horde_Imap_Client_Data_Format_Atom(strtr($value, ' ', '_'));
            $this->_imapflag = $atom->stripNonAtomCharacters();
            break;

        case 'label':
            $this->_label = $value;
            break;

        default:
            parent::__set($name, $value);
            break;
        }
    }

    /**
     */
    protected function _getLabel()
    {
        return $this->_label;
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        return json_encode(array(
            parent::serialize(),
            $this->_label,
            $this->_imapflag
        ));
    }

    /**
     */
    public function unserialize($data)
    {
        $data = json_decode($data, true);

        parent::unserialize($data[0]);
        $this->_label = $data[1];
        $this->_imapflag = $data[2];
    }

}

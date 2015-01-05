<?php
/**
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * This class provides the data structure for a user-defined message flag.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
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
     * @throws IMP_Exception
     */
    public function __set($name, $value)
    {
        switch ($name) {
        case 'imapflag':
            /* IMAP keywords must conform to RFC 3501 [9] (flag-keyword). */
            $atom = new Horde_Imap_Client_Data_Format_Atom(
                /* 2: Convert whitespace to underscore. */
                strtr(
                    /* 1: Do UTF-8 -> ASCII transliteration. */
                    Horde_String_Transliterate::toAscii($value),
                    ' ',
                    '_'
                )
            );

            /* 3: Remove all non-atom characters. */
            $imapflag = $atom->stripNonAtomCharacters();

            /* 4: If string is empty (i.e. it contained all non-ASCII
             * characters that could not be converted), save the hashed value
             * of original string as flag. */
            if (!strlen($imapflag)) {
                $imapflag = hash(
                    (PHP_MINOR_VERSION >= 4) ? 'fnv132' : 'sha1',
                    $value
                );
            }

            $this->_imapflag = $imapflag;
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

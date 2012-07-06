<?php
/**
 * This class represents thread information for a single message.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 *
 * @property string $img  An image HTML tag of the thread.
 * @property string $raw  The raw thread data.
 * @property string $reverse_img  An image HTML tag of the thread (reversed).
 * @property string $reverse_raw  The raw thread data (reversed).
 */
class IMP_Mailbox_List_Thread
{
    /* Thread level representations. */
    const BLANK = 0;
    const LINE = 1;
    const JOIN = 2;
    const JOINBOTTOM_DOWN = 3;
    const JOINBOTTOM = 4;

    /**
     * Thread information.
     *
     * @var string
     */
    protected $_data;

    /**
     * Constructor.
     *
     * @param string $data  The thread information.
     */
    public function __construct($data)
    {
        $this->_data = $data;
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'reverse_img':
        case 'reverse_raw':
            $ret = strtr($this->_data, array(
                self::JOINBOTTOM_DOWN => self::JOINBOTTOM,
                self::JOINBOTTOM => self::JOINBOTTOM_DOWN
            ));
            break;

        default:
            $ret = $this->_data;
            break;
        }

        switch ($name) {
        case 'img':
        case 'reverse_img':
            $tmp = '';
            if (strlen($ret)) {
                foreach (str_split($ret) as $val) {
                    $tmp .= '<span class="treeImg treeImg' . $val . '"></span>';
                }
            }
            return $tmp;

        case 'raw':
        case 'reverse_raw':
            return $ret;
        }
    }

}

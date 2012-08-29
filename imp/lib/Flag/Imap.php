<?php
/**
 * This class provides the data structure for a message flag.
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
abstract class IMP_Flag_Imap extends IMP_Flag_Base
{
    /**
     * The IMAP flag string used on the server.
     *
     * @var string
     */
    protected $_imapflag;

    /**
     * @param string $name  Additional properties:
     *   - imapflag: (string) The IMAP flag string.
     */
    public function __get($name)
    {
        switch ($name) {
        case 'id':
        case 'imapflag':
            return $this->_imapflag;

        default:
            return parent::__get($name);
        }
    }

    /**
     * @param array $data  List of IMAP flags.
     */
    public function match($data)
    {
        foreach ($data as $val) {
            if (strcasecmp($this->imapflag, $val) === 0) {
                return true;
            }
        }

        return false;
    }

}

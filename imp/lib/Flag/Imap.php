<?php
/**
 * This class provides the data structure for a message flag.
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
     * <pre>
     * 'imapflag' - (string) The IMAP flag string.
     * </pre>
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
     * @param array $input  List of IMAP flags.
     */
    public function match($data)
    {
        return in_array($this->imapflag, $data);
    }

}

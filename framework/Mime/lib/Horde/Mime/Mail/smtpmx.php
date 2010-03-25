<?php
/**
 * Extends Smtpmx Mail driver by allowing unaltered headers to be sent.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Mime
 */
class Mail_horde_mime_smtpmx extends Mail_smtpmx
{
    /**
     * The From address to use.
     *
     * @var string
     */
    protected $_from;

    /**
     * The raw headertext to use.
     *
     * @var string
     */
    protected $_headertext;

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters:
     * <pre>
     * 'from' - (string) The From address to use.
     * 'headertext' - (string) The raw headertext to use.
     * </pre>
     */
    public function __construct($params)
    {
        $this->_from = $params['from'];
        $this->_headertext = $params['headertext'];
        unset($params['from'], $params['headertext']);

        parent::__construct($params);
    }

    /**
     * Prepare headers for sending.
     *
     * @param array $headers  The headers array (not used).
     *
     * @return array  2 elements: the from address and the header text.
     */
    public function prepareHeaders($headers)
    {
        return array($this->_from, $this->_headertext);
    }

}

<?php
/**
 * Copyright 2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Log entry that references a sent-mail action.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 *
 * @property-read string $msg_id  Message-ID of the message sent.
 */
abstract class IMP_Maillog_Log_Sentmail
extends IMP_Maillog_Log_Base
{
    /**
     * Message ID.
     *
     * @var string
     */
    protected $_msgId;

    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     *   - msgid: (string) Message ID of the message sent.
     */
    public function __construct(array $params = array())
    {
        if (isset($params['msgid'])) {
            $this->_msgId = strval($params['msgid']);
        }
        parent::__construct($params);
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'msg_id':
            return $this->_msgId;
        }

        return parent::__get($name);
    }

    /**
     * Add entry specific data to the backend storage.
     *
     * @return array  An array of key -> value pairs to add.
     */
    public function addData()
    {
        return array_merge(parent::addData(), array_filter(array(
            'msgid' => $this->msg_id
        )));
    }

}

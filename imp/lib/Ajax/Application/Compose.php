<?php
/**
 * Compose view utilities for AJAX data.
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
 */
class IMP_Ajax_Application_Compose
{
    /**
     * Forward mapping of id -> compose object constants.
     *
     * @var array
     */
    public $forward_map = array(
        'editasnew' => IMP_Compose::EDITASNEW,
        'forward_attach' => IMP_Compose::FORWARD_ATTACH,
        'forward_auto' => IMP_Compose::FORWARD_AUTO,
        'forward_body' => IMP_Compose::FORWARD_BODY,
        'forward_both' => IMP_Compose::FORWARD_BOTH
    );

    /**
     * Reply mapping of id -> compose object constant.
     *
     * @var array
     */
    public $reply_map = array(
        'reply' => IMP_Compose::REPLY_SENDER,
        'reply_all' => IMP_Compose::REPLY_ALL,
        'reply_auto' => IMP_Compose::REPLY_AUTO,
        'reply_list' => IMP_Compose::REPLY_LIST
    );

    /**
     * @var IMP_Compose
     */
    protected $_compose;

    /**
     * @var string
     */
    protected $_type;

    /**
     * @param IMP_Compose $ob
     * @param string $type
     */
    public function __construct(IMP_Compose $ob, $type = null)
    {
        $this->_composeOb = $ob;
        $this->_type = $type;
    }

    /**
     */
    public function getResponse($result)
    {
        $ob = $this->getBaseResponse();

        $ob->body = $result['body'];
        $ob->format = $result['format'];
        $ob->header = $result['headers'];
        $ob->identity = $result['identity'];

        if ($search = array_search($result['type'], $this->reply_map)) {
            if ($this->_type == 'reply_auto') {
                $ob->opts->auto = $search;

                if (isset($result['reply_list_id'])) {
                    $ob->opts->reply_list_id = $result['reply_list_id'];
                }
                if (isset($result['reply_recip'])) {
                    $ob->opts->reply_recip = $result['reply_recip'];
                }
            }

            if (!empty($result['lang'])) {
                $ob->opts->reply_lang = array_values($result['lang']);
            }

            $ob->opts->focus = 'composeMessage';
        } elseif ($search = array_search($result['type'], $this->forward_map)) {
            if ($this->_type == 'forward_auto') {
                $ob->opts->auto = $search;
            }
        } else {
            $ob->opts->priority = $result['priority'];
            $ob->opts->readreceipt = $result['readreceipt'];
        }

        if ($atc = $this->getAttachmentInfo($result['type'])) {
            $ob->opts->atc = $atc;
        }

        return $ob;
    }

    /**
     */
    public function getBaseResponse()
    {
        $ob = new stdClass;
        $ob->body = '';
        $ob->header = array();
        $ob->imp_compose = $this->_composeOb->getCacheId();
        $ob->opts = new stdClass;
        $ob->type = $this->_type;

        return $ob;
    }

    /**
     * Return information about the current attachments for a message.
     *
     * @param integer $type  The compose type.
     *
     * @return array  An array of arrays with the following keys:
     *   - fwdattach: (integer) If non-zero, this is a forward attachment
     *   - name: (string) The attachment name
     *   - num: (integer) The current attachment number
     *   - size: (string) The size of the attachment in KB
     *   - type: (string) The MIME type of the attachment
     */
    public function getAttachmentInfo($type = IMP_Compose::COMPOSE)
    {
        $atc = array();

        foreach ($this->_composeOb as $atc_num => $data) {
            $mime = $data['part'];

            $atc[] = array(
                'fwdattach' => intval(in_array($type, array(IMP_Compose::FORWARD_ATTACH, IMP_Compose::FORWARD_BOTH))),
                'name' => $mime->getName(true),
                'num' => $atc_num,
                'type' => $mime->getType(),
                'size' => $mime->getSize()
            );
        }

        return $atc;
    }


}

<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mime
 */

/**
 * Deprecated Horde_Mime_Headers methods.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @deprecated
 * @category   Horde
 * @copyright  2014 Horde LLC
 * @internal
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @since      2.5.0
 */
class Horde_Mime_Headers_Deprecated
{
    /**
     * Base headers object.
     *
     * @var Horde_Mime_Headers
     */
    private $_headers;

    /**
     */
    public function __construct(Horde_Mime_Headers $headers)
    {
        $this->_headers = $headers;
    }

    /**
     */
    public function addMessageIdHeader()
    {
        $this->_headers->addHeaderOb(Horde_Mime_Headers_MessageId::create());
    }

    /**
     */
    public function addUserAgentHeader()
    {
        $this->_headers->addHeaderOb(Horde_Mime_Headers_UserAgent::create());
    }

    /**
     */
    public function getUserAgent()
    {
        return strval(Horde_Mime_Headers_UserAgent::create());
    }

    /**
     */
    public function setUserAgent($agent)
    {
        $this->_headers->addHeaderOb(
            new Horde_Mime_Headers_UserAgent(null, $agent)
        );
    }

    /**
     */
    public function addReceivedHeader(array $opts = array())
    {
        $this->_headers->addHeaderOb(
            Horde_Mime_Headers_Received::createHordeHop($opts)
        );
    }

    /**
     */
    public function getOb($field)
    {
        return ($h = $this->_headers[$field])
            ? $h->getAddressList(true)
            : null;
    }

    /**
     */
    public function getValue($header, $type = Horde_Mime_Headers::VALUE_STRING)
    {
        if (!($ob = $this->_headers[$header])) {
            return null;
        }

        switch ($type) {
        case Horde_Mime_Headers::VALUE_BASE:
            $tmp = $ob->value;
            break;

        case Horde_Mime_Headers::VALUE_PARAMS:
            return array_change_key_case($ob->params, CASE_LOWER);

        case Horde_Mime_Headers::VALUE_STRING:
            $tmp = $ob->full_value;
            break;
        }

        return (is_array($tmp) && (count($tmp) === 1))
            ? reset($tmp)
            : $tmp;
    }

    /**
     */
    public function listHeaders()
    {
        $lhdrs = new Horde_ListHeaders();
        return $lhdrs->headers();
    }

    /**
     */
    public function listHeadersExist()
    {
        $lhdrs = new Horde_ListHeaders();
        return $lhdrs->listHeadersExist($this->_headers);
    }

    /**
     */
    public function replaceHeader($header, $value, array $opts = array())
    {
        $this->_headers->removeHeader($header);
        $this->_headers->addHeader($header, $value, $opts);
    }

    /**
     */
    public function getString($header)
    {
        return (($hdr = $this->_headers[$header]) === null)
            ? null
            : $this->_headers[$header]->name;
    }

    /**
     */
    public function addressFields()
    {
        return array(
            'from', 'to', 'cc', 'bcc', 'reply-to', 'resent-to', 'resent-cc',
            'resent-bcc', 'resent-from', 'sender'
        );
    }

    /**
     */
    public function singleFields($list = true)
    {
        $fields = array(
            'to', 'from', 'cc', 'bcc', 'date', 'sender', 'reply-to',
            'message-id', 'in-reply-to', 'references', 'subject',
            'content-md5', 'mime-version', 'content-type',
            'content-transfer-encoding', 'content-id', 'content-description',
            'content-base', 'content-disposition', 'content-duration',
            'content-location', 'content-features', 'content-language',
            'content-alternative', 'importance', 'x-priority'
        );

        $list_fields = array(
            'list-help', 'list-unsubscribe', 'list-subscribe', 'list-owner',
            'list-post', 'list-archive', 'list-id'
        );

        return $list
            ? array_merge($fields, $list_fields)
            : $fields;
    }

    /**
     */
    public function mimeParamFields()
    {
        return array('content-type', 'content-disposition');
    }

}

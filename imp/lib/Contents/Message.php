<?php
/**
 * Copyright 2005-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2005-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Prepare details for viewing a message.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2005-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Contents_Message
{
    /**
     * The list of headers used by this class.
     *
     * @var array
     */
    public static $headersUsed = array(
        'resent-date',
        'resent-from'
    );

    /**
     * Contents object.
     *
     * @var IMP_Contents
     */
    public $contents;

    /**
     * Cached values.
     *
     * @var array
     */
    protected $_cache = array();

    /**
     * Envelope object.
     *
     * @var Horde_Imap_Client_Data_Envelope
     */
    protected $_envelope;

    /**
     * Header information.
     *
     * @var Horde_Mime_Headers
     */
    protected $_headers;

    /**
     * Indices object.
     *
     * @var IMP_Indices
     */
    protected $_indices;

    /**
     * Default list of part info elements to display.
     *
     * @var array
     */
    public $part_info = array(
        'icon', 'description', 'size', 'download'
    );

    /**
     * Don't seen seen flag?
     *
     * @var boolean
     */
    protected $_peek;

    /**
     * Constructor.
     *
     * @param IMP_Indices $indices  The index of the message.
     * @param boolean $peek         Don't set seen flag?
     */
    public function __construct(IMP_Indices $indices, $peek = false)
    {
        global $injector;

        /* Get envelope/header information. We don't use flags in this
         * view. */
        try {
            list($mbox, $uid) = $indices->getSingle();
            if (!$uid) {
                throw new Exception();
            }

            $query = new Horde_Imap_Client_Fetch_Query();
            $query->envelope();
            $query->headers(
                'imp',
                self::$headersUsed,
                array(
                    'cache' => true,
                    'peek' => true
                )
            );

            $imp_imap = $mbox->imp_imap;
            $imp_imap->openMailbox($mbox, Horde_Imap_Client::OPEN_READWRITE);

            $ret = $imp_imap->fetch($mbox, $query, array(
                'ids' => $imp_imap->getIdsOb($uid)
            ));

            if (!($ob = $ret->first())) {
                throw new Exception();
            }

            $this->contents = $injector->getInstance('IMP_Factory_Contents')->create($indices);

            if (!$peek) {
                $this->_loadHeaders();
            }
        } catch (Exception $e) {
            throw new IMP_Exception(_("Requested message not found."));
        }

        $this->_envelope = $ob->getEnvelope();
        $this->_headers = $ob->getHeaders(
            'imp',
            Horde_Imap_Client_Data_Fetch::HEADER_PARSE
        );
        $this->_indices = $indices;
        $this->_peek = $peek;
    }

    /**
     * Create the object used to display the message.
     *
     * @return array  Array with the following keys:
     *   - atc: (object) Attachment information.
     *     - download: (string) The URL for the download all action.
     *     - label: (string) The attachment label.
     *     - list: (array) Attachment information.
     *   - md: (array) Metadata.
     *   - msgtext: (string) The text of the message.
     *   - onepart: (boolean) True if message only contains one part.
     *
     * @throws IMP_Exception
     */
    public function showMessage()
    {
        global $prefs, $registry, $session;

        $result = array();

        // Create message text and attachment list.
        $result['msgtext'] = '';
        $part_info = $this->part_info;
        $show_parts = $prefs->getValue('parts_display');

        /* Do MDN processing now. */
        switch ($registry->getView()) {
        case $registry::VIEW_DYNAMIC:
            if ($this->_indices->mdnCheck($this->_loadHeaders())) {
                $status = new IMP_Mime_Status(null, array(
                    _("The sender of this message is requesting notification from you when you have read this message."),
                    Horde::link('#', '', '', '', '', '', '', array('id' => 'send_mdn_link')) . _("Click to send the notification message.") . '</a>'
                ));
                $status->domid('sendMdnMessage');
                $result['msgtext'] .= strval($status);
            }
        }

        /* Build body text. This needs to be done before we build the
         * attachment list. */
        $session->close();
        $inlineout = $this->getInlineOutput();
        $session->start();

        $result['md'] = $inlineout['metadata'];
        $result['msgtext'] .= $inlineout['msgtext'];
        if ($inlineout['one_part']) {
            $result['onepart'] = true;
        }

        if (count($inlineout['atc_parts']) ||
            (($show_parts == 'all') && count($inlineout['display_ids']) > 2)) {
            $result['atc']['label'] = ($show_parts == 'all')
                ? _("Parts")
                : sprintf(ngettext("%d Attachment", "%d Attachments", count($inlineout['atc_parts'])), count($inlineout['atc_parts']));
            if (count($inlineout['atc_parts']) > 1) {
                $result['atc']['download'] = strval($this->contents->urlView(
                    $this->contents->getMIMEMessage(),
                    'download_all'
                )->setRaw(true));
            }
        }

        /* Show attachment information in headers? */
        if (!empty($inlineout['atc_parts'])) {
            $partlist = array();

            if ($show_parts == 'all') {
                array_unshift($part_info, 'id');
            }

            foreach ($inlineout['atc_parts'] as $id) {
                $contents_mask = IMP_Contents::SUMMARY_DESCRIP |
                    IMP_Contents::SUMMARY_DESCRIP_LINK |
                    IMP_Contents::SUMMARY_DOWNLOAD |
                    IMP_Contents::SUMMARY_ICON |
                    IMP_Contents::SUMMARY_SIZE;
                $part_info[] = 'description_raw';
                $part_info[] = 'download_url';

                $summary = $this->contents->getSummary($id, $contents_mask);
                $tmp = array();
                foreach ($part_info as $val) {
                    if (isset($summary[$val])) {
                        $tmp[$val] = ($summary[$val] instanceof Horde_Url)
                            ? strval($summary[$val]->setRaw(true))
                            : $summary[$val];
                    }
                }
                $partlist[] = array_filter($tmp);
            }

            $result['atc']['list'] = $partlist;
        }

        return $result;
    }

    /**
     * Add changed flag information to the AJAX queue output, if necessary.
     */
    public function addChangedFlag()
    {
        global $injector;

        /* Add changed flag information. */
        list($mbox,) = $this->_indices->getSingle();
        if (!$this->_peek && $mbox->is_imap) {
            $status = $mbox->imp_imap->status(
                $mbox,
                Horde_Imap_Client::STATUS_PERMFLAGS
            );

            if (in_array(Horde_Imap_Client::FLAG_SEEN, $status['permflags'])) {
                $injector->getInstance('IMP_Ajax_Queue')->flag(
                    array(Horde_Imap_Client::FLAG_SEEN),
                    true,
                    $this->_indices
                );
            }
        }
    }

    /**
     * Return data to build an address header.
     *
     * @param mixed $header   The address header name (string) or a
     *                        Horde_Mime_Rfc822_List object.
     * @param integer $limit  Limit display to this many addresses. If null,
     *                        shows all addresses.
     *
     * @return array  An array with the following entries:
     *   - addr: (array) List of addresses/groups.
     *           Group keys: 'a' (list of addresses); 'g' (group name)
     *           Address keys: 'b' (bare address); 'p' (personal part)
     *           Both: 'v' (full value)
     *   - limit: (integer) If limit was reached, the number of total
     *            addresses.
     *   - raw: (string) A raw string to display instead of addresses.
     */
    public function getAddressHeader($header, $limit = 50)
    {
        if ($header instanceof Horde_Mail_Rfc822_List) {
            $addrlist = $header;
        } else {
            $addrlist = $this->_envelope->{strval($header)};
        }

        $addrlist->unique();

        $addr_ob = new IMP_Ajax_Addresses($addrlist);
        $addr_array = $addr_ob->toArray($limit);

        $out = array();
        if ($addr_array->limit) {
            $out['limit'] = $addr_array->total;
        }

        if (!empty($addr_array->addr)) {
            $out['addr'] = $addr_array->addr;
        } elseif ($header == 'to') {
            $out['raw'] = _("Undisclosed Recipients");
        }

        return $out;
    }

    /**
     * Get the inline display output for a message.
     *
     * @param string $mimeid  Restrict output to this MIME ID (and children).
     *
     * @return array  See IMP_Contents_InlineOutput#getInlineOutput().
     */
    public function getInlineOutput($mimeid = null)
    {
        global $registry;

        switch ($registry->getView()) {
        case $registry::VIEW_MINIMAL:
        case $registry::VIEW_SMARTMOBILE:
            $contents_mask = 0;
            break;

        default:
            $contents_mask = IMP_Contents::SUMMARY_BYTES |
                IMP_Contents::SUMMARY_SIZE |
                IMP_Contents::SUMMARY_ICON |
                IMP_Contents::SUMMARY_DESCRIP_LINK |
                IMP_Contents::SUMMARY_DOWNLOAD |
                IMP_Contents::SUMMARY_PRINT_STUB;
            break;
        }

        $part_info_display = $this->part_info;
        $part_info_display[] = 'print';

        $inline_ob = new IMP_Contents_InlineOutput();
        return $inline_ob->getInlineOutput($this->contents, array(
            'mask' => $contents_mask,
            'mimeid' => $mimeid,
            'part_info_display' => $part_info_display
        ));
    }

    /**
     * Get the user-specified headers.
     *
     * @return array  The list of user-defined headers.  Array of arrays with
     *                these keys:
     * <pre>
     *   - name: (string) Header name.
     *   - value: (string) Header value.
     * </pre>
     */
    public function getUserHeaders()
    {
        global $prefs;

        $headers = array();
        $user_hdrs = $prefs->getValue('mail_hdr');

        /* Split the list of headers by new lines and sort the list of headers
         * to make sure there are no duplicates. */
        if (is_array($user_hdrs)) {
            $user_hdrs = implode("\n", $user_hdrs);
        }

        $user_hdrs = trim($user_hdrs);
        if (empty($user_hdrs)) {
            return $headers;
        }

        $user_hdrs = array_filter(array_keys(array_flip(
            array_map(
                'trim',
                preg_split(
                    "/[\n\r]+/",
                    str_replace(':', '', $user_hdrs)
                )
            )
        )));
        natcasesort($user_hdrs);

        $headers_ob = $this->_loadHeaders();

        foreach ($user_hdrs as $hdr) {
            if ($user_val = $headers_ob[$hdr]) {
                $user_val = $user_val->value;
                foreach ((is_array($user_val) ? $user_val : array($user_val)) as $val) {
                    $headers[] = array(
                        'name' => $hdr,
                        'value' => $val
                    );
                }
            }
        }

        return array_values($headers);
    }

    /**
     * Return subject header data.
     *
     * @return array  Array with these possible keys:
     * <pre>
     *   - subject: (string) The subject.
     *   - subjectlink: (string) The subject with linked URLs/email addresses
     *                  (if not present, is same as 'subject').
     *   - title: (string) The title of the page derived from the subject.
     * </pre>
     */
    public function getSubject()
    {
        global $injector;

        if (!isset($this->_cache['subject'])) {
            $out = array();

            if (strlen($subject = $this->_envelope->subject)) {
                $text_filter = $injector->getInstance('Horde_Core_Factory_TextFilter');
                $filtered_subject = preg_replace(
                    "/\b\s+\b/",
                    ' ',
                    IMP::filterText($subject)
                );

                $out['subject'] = $text_filter->filter(
                    $filtered_subject,
                    'text2html',
                    array(
                        'parselevel' => Horde_Text_Filter_Text2html::NOHTML
                    )
                );
                $subjectlink = $text_filter->filter(
                    $filtered_subject,
                    'text2html',
                    array(
                        'parselevel' => Horde_Text_Filter_Text2html::MICRO
                    )
                );

                if ($subjectlink != $out['subject']) {
                    $out['subjectlink'] = $subjectlink;
                }
                $out['title'] = $subject;
            } else {
                $out['subject'] = $out['title'] = _("[No Subject]");
            }

            $this->_cache['subject'] = $out;
        }

        return $this->_cache['subject'];
    }

    /**
     * Return date data.
     *
     * @return mixed  Either a IMP_Message_Date object or null if no date
     *                information is available.
     */
    public function getDateOb()
    {
        return ($date = $this->_envelope->date)
            ? new IMP_Message_Date($date)
            : null;
    }

    /**
     * Return the save link for the message source.
     *
     * @return Horde_Url  URL for the save link.
     */
    public function getSaveAs()
    {
        list($bmbox, $buid) = ($this->_indices instanceof IMP_Indices_Mailbox)
            ? $this->_indices->buids->getSingle()
            : $this->_indices->getSingle();

        $subject = $this->getSubject();

        return IMP_Contents_View::downloadUrl(
            htmlspecialchars_decode($subject['subject']),
            array_merge(
                array('actionID' => 'save_message'),
                $bmbox->urlParams($buid)
            )
        );
    }

    /**
     * Return resent message data.
     *
     * @return array  An array of arrays, each sub-array representing a resent
     *                action and containing these keys:
     *   - date: (IMP_Message_Date) Date object of the resent action.
     *   - from: (Horde_Mail_Rfc822_List) Address object containing the
     *           address(es) that resent the message.
     */
    public function getResentData()
    {
        $out = array();

        if ($date = $this->_headers['Resent-Date']) {
            $dates = array_values($date->value);
            $from = array_values(
                $this->_headers['Resent-From']->getAddressList()
            );

            /* Sanity checking: RFC 5322 [3.6.6] declares that resent messages
             * MUST incude (at least) both Date and From. These headers are
             * "packaged" together by appending at the top of the headers.
             * Check for equal counts of both headers. If different, ignore
             * this information (not going to try to parse headers to fix
             * such a broken message just for this information). If the same,
             * assume that these headers are correctly packaged and link each
             * header together that shares the same array slot. */
            if (count($dates) === count($from)) {
                foreach ($dates as $key => $val) {
                    $out[] = array(
                        'date' => new IMP_Message_Date($val),
                        'from' => $from[$key]
                    );
                }
            }
        }

        return $out;
    }

    /* Internal methods. */

    /**
     * Loads the MIME headers object internally.
     */
    protected function _loadHeaders()
    {
        return $this->_peek
            ? $this->contents->getHeader()
            : $this->contents->getHeaderAndMarkAsSeen();
    }

}

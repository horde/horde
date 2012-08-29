<?php
/**
 * Horde_ActiveSync_Request_Search::
 *
 * Portions of this class were ported from the Z-Push project:
 *   File      :   wbxml.php
 *   Project   :   Z-Push
 *   Descr     :   WBXML mapping file
 *
 *   Created   :   01.10.2007
 *
 *   � Zarafa Deutschland GmbH, www.zarafaserver.de
 *   This file is distributed under GPL-2.0.
 *   Consult COPYING file for details
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Handle Search requests.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Request_Search extends Horde_ActiveSync_Request_Base
{

    /** Search code page **/
    const SEARCH_SEARCH              = 'Search:Search';
    const SEARCH_STORE               = 'Search:Store';
    const SEARCH_NAME                = 'Search:Name';
    const SEARCH_QUERY               = 'Search:Query';
    const SEARCH_OPTIONS             = 'Search:Options';
    const SEARCH_RANGE               = 'Search:Range';
    const SEARCH_STATUS              = 'Search:Status';
    const SEARCH_RESPONSE            = 'Search:Response';
    const SEARCH_RESULT              = 'Search:Result';
    const SEARCH_PROPERTIES          = 'Search:Properties';
    const SEARCH_TOTAL               = 'Search:Total';
    const SEARCH_EQUALTO             = 'Search:EqualTo';
    const SEARCH_VALUE               = 'Search:Value';
    const SEARCH_AND                 = 'Search:And';
    const SEARCH_OR                  = 'Search:Or';
    const SEARCH_FREETEXT            = 'Search:FreeText';
    const SEARCH_DEEPTRAVERSAL       = 'Search:DeepTraversal';
    const SEARCH_LONGID              = 'Search:LongId';
    const SEARCH_REBUILDRESULTS      = 'Search:RebuildResults';
    const SEARCH_LESSTHAN            = 'Search:LessThan';
    const SEARCH_GREATERTHAN         = 'Search:GreaterThan';
    const SEARCH_SCHEMA              = 'Search:Schema';
    const SEARCH_SUPPORTED           = 'Search:Supported';
    const SEARCH_USERNAME            = 'Search:UserName';
    const SEARCH_PASSWORD            = 'Search:Password';
    const SEARCH_CONVERSATIONID      = 'Search:ConversationId';
    /** Search Status **/
    const SEARCH_STATUS_SUCCESS      = 1;
    const SEARCH_STATUS_ERROR        = 3;

    /** Store Status **/
    const STORE_STATUS_SUCCESS       = 1;
    const STORE_STATUS_PROTERR       = 2;
    const STORE_STATUS_SERVERERR     = 3;
    const STORE_STATUS_BADLINK       = 4;
    const STORE_STATUS_NOTFOUND      = 6;
    const STORE_STATUS_CONNECTIONERR = 7;
    const STORE_STATUS_COMPLEX       = 8;


    /**
     * Handle request
     *
     * @return boolean
     */
    protected function _handle()
    {
        $this->_logger->info(sprintf(
            '[%s] Beginning SEARCH',
            $this->_device->id));

        $search_range = '0';
        $search_status = self::SEARCH_STATUS_SUCCESS;
        $store_status = self::STORE_STATUS_SUCCESS;

        if (!$this->_decoder->getElementStartTag(self::SEARCH_SEARCH) ||
            !$this->_decoder->getElementStartTag(self::SEARCH_STORE) ||
            !$this->_decoder->getElementStartTag(self::SEARCH_NAME)) {

            $search_status = self::SEARCH_STATUS_ERROR;
        }
        $search_name = $this->_decoder->getElementContent();
        if (!$this->_decoder->getElementEndTag()) {
            $search_status = self::SEARCH_STATUS_ERROR;
            $store_status = self::STORE_STATUS_PROTERR;
        }

        if (!$this->_decoder->getElementStartTag(self::SEARCH_QUERY)) {
            $search_status = self::SEARCH_STATUS_ERROR;
            $store_status = self::STORE_STATUS_PROTERR;
        }
        $search_query = array();
        switch (strtolower($search_name)) {
        case 'documentlibrary':
            $this->_logger->err('DOCUMENTLIBRARY NOT SUPPORTED.');
            return false;
        case 'mailbox':
            $search_query['query'] = $this->_parseQuery();
            break;
        case 'gal':
            $search_query['query'] = $this->_decoder->getElementContent();
        }
        if (!$this->_decoder->getElementEndTag()) {
            $search_status = self::SEARCH_STATUS_ERROR;
            $store_status = self::STORE_STATUS_PROTERR;
        }

        if ($this->_decoder->getElementStartTag(self::SEARCH_OPTIONS)) {
            while(1) {
                if ($this->_decoder->getElementStartTag(self::SEARCH_RANGE)) {
                    $search_range = $this->_decoder->getElementContent();
                    if (!$this->_decoder->getElementEndTag()) {
                        $search_status = self::SEARCH_STATUS_ERROR;
                        $store_status = self::STORE_STATUS_PROTERR;
                    }
                }
                if ($this->_decoder->getElementStartTag(self::SEARCH_DEEPTRAVERSAL)) {
                    if (!($searchdeeptraversal = $this->_decoder->getElementContent())) {
                        $search_querydeeptraversal = true;
                    } elseif (!$this->_decoder->getElementEndTag()) {
                        return false;
                    }
                }
                if ($this->_decoder->getElementStartTag(self::SEARCH_REBUILDRESULTS)) {
                    if (!($searchrebuildresults = $this->_decoder->getElementContent())) {
                        $search_queryrebuildresults = true;
                    } elseif (!$this->_decoder->getElementEndTag()) {
                        return false;
                    }
                }
                if ($this->_decoder->getElementStartTag(self::SEARCH_USERNAME)) {
                    if (!($search_queryusername = $this->_decoder->getElementContent())) {
                        return false;
                    } elseif (!$this->_decoder->getElementEndTag()) {
                        return false;
                    }
                }
                if ($this->_decoder->getElementStartTag(self::SEARCH_PASSWORD)) {
                    if (!($search_querypassword = $this->_decoder->getElementContent()))
                        return false;
                    else
                        if(!$this->_decoder->getElementEndTag())
                        return false;
                }
                if ($this->_decoder->getElementStartTag(self::SEARCH_SCHEMA)) {
                    if (!($searchschema = $this->_decoder->getElementContent())) {
                        $searchschema = true;
                    } elseif (!$this->_decoder->getElementEndTag()) {
                        return false;
                    }
                }
                if ($this->_decoder->getElementStartTag(Horde_ActiveSync::AIRSYNCBASE_BODYPREFERENCE)) {
                    $bodypreference=array();
                    while(1) {
                        if ($this->_decoder->getElementStartTag(Horde_ActiveSync::AIRSYNCBASE_TYPE)) {
                            $bodypreference['type'] = $this->_decoder->getElementContent();
                            if (!$this->_decoder->getElementEndTag()) {
                                return false;
                            }
                        }
                        if ($this->_decoder->getElementStartTag(Horde_ActiveSync::AIRSYNCBASE_TRUNCATIONSIZE)) {
                            $bodypreference['truncationsize'] = $this->_decoder->getElementContent();
                            if(!$this->_decoder->getElementEndTag())
                                return false;
                        }
                        if ($this->_decoder->getElementStartTag(Horde_ActiveSync::AIRSYNCBASE_ALLORNONE)) {
                            $bodypreference['allornone'] = $this->_decoder->getElementContent();
                            if (!$this->_decoder->getElementEndTag()) {
                                return false;
                            }
                        }
                        $e = $this->_decoder->peek();
                        if ($e[Horde_ActiveSync_Wbxml::EN_TYPE] == Horde_ActiveSync_Wbxml::EN_TYPE_ENDTAG) {
                            $this->_decoder->getElementEndTag();
                            if (!isset($searchbodypreference['wanted'])) {
                                $searchbodypreference['wanted'] = $bodypreference['type'];
                            }
                            if (isset($bodypreference['type'])) {
                                $searchbodypreference[$bodypreference['type']] = $bodypreference;
                            }
                            break;
                        }
                    }
                }
                $e = $this->_decoder->peek();
                if ($e[Horde_ActiveSync_Wbxml::EN_TYPE] == Horde_ActiveSync_Wbxml::EN_TYPE_ENDTAG) {
                    $this->_decoder->getElementEndTag();
                    break;
                }
            }
        }

        if (!$this->_decoder->getElementEndTag()) { //store
            $search_status = self::SEARCH_STATUS_ERROR;
            $store_status = self::STORE_STATUS_PROTERR;
        }
        if (!$this->_decoder->getElementEndTag()) { //search
            $search_status = self::SEARCH_STATUS_ERROR;
            $store_status = self::STORE_STATUS_PROTERR;
        }

        $search_query['range'] = $search_range;
        switch(strtolower($search_name)) {
        case 'documentlibrary':
            // not supported
            break;
        case 'mailbox':
            $search_query['rebuildresults'] = $search_queryrebuildresults;
            $search_query['deeptraversal'] =  $search_querydeeptraversal;
            break;
        }

        // Get search results from backend
        $search_result = $this->_driver->getSearchResults($search_name, $search_query);

        /* Send output */
        $this->_encoder->startWBXML();
        $this->_encoder->startTag(self::SEARCH_SEARCH);

        $this->_encoder->startTag(self::SEARCH_STATUS);
        $this->_encoder->content($search_status);
        $this->_encoder->endTag();

        $this->_encoder->startTag(self::SEARCH_RESPONSE);
        $this->_encoder->startTag(self::SEARCH_STORE);

        $this->_encoder->startTag(self::SEARCH_STATUS);
        $this->_encoder->content($store_status);
        $this->_encoder->endTag();

        if (is_array($search_result['rows']) && !empty($search_result['rows'])) {
            $search_total = count($search_result['rows']);
            foreach ($search_result['rows'] as $u) {
                switch (strtolower($search_name)) {
                case 'documentlibrary':
                    // not supported
                    continue;
                case 'gal':
                    $this->_encoder->startTag(self::SEARCH_RESULT);
                    $this->_encoder->startTag(self::SEARCH_PROPERTIES);

                    $this->_encoder->startTag(Horde_ActiveSync::GAL_DISPLAYNAME);
                    $this->_encoder->content($u[Horde_ActiveSync::GAL_DISPLAYNAME]);
                    $this->_encoder->endTag();

                    $this->_encoder->startTag(Horde_ActiveSync::GAL_PHONE);
                    $this->_encoder->content($u[Horde_ActiveSync::GAL_PHONE]);
                    $this->_encoder->endTag();

                    $this->_encoder->startTag(Horde_ActiveSync::GAL_OFFICE);
                    $this->_encoder->content($u[Horde_ActiveSync::GAL_OFFICE]);
                    $this->_encoder->endTag();

                    $this->_encoder->startTag(Horde_ActiveSync::GAL_TITLE);
                    $this->_encoder->content($u[Horde_ActiveSync::GAL_TITLE]);
                    $this->_encoder->endTag();

                    $this->_encoder->startTag(Horde_ActiveSync::GAL_COMPANY);
                    $this->_encoder->content($u[Horde_ActiveSync::GAL_COMPANY]);
                    $this->_encoder->endTag();

                    $this->_encoder->startTag(Horde_ActiveSync::GAL_ALIAS);
                    $this->_encoder->content($u[Horde_ActiveSync::GAL_ALIAS]);
                    $this->_encoder->endTag();

                    $this->_encoder->startTag(Horde_ActiveSync::GAL_FIRSTNAME);
                    $this->_encoder->content($u[Horde_ActiveSync::GAL_FIRSTNAME]);
                    $this->_encoder->endTag();

                    $this->_encoder->startTag(Horde_ActiveSync::GAL_LASTNAME);
                    $this->_encoder->content($u[Horde_ActiveSync::GAL_LASTNAME]);
                    $this->_encoder->endTag();

                    $this->_encoder->startTag(Horde_ActiveSync::GAL_HOMEPHONE);
                    $this->_encoder->content($u[Horde_ActiveSync::GAL_HOMEPHONE]);
                    $this->_encoder->endTag();

                    $this->_encoder->startTag(Horde_ActiveSync::GAL_MOBILEPHONE);
                    $this->_encoder->content($u[Horde_ActiveSync::GAL_MOBILEPHONE]);
                    $this->_encoder->endTag();

                    $this->_encoder->startTag(Horde_ActiveSync::GAL_EMAILADDRESS);
                    $this->_encoder->content($u[Horde_ActiveSync::GAL_EMAILADDRESS]);
                    $this->_encoder->endTag();

                    $this->_encoder->endTag();//result
                    $this->_encoder->endTag();//properties
                    break;
                case 'mailbox':
                    $this->_encoder->startTag(self::SEARCH_RESULT);
                    $this->_encoder->startTag(Horde_ActiveSync::SYNC_FOLDERTYPE);
                    $this->_encoder->content(Horde_ActiveSync::CLASS_EMAIL);
                    $this->_encoder->endTag();
                    $this->_encoder->startTag(self::SEARCH_LONGID);
                    $this->_encoder->content($u['uniqueid']);
                    $this->_encoder->endTag();
                    $this->_encoder->startTag(Horde_ActiveSync::SYNC_FOLDERID);
                    $this->_encoder->content($u['searchfolderid']);
                    $this->_encoder->endTag();
                    $this->_encoder->startTag(self::SEARCH_PROPERTIES);
                    $msg = $this->_driver->ItemOperationsFetchMailbox($u['uniqueid'], $searchbodypreference);
                    $msg->encodeStream($this->_encoder);
                    $this->_encoder->endTag();//properties
                    $this->_encoder->endTag();//result
                }

                $this->_encoder->startTag(self::SEARCH_RANGE);
                $this->_encoder->content($search_range);
                $this->_encoder->endTag();

                $this->_encoder->startTag(self::SEARCH_TOTAL);
                $this->_encoder->content($search_total);
                $this->_encoder->endTag();
            }
        }

        $this->_encoder->endTag();//store
        $this->_encoder->endTag();//response
        $this->_encoder->endTag();//search

        return true;
    }

    /**
     * Receive, and parse, the incoming wbxml query.
     *
     * According to MS docs, OR is supported in the protocol, but will ALWAYS
     * return a searchToComplex status in Exchange 2007. Additionally, AND is
     * ONLY supported as the topmost element. No nested AND is allowed. All
     * such queries will return a searchToComplex status.
     *
     * @param boolean $subquery  Parsing a subquery.
     *
     * @return array
     */
    protected function _parseQuery($subquery = null)
    {
        $query = array();
        while (($type = ($this->_decoder->getElementStartTag(self::SEARCH_AND) ? self::SEARCH_AND :
                ($this->_decoder->getElementStartTag(self::SEARCH_OR) ? self::SEARCH_OR :
                ($this->_decoder->getElementStartTag(self::SEARCH_EQUALTO) ? self::SEARCH_EQUALTO :
                ($this->_decoder->getElementStartTag(self::SEARCH_LESSTHAN) ? self::SEARCH_LESSTHAN :
                ($this->_decoder->getElementStartTag(self::SEARCH_GREATERTHAN) ? self::SEARCH_GREATERTHAN :
                ($this->_decoder->getElementStartTag(self::SEARCH_FREETEXT) ? self::SEARCH_FREETEXT :
                ($this->_decoder->getElementStartTag(Horde_ActiveSYnc::SYNC_FOLDERID) ? Horde_ActiveSync::SYNC_FOLDERID :
                ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_FOLDERTYPE) ? Horde_ActiveSync::SYNC_FOLDERTYPE :
                ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_DOCUMENTLIBRARY_LINKID) ? Horde_ActiveSync::SYNC_DOCUMENTLIBRARY_LINKID :
                ($this->_decoder->getElementStartTag(Horde_ActiveSync_Message_Mail::POOMMAIL_DATERECEIVED) ? Horde_ActiveSync_Message_Mail::POOMMAIL_DATERECEIVED :
                -1))))))))))) != -1) {


            switch ($type) {
            case self::SEARCH_AND:
            case self::SEARCH_OR:
            case self::SEARCH_EQUALTO:
            case self::SEARCH_LESSTHAN:
            case self::SEARCH_GREATERTHAN:
                $q = array(
                    'op' => $type,
                    'value' => $this->_parseQuery(true)
                );
                if ($subquery) {
                    $query['subquery'][] = $q;
                } else {
                    $query[] = $q;
                }
                $this->_decoder->getElementEndTag();
                break;
            default:
                if (($query[$type] = $this->_decoder->getElementContent())) {
                    $this->_decoder->getElementEndTag();
                } else {
                    $this->_decoder->getElementStartTag(self::SEARCH_VALUE);
                    $query[$type] = $this->_decoder->getElementContent();
                    switch ($type) {
                    case Horde_ActiveSync_Message_Mail::POOMMAIL_DATERECEIVED:
                        $query[$type] = new Horde_Date($query[$type]);
                        break;
                    }
                    $this->_decoder->getElementEndTag();
                };
                break;
            }
        }

        return $query;
    }

}
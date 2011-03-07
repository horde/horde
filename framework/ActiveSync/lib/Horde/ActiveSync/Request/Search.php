<?php
/**
 * ActiveSync Handler for Search requests
 *
 * Copyright 2009 - 2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package ActiveSync
 */
/**
 * Zarafa Deutschland GmbH, www.zarafaserver.de
 * This file is distributed under GPL v2.
 * Consult LICENSE file for details
 */
class Horde_ActiveSync_Request_Search extends Horde_ActiveSync_Request_Base
{

    /** Search code page **/
    const SEARCH_SEARCH = 'Search:Search';
    const SEARCH_STORE = 'Search:Store';
    const SEARCH_NAME = 'Search:Name';
    const SEARCH_QUERY = 'Search:Query';
    const SEARCH_OPTIONS = 'Search:Options';
    const SEARCH_RANGE = 'Search:Range';
    const SEARCH_STATUS = 'Search:Status';
    const SEARCH_RESPONSE = 'Search:Response';
    const SEARCH_RESULT = 'Search:Result';
    const SEARCH_PROPERTIES = 'Search:Properties';
    const SEARCH_TOTAL = 'Search:Total';
    const SEARCH_EQUALTO = 'Search:EqualTo';
    const SEARCH_VALUE = 'Search:Value';
    const SEARCH_AND = 'Search:And';
    const SEARCH_OR = 'Search:Or';
    const SEARCH_FREETEXT = 'Search:FreeText';
    const SEARCH_DEEPTRAVERSAL = 'Search:DeepTraversal';
    const SEARCH_LONGID = 'Search:LongId';
    const SEARCH_REBUILDRESULTS = 'Search:RebuildResults';
    const SEARCH_LESSTHAN = 'Search:LessThan';
    const SEARCH_GREATERTHAN = 'Search:GreaterThan';
    const SEARCH_SCHEMA = 'Search:Schema';
    const SEARCH_SUPPORTED = 'Search:Supported';

    /** Search Status **/
    const SEARCH_STATUS_SUCCESS = 1;
    const SEARCH_STATUS_ERROR = 3;

    /** Store Status **/
    const STORE_STATUS_SUCCESS = 1;
    const STORE_STATUS_PROTERR = 2;
    const STORE_STATUS_SERVERERR = 3;
    const STORE_STATUS_BADLINK = 4;
    const STORE_STATUS_NOTFOUND = 6;
    const STORE_STATUS_CONNECTIONERR = 7;
    const STORE_STATUS_COMPLEX = 8;


    /**
     * Handle request
     *
     * @return boolean
     */
    public function handle()
    {
        parent::handle();
        $this->_logger->info('[' . $this->_device->id . '] Beginning SEARCH');

        $searchrange = '0';
        $search_status = self::SEARCH_STATUS_SUCCESS;
        $store_status = self::STORE_STATUS_SUCCESS;

        if (!$this->_decoder->getElementStartTag(self::SEARCH_SEARCH) ||
            !$this->_decoder->getElementStartTag(self::SEARCH_STORE) ||
            !$this->_decoder->getElementStartTag(self::SEARCH_NAME)) {

            $search_status = self::SEARCH_STATUS_ERROR;
        }

        /* The type of search, we only support GAL right now */
        $searchname = $this->_decoder->getElementContent();
        if (!$this->_decoder->getElementEndTag()) {
            $search_status = self::SEARCH_STATUS_ERROR;
            $store_status = self::STORE_STATUS_PROTERR;
        }

        /* The search query */
        if (!$this->_decoder->getElementStartTag(self::SEARCH_QUERY)) {
            $search_status = self::SEARCH_STATUS_ERROR;
            $store_status = self::STORE_STATUS_PROTERR;
        }
        $searchquery = $this->_decoder->getElementContent();
        if (!$this->_decoder->getElementEndTag()) {
            $search_status = self::SEARCH_STATUS_ERROR;
            $store_status = self::STORE_STATUS_PROTERR;
        }

        /* Range */
        if ($this->_decoder->getElementStartTag(self::SEARCH_OPTIONS)) {
            while(1) {
                if ($this->_decoder->getElementStartTag(self::SEARCH_RANGE)) {
                    $searchrange = $this->_decoder->getElementContent();
                    if (!$this->_decoder->getElementEndTag()) {
                        $search_status = self::SEARCH_STATUS_ERROR;
                        $store_status = self::STORE_STATUS_PROTERR;
                    }
                }
                $e = $this->_decoder->peek();
                if ($e[Horde_ActiveSync_Wbxml::EN_TYPE] == Horde_ActiveSync_Wbxml::EN_TYPE_ENDTAG) {
                    $this->_decoder->getElementEndTag();
                    break;
                }
            }
        }

        /* Close the store container */
        if (!$this->_decoder->getElementEndTag()) {//store
            $search_status = self::SEARCH_STATUS_ERROR;
            $store_status = self::STORE_STATUS_PROTERR;
        }

        /* Close the search container */
        if (!$this->_decoder->getElementEndTag()) {//search
            $search_status = self::SEARCH_STATUS_ERROR;
            $store_status = self::STORE_STATUS_PROTERR;
        }

        /* We only support the GAL */
        if (strtoupper($searchname) != "GAL") {
            $this->_logger->debug('Searchtype ' . $searchname . 'is not supported');
            $store_status = self::STORE_STATUS_COMPLEX;
        }

        /* Get search results from backend */
        $rows = $this->_driver->getSearchResults($searchquery, $searchrange);

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

        $searchrange = $rows['range'];

        /* Build the results */
        foreach ($rows['rows'] as $u) {
            $this->_encoder->startTag(self::SEARCH_RESULT);
            $this->_encoder->startTag(self::SEARCH_PROPERTIES);

            $this->_encoder->startTag(Horde_ActiveSync::GAL_DISPLAYNAME);
            $this->_encoder->content($u[Horde_ActiveSync::GAL_DISPLAYNAME]);
            $this->_encoder->endTag();

            $this->_encoder->startTag(Horde_ActiveSync::GAL_PHONE);
            $this->_encoder->content($u[Horde_ActiveSync::GAL_PHONE]);
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

            $this->_encoder->startTag(Horde_ActiveSync::GAL_EMAILADDRESS);
            $this->_encoder->content($u[Horde_ActiveSync::GAL_EMAILADDRESS]);
            $this->_encoder->endTag();

            $this->_encoder->startTag(Horde_ActiveSync::GAL_HOMEPHONE);
            $this->_encoder->content($u[Horde_ActiveSync::GAL_HOMEPHONE]);
            $this->_encoder->endTag();

            $this->_encoder->startTag(Horde_ActiveSync::GAL_COMPANY);
            $this->_encoder->content($u[Horde_ActiveSync::GAL_COMPANY]);
            $this->_encoder->endTag();

            $this->_encoder->endTag();//result
            $this->_encoder->endTag();//properties

            $this->_encoder->startTag(self::SEARCH_RANGE);
            $this->_encoder->content($searchrange);
            $this->_encoder->endTag();

            $this->_encoder->startTag(self::SEARCH_TOTAL);
            $this->_encoder->content(count($rows));
            $this->_encoder->endTag();
        }

        $this->_encoder->endTag();//store
        $this->_encoder->endTag();//response
        $this->_encoder->endTag();//search

        return true;
    }

}
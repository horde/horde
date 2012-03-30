<?php
/**
 * Horde_ActiveSync_Request_ItemOperations
 *
 * PHP Version 5
 *
 * Contains portions of code from ZPush
 * Zarafa Deutschland GmbH, www.zarafaserver.de
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 * @copyright 2012 Horde LLC (http://www.horde.org/)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @link      http://pear.horde.org/index.php?package=ActiveSync
 * @package   ActiveSync
 */
/**
 * ActiveSync Handler for ItemOperations requests
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 * @copyright 2012 Horde LLC (http://www.horde.org/)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @link      http://pear.horde.org/index.php?package=ActiveSync
 * @package   ActiveSync
 */
class Horde_ActiveSync_Request_ItemOperations extends Horde_ActiveSync_Request_Base
{
    const ITEMOPERATIONS_ITEMOPERATIONS     = 'ItemOperations:ItemOperations';
    const ITEMOPERATIONS_FETCH              = 'ItemOperations:Fetch';
    const ITEMOPERATIONS_STORE              = 'ItemOperations:Store';
    const ITEMOPERATIONS_OPTIONS            = 'ItemOperations:Options';
    const ITEMOPERATIONS_RANGE              = 'ItemOperations:Range';
    const ITEMOPERATIONS_TOTAL              = 'ItemOperations:Total';
    const ITEMOPERATIONS_PROPERTIES         = 'ItemOperations:Properties';
    const ITEMOPERATIONS_DATA               = 'ItemOperations:Data';
    const ITEMOPERATIONS_STATUS             = 'ItemOperations:Status';
    const ITEMOPERATIONS_RESPONSE           = 'ItemOperations:Response';
    const ITEMOPERATIONS_VERSION            = 'ItemOperations:Version';
    const ITEMOPERATIONS_SCHEMA             = 'ItemOperations:Schema';
    const ITEMOPERATIONS_PART               = 'ItemOperations:Part';
    const ITEMOPERATIONS_EMPTYFOLDERCONTENT = 'ItemOperations:EmptyFolderContent';
    const ITEMOPERATIONS_DELETESUBFOLDERS   = 'ItemOperations:DeleteSubFolders';
    const ITEMOPERATIONS_USERNAME           = 'ItemOperations:UserName';
    const ITEMOPERATIONS_PASSWORD           = 'ItemOperations:Password';

    /* Status */
    const STATUS_SUCCESS         = 1;
    const STATUS_PROTERR         = 2;
    const STATUS_SERVERERR       = 3;
    // 4 - 13 are Document library related.
    const STATUS_ATTINVALID      = 15;
    const STATUS_POLICYERR       = 16;
    const STATUS_PARTSUCCESS     = 17;
    const STATUS_CREDENTIALS     = 18;
    const STATUS_PROTERR_OPTIONS = 155;
    const STATUS_NOT_SUPPORTED   = 156;


    /**
     * Handle the request.
     *
     * @return boolean
     */
    protected function _handle()
    {
        $this->_logger->info(sprintf(
            '[%s] Handling ITEMOPERATIONS command.',
            $this->_device->id)
        );

        $this->_statusCode = self::STATUS_SUCCESS;

        if(!$this->_decoder->getElementStartTag(self::ITEMOPERATIONS_ITEMOPERATIONS)) {
            throw new Horde_ActiveSync_Exception('Protocol Error');
        }

        // The current itemoperation task
        $thisio = array();
        $mimesupport = 0;
        $rightsmanagementsupport = false;
        while (($reqtype = ($this->_decoder->getElementStartTag(self::ITEMOPERATIONS_FETCH) ? self::ITEMOPERATIONS_FETCH :
                  ($this->_decoder->getElementStartTag(self::ITEMOPERATIONS_EMPTYFOLDERCONTENT) ? self::ITEMOPERATIONS_EMPTYFOLDERCONTENT  : -1))) != -1) {

            if ($reqtype == self::ITEMOPERATIONS_FETCH) {
                $thisio['type'] = 'fetch';

                while (($reqtag = ($this->_decoder->getElementStartTag(self::ITEMOPERATIONS_STORE) ? self::ITEMOPERATIONS_STORE :
                              ($this->_decoder->getElementStartTag(self::ITEMOPERATIONS_OPTIONS) ? self::ITEMOPERATIONS_OPTIONS :
                              ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_SERVERENTRYID) ? Horde_ActiveSync::SYNC_SERVERENTRYID :
                              ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_FOLDERID) ? Horde_ActiveSync::SYNC_FOLDERID :
                              ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_DOCUMENTLIBRARY_LINKID) ? Horde_ActiveSync::SYNC_DOCUMENTLIBRARY_LINKID :
                              ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_AIRSYNCBASE_FILEREFERENCE) ? Horde_ActiveSync::SYNC_AIRSYNCBASE_FILEREFERENCE :
                              ($this->_decoder->getElementStartTag(self::ITEMOPERATIONS_USERNAME) ? self::ITEMOPERATIONS_USERNAME :
                              ($this->_decoder->getElementStartTag(self::ITEMOPERATIONS_PASSWORD) ? self::ITEMOPERATIONS_PASSWORD :
                              ($this->_decoder->getElementStartTag(Horde_ActiveSync_Request_Search::SEARCH_LONGID) ? Horde_ActiveSync_Request_Search::SYNC_SEARCH_LONGID :
                              -1)))))))))) != -1) {

                    if ($reqtag == self::ITEMOPERATIONS_OPTIONS) {
                        while (($thisoption = ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_MIMESUPPORT) ? Horde_ActiveSync::SYNC_MIMESUPPORT :
                                ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_AIRSYNCBASE_BODYPREFERENCE) ? Horde_ActiveSync::SYNC_AIRSYNCBASE_BODYPREFERENCE :
                                  ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_RIGHTSMANAGEMENT_RIGHTSMANAGEMENTSUPPORT) ? Horde_ActiveSync::SYNC_RIGHTSMANAGEMENT_RIGHTSMANAGEMENTSUPPORT :
                                  -1)))) != -1) {

                            switch ($thisoption) {
                            case Horde_ActiveSync::SYNC_MIMESUPPORT:
                                $mimesupport = $this->_decoder->getElementContent();
                                $this->_decoder->getElementEndTag();
                                break;
                            case Horde_ActiveSync::SYNC_AIRSYNCBASE_BODYPREFERENCE:
                                $bodypreference = array();
                                while(1) {
                                    if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_AIRSYNCBASE_TYPE)) {
                                        $bodypreference['type'] = $this->_decoder->getElementContent();
                                        if (!$this->_decoder->getElementEndTag()) {
                                            throw new Horde_ActiveSync_Exception('Protocol Error');
                                        }
                                    }
                                    if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_AIRSYNCBASE_TRUNCATIONSIZE)) {
                                        $bodypreference['truncationsize'] = $this->_decoder->getElementContent();
                                        if (!$this->_decoder->getElementEndTag()) {
                                            throw new Horde_ActiveSync_Exception('Protocol Error');
                                        }
                                    }
                                    if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_AIRSYNCBASE_ALLORNONE)) {
                                        $bodypreference['allornone'] = $this->_decoder->getElementContent();
                                        if (!$this->_decoder->getElementEndTag()) {
                                            throw new Horde_ActiveSync_Exception('Protocol Error');
                                        }
                                    }

                                    $e = $this->_decoder->peek();
                                    if ($e[Horde_ActiveSync_Wbxml::EN_TYPE] == Horde_ActiveSync_Wbxml::EN_TYPE_ENDTAG) {
                                        $this->_decoder->getElementEndTag();
                                        if (!isset($thisio['bodypreference']['wanted'])) {
                                            $thisio['bodypreference']['wanted'] = $bodypreference['type'];
                                        }
                                        if (isset($bodypreference['type'])) {
                                            $thisio['bodypreference'][$bodypreference['type']] = $bodypreference;
                                        }
                                        break;
                                    }
                                }
                                break;
                            case Horde_ActiveSync::SYNC_RIGHTSMANAGEMENT_RIGHTSMANAGEMENTSUPPORT:
                                $rightsmanagementsupport = $this->_decoder->getElementContent();
                                if (!$this->_decoder->getElementEndTag()) {
                                    throw new Horde_ActiveSync_Exception('Protocol Error');
                                }
                                break;
                            }
                        }
                    } elseif ($reqtag == self::ITEMOPERATIONS_STORE) {
                        $thisio['store'] = $this->_decoder->getElementContent();
                    } elseif ($reqtag == self::ITEMOPERATIONS_USERNAME) {
                        $thisio['username'] = $this->_decoder->getElementContent();
                    } elseif ($reqtag == self::ITEMOPERATIONS_PASSWORD) {
                        $thisio['password'] = $this->_decoder->getElementContent();
                    } elseif ($reqtag == Horde_ActiveSync_Request_Search::SEARCH_LONGID) {
                        $thisio['searchlongid'] = $this->_decoder->getElementContent();
                    } elseif ($reqtag == Horde_ActiveSync::SYNC_AIRSYNCBASE_FILEREFERENCE) {
                        $thisio['airsyncbasefilereference'] = $this->_decoder->getElementContent();
                    } elseif ($reqtag == Horde_ActiveSync::SYNC_SERVERENTRYID) {
                        $thisio['serverentryid'] = $this->_decoder->getElementContent();
                    } elseif ($reqtag == Horde_ActiveSync::SYNC_FOLDERID) {
                        $thisio['folderid'] = $this->_decoder->getElementContent();
                    } elseif ($reqtag == Horde_ActiveSync::SYNC_DOCUMENTLIBRARY_LINKID) {
                        $thisio['documentlibrarylinkid'] = $this->_decoder->getElementContent();
                    }
                    $e = $this->_decoder->peek();
                    if ($e[Horde_ActiveSync_Wbxml::EN_TYPE] == Horde_ActiveSync_Wbxml::EN_TYPE_ENDTAG) {
                        $this->_decoder->getElementEndTag();
                    }
                }
                $itemoperations[] = $thisio;
                $this->_decoder->getElementEndTag(); // end SYNC_ITEMOPERATIONS_FETCH
            }
        }
        $this->_decoder->getElementEndTag(); // end SYNC_ITEMOPERATIONS_ITEMOPERATIONS

        $this->_encoder->startWBXML($this->_activeSync->hasMultipart());
        $this->_encoder->startTag(self::ITEMOPERATIONS_ITEMOPERATIONS);

        $this->_encoder->startTag(self::ITEMOPERATIONS_STATUS);
        $this->_encoder->content(self::STATUS_SUCCESS);
        $this->_encoder->endTag();

        $this->_encoder->startTag(self::ITEMOPERATIONS_RESPONSE);
        foreach($itemoperations as $value) {
            switch($value['type']) {
            case 'fetch' :
                switch(strtolower($value['store'])) {
                case 'mailbox' :
                    $this->_encoder->startTag(self::ITEMOPERATIONS_FETCH);

                    $this->_encoder->startTag(self::ITEMOPERATIONS_STATUS);
                    $this->_encoder->content(1);
                    $this->_encoder->endTag();

                    if (isset($value['airsyncbasefilereference'])) {
                        $this->_encoder->startTag(Horde_ActiveSync::SYNC_AIRSYNCBASE_FILEREFERENCE);
                        $this->_encoder->content($value['airsyncbasefilereference']);
                        $this->_encoder->endTag();
                        $msg = $this->_driver->itemOperationsGetAttachmentData($value['airsyncbasefilereference']);
                    } elseif (isset($value['searchlongid'])) {
                        $this->_encoder->startTag(Horde_ActiveSync_Request_Search::SEARCH_LONGID);
                        $this->_encoder->content($value['searchlongid']);
                        $this->_encoder->endTag();
                        $this->_encoder->startTag(Horde_ActiveSync::SYNC_FOLDERTYPE);
                        $this->_encoder->content('Email');
                        $this->_encoder->endTag();
                        $msg = $this->_driver->itemOperationsFetchMailbox($value['searchlongid'], $value['bodypreference'], $mimesupport);
                    } else {
                        if (isset($value['folderid'])) {
                            $this->_encoder->startTag(Horde_ActiveSync::SYNC_FOLDERID);
                            $this->_encoder->content($value['folderid']);
                            $this->_encoder->endTag();
                        }
                        if (isset($value['serverentryid'])) {
                            $this->_encoder->startTag(Horde_ActiveSync::SYNC_SERVERENTRYID);
                            $this->_encoder->content($value['serverentryid']);
                            $this->_encoder->endTag();
                        }
                        $this->_encoder->startTag(Horde_ActiveSync::SYNC_FOLDERTYPE);
                        $this->_encoder->content('Email');
                        $this->_encoder->endTag();
                        $msg = $this->_driver->fetch(
                            $value['folderid'],
                            $value['serverentryid'],
                            array('bodyprefs' => $value['bodypreference'], 'mimesupport' => $mimesupport)
                        );
                    }
                    $this->_encoder->startTag(self::ITEMOPERATIONS_PROPERTIES);
                    $msg->encode($this->_encoder);
                    $this->_encoder->endTag();

                    $this->_encoder->endTag();
                    break;
                case 'documentlibrary' :
                    // Not supported
                default :
                    $this->_logger->debug(sprintf(
                        "[%s] %s not supported by HANDLEITEMOPERATIONS.",
                        $this->_device->id,
                        $value['type'])
                    );
                    break;
                }
                break;
            default :
                $this->_logger->debug(sprintf(
                    "[%s] %s not supported by HANDLEITEMOPERATIONS.",
                    $this->_device->id,
                    $value['type'])
                );
                break;
            }
        }
        $this->_encoder->endTag(); //end SYNC_ITEMOPERATIONS_RESPONSE
        $this->_encoder->endTag(); //end SYNC_ITEMOPERATIONS_ITEMOPERATIONS
    }

}
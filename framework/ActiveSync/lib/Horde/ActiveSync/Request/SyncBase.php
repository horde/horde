<?php
/**
 * Horde_ActiveSync_Request_SyncBase::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2013 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Base class for handling ActiveSync Sync-type requests.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2013 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
abstract class Horde_ActiveSync_Request_SyncBase extends Horde_ActiveSync_Request_Base
{

    /**
     * Parse incoming BODYPARTPREFERENCE options.
     *
     * @param array $collection  An array structure to parse the data into.
     */
    protected function _bodyPartPrefs(&$collection)
    {
        if ($this->_decoder->getElementStartTag(Horde_ActiveSync::AIRSYNCBASE_BODYPARTPREFERENCE)) {
            $collection['bodypartprefs'] = array();
            if ($this->_decoder->getElementStartTag(Horde_ActiveSync::AIRSYNCBASE_TYPE)) {
                $collection['bodypartprefs']['type'] = $this->_decoder->getElementContent();
                // MS-ASAIRS 2.2.2.22.3 type MUST be BODYPREF_TYPE_HTML
                if (!$this->_decoder->getElementEndTag() ||
                    $collection['bodypartprefs']['type'] != Horde_ActiveSync::BODYPREF_TYPE_HTML) {
                    $this->_statusCode = self::STATUS_PROTERROR;
                    $this->_handleError($collection);
                    exit;
                }
            }
            if ($this->_decoder->getElementStartTag(Horde_ActiveSync::AIRSYNCBASE_TRUNCATIONSIZE)) {
                $collection['bodypartprefs']['truncationsize'] = $this->_decoder->getElementContent();
                if (!$this->_decoder->getElementEndTag()) {
                    $this->_statusCode = self::STATUS_PROTERROR;
                    $this->_handleError($collection);
                    exit;
                }
            }

            if ($this->_decoder->getElementStartTag(Horde_ActiveSync::AIRSYNCBASE_AllORNONE)) {
                $collection['bodypartprefs']['allornone'] = $this->_decoder->getElementContent();
                // MS-ASAIRS 2.2.2.1.1 - MUST be ignored if no trunction
                // size is set. Note we still must read it if it sent
                // so reading the wbxml stream does not break.
                if (empty($collection['bodypartprefs']['truncationsize'])) {
                    unset($collection['bodypartprefs']['allornone']);
                }
                if (!$this->_decoder->getElementEndTag()) {
                    $this->_statusCode = self::STATUS_PROTERROR;
                    $this->_handleError($collection);
                    exit;
                }
            }
            if ($this->_decoder->getElementStartTag(Horde_ActiveSync::AIRSYNCBASE_PREVIEW)) {
                $collection['bodypartprefs']['preview'] = $this->_decoder->getElementContent();
                // MS-ASAIRS 2.2.2.18.3 - Max size of preview is 255.
                if (!$this->_decoder->getElementEndTag() ||
                    $collection['bodypartprefs']['preview'] > 255) {

                    $this->_statusCode = self::STATUS_PROTERROR;
                    $this->_handleError($collection);
                    exit;
                }
            }
            if (!$this->_decoder->getElementEndTag()) {
                $this->_statusCode = self::STATUS_PROTERROR;
                $this->_handleError($collection);
                exit;
            }
        }
    }

    /**
     * Parse incoming BODYPREFERENCE options.
     *
     * @param array  An array structure to parse the values into.
     */
    protected function _bodyPrefs(&$collection)
    {
        if ($this->_decoder->getElementStartTag(Horde_ActiveSync::AIRSYNCBASE_BODYPREFERENCE)) {
            $body_pref = array();
            if (empty($collection['bodyprefs'])) {
                $collection['bodyprefs'] = array();
            }
            while (1) {
                if ($this->_decoder->getElementStartTag(Horde_ActiveSync::AIRSYNCBASE_TYPE)) {
                    $body_pref['type'] = $this->_decoder->getElementContent();
                    $collection['bodyprefs']['wanted'] = $body_pref['type'];
                    if (!$this->_decoder->getElementEndTag()) {
                        $this->_statusCode = self::STATUS_PROTERROR;
                        $this->_handleError($collection);
                        exit;
                    }
                }

                if ($this->_decoder->getElementStartTag(Horde_ActiveSync::AIRSYNCBASE_TRUNCATIONSIZE)) {
                    $body_pref['truncationsize'] = $this->_decoder->getElementContent();
                    if (!$this->_decoder->getElementEndTag()) {
                        $this->_statusCode = self::STATUS_PROTERROR;
                        $this->_handleError($collection);
                        exit;
                    }
                }

                if ($this->_decoder->getElementStartTag(Horde_ActiveSync::AIRSYNCBASE_ALLORNONE)) {
                    $body_pref['allornone'] = $this->_decoder->getElementContent();
                    if (!$this->_decoder->getElementEndTag()) {
                        $this->_statusCode = self::STATUS_PROTERROR;
                        $this->_handleError($collection);
                        exit;
                    }
                }

                if ($this->_decoder->getElementStartTag(Horde_ActiveSync::AIRSYNCBASE_PREVIEW)) {
                    $body_pref['preview'] = $this->_decoder->getElementContent();
                    if (!$this->_decoder->getElementEndTag()) {
                        $this->_statusCode = self::STATUS_PROTERROR;
                        $this->_handleError($collection);
                        exit;
                    }
                }

                $e = $this->_decoder->peek();
                if ($e[Horde_ActiveSync_Wbxml::EN_TYPE] == Horde_ActiveSync_Wbxml::EN_TYPE_ENDTAG) {
                    $this->_decoder->getElementEndTag();
                    $collection['bodyprefs'][$body_pref['type']] = $body_pref;
                    break;
                }
            }
        }
    }

    protected function _rightsManagement(&$collection)
    {
        if ($this->_decoder->getElementStartTag(Horde_ActiveSync::RM_SUPPORT)) {
            $collection['rightsmanagement'] = $this->_decoder->getElementContent();
            if (!$this->_decoder->getElementEndTag()) {
                $this->_statusCode = self::STATUS_PROTERROR;
                $this->_handleError($collection);
                exit;
            }
        }
    }

    protected function _mimeSupport(&$collection)
    {
        if ($this->_decoder->getElementStartTag(Horde_ActiveSync::SYNC_MIMESUPPORT)) {
            $collection['mimesupport'] = $this->_decoder->getElementContent();
            if (!$this->_decoder->getElementEndTag()) {
                $this->_statusCode = self::STATUS_PROTERROR;
                $this->_handleError($collection);
                exit;
            }
        }
    }
}

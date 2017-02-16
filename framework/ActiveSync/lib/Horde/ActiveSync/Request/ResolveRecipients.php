<?php
/**
 * Horde_ActiveSync_Request_ResolveRecipients::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2012-2017 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * ActiveSync Handler for resolving recipients.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2012-2017 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 * @internal
 */
class Horde_ActiveSync_Request_ResolveRecipients extends Horde_ActiveSync_Request_Base
{
    const TAG_RESOLVERECIPIENTS      = 'ResolveRecipients:ResolveRecipients';
    const TAG_RESPONSE               = 'ResolveRecipients:Response';
    const TAG_STATUS                 = 'ResolveRecipients:Status';
    const TAG_TYPE                   = 'ResolveRecipients:Type';
    const TAG_RECIPIENT              = 'ResolveRecipients:Recipient';
    const TAG_DISPLAYNAME            = 'ResolveRecipients:DisplayName';
    const TAG_EMAILADDRESS           = 'ResolveRecipients:EmailAddress';
    const TAG_CERTIFICATES           = 'ResolveRecipients:Certificates';
    const TAG_CERTIFICATE            = 'ResolveRecipients:Certificate';
    const TAG_MINICERTIFICATE        = 'ResolveRecipients:MiniCertificate';
    const TAG_OPTIONS                = 'ResolveRecipients:Options';
    const TAG_TO                     = 'ResolveRecipients:To';
    const TAG_CERTIFICATERETRIEVAL   = 'ResolveRecipients:CertificateRetrieval';
    const TAG_RECIPIENTCOUNT         = 'ResolveRecipients:RecipientCount';
    const TAG_MAXCERTIFICATES        = 'ResolveRecipients:MaxCertificates';
    const TAG_MAXAMBIGUOUSRECIPIENTS = 'ResolveRecipients:MaxAmbiguousRecipients';
    const TAG_CERTIFICATECOUNT       = 'ResolveRecipients:CertificateCount';
    const TAG_MAXSIZE                = 'ResolveRecipients:MaxSize';
    const TAG_DATA                   = 'ResolveRecipients:Data';
    const TAG_PICTURE                = 'ResolveRecipients:Picture';
    const TAG_MAXPICTURES            = 'ResolveRecipients:MaxPictures';

    // 14
    const TAG_AVAILABILITY           = 'ResolveRecipients:Availability';
    const TAG_STARTTIME              = 'ResolveRecipients:StartTime';
    const TAG_ENDTIME                = 'ResolveRecipients:EndTime';
    const TAG_MERGEDFREEBUSY         = 'ResolveRecipients:MergedFreeBusy';

    /* Certificate Retrieval */
    const CERT_RETRIEVAL_NONE        = 1;
    const CERT_RETRIEVAL_FULL        = 2;
    const CERT_RETRIEVAL_MINI        = 3;

    /* Global Status */
    const STATUS_SUCCESS             = 1;
    const STATUS_PROTERR             = 5;
    const STATUS_SERVERERR           = 6;

    /* Response Status */
    const STATUS_RESPONSE_SUCCESS    = 1;
    const STATUS_RESPONSE_AMBSUGG    = 2;
    const STATUS_RESPONSE_NONE       = 4;

    /* Certificate Status */
    const STATUS_CERT_SUCCESS        = 1;
    const STATUS_CERT_NOCERT         = 7;
    const STATUS_LIMIT               = 8;

    /* Availability Status */
    const STATUS_AVAIL_SUCCESS       = 1;
    const STATUS_AVAIL_MAXRECIPIENTS = 160;
    const STATUS_AVAIL_MAXLIST       = 161;
    const STATUS_AVAIL_TEMPFAILURE   = 162;
    const STATUS_AVAIL_NOTFOUND      = 163;

    /**
     * Handle the request
     *
     * @return boolean
     * @throws Horde_ActiveSync_Exception
     */
    protected function _handle()
    {
        $this->_logger->meta('Handling RESOLVERECIPIENTS command.');
        if (!$this->_decoder->getElementStartTag(self::TAG_RESOLVERECIPIENTS)) {
            return false;
        }
        $status = self::STATUS_SUCCESS;

        while ($status == self::STATUS_SUCCESS &&
            ($field = ($this->_decoder->getElementStartTag(self::TAG_TO) ? self::TAG_TO :
             ($this->_decoder->getElementStartTag(self::TAG_OPTIONS) ? self::TAG_OPTIONS :
            -1))) != -1) {

            if ($field == self::TAG_OPTIONS) {
                while ($status == self::STATUS_SUCCESS &&
                    ($option = ($this->_decoder->getElementStartTag(self::TAG_CERTIFICATERETRIEVAL) ? self::TAG_CERTIFICATERETRIEVAL :
                    ($this->_decoder->getElementStartTag(self::TAG_MAXCERTIFICATES) ? self::TAG_MAXCERTIFICATES :
                    ($this->_decoder->getElementStartTag(self::TAG_MAXAMBIGUOUSRECIPIENTS) ? self::TAG_MAXAMBIGUOUSRECIPIENTS :
                    ($this->_decoder->getElementStartTag(self::TAG_AVAILABILITY) ? self::TAG_AVAILABILITY :
                    -1))))) != -1) {

                    if ($option == self::TAG_AVAILABILITY) {
                        $options[self::TAG_AVAILABILITY] = true;
                        while ($status == self::STATUS_SUCCESS &&
                            ($tag = ($this->_decoder->getElementStartTag(self::TAG_STARTTIME) ? self::TAG_STARTTIME :
                                ($this->_decoder->getElementStartTag(self::TAG_ENDTIME) ? self::TAG_ENDTIME :
                                -1))) != -1) {
                            $options[$tag] = $this->_decoder->getElementContent();
                            if (!$this->_decoder->getElementEndTag()) {
                                $status = self::STATUS_PROTERR;
                            }
                        }
                    } else {
                        $options[$option] = $this->_decoder->getElementContent();
                    }

                    if ($option == self::TAG_PICTURE) {
                        $options[self::TAG_PICTURE] = true;
                        if ($this->_decoder->getElementStartTag(self::TAG_MAXSIZE)) {
                            $options[self::TAG_MAXSIZE] = $this->_decoder->getElementContent();
                        }
                        if (!$this->_decoder->getElementEndTag()) {
                            $status = self::STATUS_PROTERR;
                        }
                        if ($this->_decoder->getElementStartTag(self::TAG_MAXPICTURES)) {
                            $options[self::TAG_MAXPICTURES] = $this->_decoder->getElementContent();
                        }
                        if (!$this->_decoder->getElementEndTag()) {
                                $status = self::STATUS_PROTERR;
                        }
                    }

                    if (!$this->_decoder->getElementEndTag()) {
                        $status = self::STATUS_PROTERR;
                    }
                }
                if (!$this->_decoder->getElementEndTag()) {
                    $status = self::STATUS_PROTERR;
                }
            } elseif ($field == self::TAG_TO) {
                $content = $this->_decoder->getElementContent();
                $to[] = $content;
                if (!$this->_decoder->getElementEndTag()) {
                    $status = self::STATUS_PROTERR;
                }
            }
        }

        // Verify max isn't attempted.
        if (isset($options[self::TAG_AVAILABILITY]) && count($to) > 100) {
            // Specs say to send this, but it's defined as a child of the
            // self::TAG_AVAILABILITY response. If we have too many recipients,
            // we don't check the availability?? Not sure what to do with this.
            // For now, treat it as a protocol error.
            //$avail_status = self::STATUS_AVAIL_MAXRECIPIENTS;
            $status = self::STATUS_PROTERR;
        }

        $results = array();
        if ($status == self::STATUS_SUCCESS) {
            foreach ($to as $item) {
                $driver_opts = array(
                    'maxcerts' => !empty($options[self::TAG_MAXCERTIFICATES]) ? $options[self::TAG_MAXCERTIFICATES] : false,
                    'maxambiguous' => !empty($options[self::TAG_MAXAMBIGUOUSRECIPIENTS]) ? $options[self::TAG_MAXAMBIGUOUSRECIPIENTS] : false,
                    'starttime' => !empty($options[self::TAG_STARTTIME]) ? new Horde_Date($options[self::TAG_STARTTIME], 'utc') : false,
                    'endtime' => !empty($options[self::TAG_ENDTIME]) ? new Horde_Date($options[self::TAG_ENDTIME], 'utc') : false,
                    'pictures' => !empty($options[self::TAG_PICTURE]),
                    'maxsize' => !empty($options[self::TAG_MAXSIZE]) ? $options[self::TAG_MAXSIZE] : false,
                    'maxpictures' => !empty($options[self::TAG_MAXPICTURES]) ? $options[self::TAG_MAXPICTURES] : false,
                );
                $results[$item] = $this->_driver->resolveRecipient(
                    isset($options[self::TAG_CERTIFICATERETRIEVAL]) ? 'certificate' : 'availability',
                    $item,
                    $driver_opts
                );
            }
        }

        $this->_encoder->startWBXML();
        $this->_encoder->startTag(self::TAG_RESOLVERECIPIENTS);

        $this->_encoder->startTag(self::TAG_STATUS);
        $this->_encoder->content($status);
        $this->_encoder->endTag();

        foreach ($to as $item) {
            $this->_encoder->startTag(self::TAG_RESPONSE);

            $this->_encoder->startTag(self::TAG_TO);
            $this->_encoder->content($item);
            $this->_encoder->endTag();

            $this->_encoder->startTag(self::TAG_STATUS);
            if (empty($results[$item])) {
                $responseStatus = self::STATUS_RESPONSE_NONE;
            } elseif (count($results[$item]) > 1) {
                $responseStatus = self::STATUS_RESPONSE_AMBSUGG;
            } else {
                $responseStatus = self::STATUS_RESPONSE_SUCCESS;
            }
            $this->_encoder->content($responseStatus);
            $this->_encoder->endTag();

            $this->_encoder->startTag(self::TAG_RECIPIENTCOUNT);
            $this->_encoder->content(count($results[$item]));
            $this->_encoder->endTag();

            foreach ($results[$item] as $value) {
                $this->_encoder->startTag(self::TAG_RECIPIENT);

                $this->_encoder->startTag(self::TAG_TYPE);
                $this->_encoder->content($value['type']);
                $this->_encoder->endTag();

                $this->_encoder->startTag(self::TAG_DISPLAYNAME);
                $this->_encoder->content($value['displayname']);
                $this->_encoder->endTag();

                $this->_encoder->startTag(self::TAG_EMAILADDRESS);
                $this->_encoder->content($value['emailaddress']);
                $this->_encoder->endTag();

                if (isset($options[self::TAG_CERTIFICATERETRIEVAL]) &&
                    $options[self::TAG_CERTIFICATERETRIEVAL] > 1) {

                    $this->_encoder->startTag(self::TAG_CERTIFICATES);

                    $this->_encoder->startTag(self::TAG_STATUS);
                    if (count($value['entries']) == 0) {
                        $certStatus = self::STATUS_CERT_NOCERT;
                    } else {
                        $certStatus = self::STATUS_CERT_SUCCESS;
                    }
                    $this->_encoder->content($certStatus);
                    $this->_encoder->endTag();

                    $this->_encoder->startTag(self::TAG_CERTIFICATECOUNT);
                    $this->_encoder->content(count($value['entries']));
                    $this->_encoder->endTag();

                    switch ($options[self::TAG_CERTIFICATERETRIEVAL]) {
                    case self::CERT_RETRIEVAL_FULL:
                        foreach($value['entries'] as $cert) {
                            $this->_encoder->startTag(self::TAG_CERTIFICATE);
                            $this->_encoder->content($cert);
                            $this->_encoder->endTag();
                        }
                        break;
                    case self::CERT_RETRIEVAL_MINI:
                        foreach($value['entries'] as $cert) {
                            $this->_encoder->startTag(self::TAG_MINICERTIFICATE);
                            $this->_encoder->content($cert);
                            $this->_encoder->endTag();
                        }
                    }
                    $this->_encoder->endTag();
                }

                if (isset($options[self::TAG_AVAILABILITY])) {
                    $this->_encoder->startTag(self::TAG_AVAILABILITY);

                    $this->_encoder->startTag(self::TAG_STATUS);
                    $this->_encoder->content(empty($value['availability']) ? self::STATUS_AVAIL_NOTFOUND : self::STATUS_AVAIL_SUCCESS);
                    $this->_encoder->endTag();

                    if (!empty($value['availability'])) {
                        $this->_encoder->startTag(self::TAG_MERGEDFREEBUSY);
                        $this->_encoder->content($value['availability']);
                        $this->_encoder->endTag();
                    }
                    $this->_encoder->endTag();
                }

                if ($this->_device->version >= Horde_ActiveSync::VERSION_FOURTEENONE &&
                    isset($options[self::TAG_PICTURE]) &&
                    !empty($value['picture'])) {

                    $this->_encoder->startTag(self::TAG_PICTURE);
                    $value['picture']->encodeStream($this->_encoder);
                    $this->_encoder->endTag();
                }

                $this->_encoder->endTag(); // end self::TAG_RECIPIENT
            }
            $this->_encoder->endTag(); // end self::TAG_RESPONSE
        }
        $this->_encoder->endTag(); // end self::TAG_RESOLVERECIPIENTS

        return true;
    }

}

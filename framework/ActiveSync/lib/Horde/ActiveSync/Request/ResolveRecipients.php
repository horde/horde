<?php
/**
 * Horde_ActiveSync_Request_ResolveRecipients::
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
 * ActiveSync Handler for resolving recipients.
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

    /**
     * Handle the request
     *
     * @return boolean
     * @throws Horde_ActiveSync_Exception
     */
    protected function _handle()
    {
        $this->_logger->info(sprintf(
            "[%s] RESOLVERECIPIENTS",
            $this->_device->id));

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
                    -1)))) != -1) {

                    $options[$option] = $this->_decoder->getElementContent();
                    if (!$this->_decoder->getElementEndTag()) {
                        $status = self::STATUS_PROTERR;
                    }
                }
                if (!$this->_decoder->getElementEndTag()) {
                    $status = self::STATUS_PROTERR;
                }
            } elseif ($field == self::TAG_TO) {
                $content = $this->_decoder->getElementContent();
                $this->_logger->debug($content);
                $to[] = $content;
                if (!$this->_decoder->getElementEndTag()) {
                    $status = self::STATUS_PROTERR;
                }
            }
        }

        $results = array();
        foreach ($to as $item) {
            if (isset($options[self::TAG_CERTIFICATERETRIEVAL])) {
                $result = $this->_driver->resolveRecipient(
                    'certificate',
                    $item,
                    array(
                        'maxcerts' => $options[self::TAG_MAXCERTIFICATES],
                        'maxambiguous' => $options[self::TAG_MAXAMBIGUOUSRECIPIENTS],
                    )
                );
                $results[$item] = $result;
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
            if (count($results[$item]) > 1) {
                $responseStatus = self::STATUS_RESPONSE_AMBSUGG;
            } elseif (count($results[$item]) == 0) {
                $responseStatus = self::STATUS_RESPONSE_NONE;
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

                    $this->_encoder->startTag(self::TAG_RECIPIENTCOUNT);
                    $this->_encoder->content(count($results[$item]));
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

                $this->_encoder->endTag(); // end self::TAG_RECIPIENT
            }

            $this->_encoder->endTag(); // end self::TAG_RESPONSE
        }

        $this->_encoder->endTag(); // end self::TAG_RESOLVERECIPIENTS

        return true;
    }

}

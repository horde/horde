<?php
/**
 * Horde_ActiveSync_Request_ValidateCertificate::
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
 * ActiveSync Handler for ValidateCertificate requests.  Responsible for
 * determining if the presented smime certificate is valid.
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
class Horde_ActiveSync_Request_ValidateCert extends Horde_ActiveSync_Request_Base
{
    const VALIDATECERT_VALIDATECERT     = 'ValidateCert:ValidateCert';
    const VALIDATECERT_CERTIFICATES     = 'ValidateCert:Certificates';
    const VALIDATECERT_CERTIFICATE      = 'ValidateCert:Certificate';
    const VALIDATECERT_CERTIFICATECHAIN = 'ValidateCert:CertificateChain';
    const VALIDATECERT_CHECKCRL         = 'ValidateCert:CheckCRL';
    const VALIDATECERT_STATUS           = 'ValidateCert:Status';

    const STATUS_SUCCESS                = 1;
    const STATUS_PROTERR                = 2;
    const STATUS_SIGERR                 = 3;
    const STATUS_UNTRUSTED              = 4;
    const STATUS_EXPIRED                = 7;
    const STATUS_PURPOSE_INVALID        = 9;
    const STATUS_MISSING_INFO           = 10;
    const STATUS_UNKNOWN                = 17;

    /**
     * Handle request
     *
     * @return boolean
     * @throws Horde_ActiveSync_Exception
     */
    protected function _handle()
    {
        $this->_logger->info(sprintf(
            "[%s] Handling ValidateCertificate command.",
            $this->_device->id)
        );

        if (!$this->_decoder->getElementStartTag(self::VALIDATECERT_VALIDATECERT)) {
            throw new Horde_ActiveSync_Exception('Protocol Error');
        }

        $certificates = array();
        $chain_certificates = array();
        while (($field = ($this->_decoder->getElementStartTag(self::VALIDATECERT_CERTIFICATES) ? self::VALIDATECERT_CERTIFICATES :
            ($this->_decoder->getElementStartTag(self::VALIDATECERT_CERTIFICATECHAIN) ? self::VALIDATECERT_CERTIFICATECHAIN :
            ($this->_decoder->getElementStartTag(self::VALIDATECERT_CHECKCRL) ? self::VALIDATECERT_CHECKCRL :
            -1)))) != -1) {
            if ($field == self::VALIDATECERT_CERTIFICATES) {
                while ($this->_decoder->getElementStartTag(self::VALIDATECERT_CERTIFICATE)) {
                    $certificates[] = $this->_decoder->getElementContent();
                    $this->_logger->debug('VALIDATE CERT: ' . $certificates[count($certificates) - 1]);
                    if (!$this->_decoder->getElementEndTag()) {
                        throw new Horde_ActiveSync_Exception('Protocol Error');
                    }
                }
                if (!$this->_decoder->getElementEndTag()) {
                    throw new Horde_ActiveSync_Exception('Protocol Error');
                }
            } elseif ($field == self::VALIDATECERT_CERTIFICATECHAIN) {
                while ($this->_decoder->getElementStartTag(self::VALIDATECERT_CERTIFICATE)) {
                    $chain_certificates[] = $this->_decoder->getElementContent();
                    $this->_logger->debug('CHAIN CERT: ' . $chain_certificates[count($chain_certificates) - 1]);
                    if (!$this->_decoder->getElementEndTag()) {
                        throw new Horde_ActiveSync_Exception('Protocol Error');
                    }
                }
                if (!$this->_decoder->getElementEndTag()) {
                    throw new Horde_ActiveSync_Exception('Protocol Error');
                }
            } elseif ($field == self::VALIDATECERT_CHECKCRL) {
                if ($checkcrl = $this->_decoder->getElementContent()) {
                    $this->_logger->debug('CRL: ' . $checkcrl);
                }
                if (!$this->_decoder->getElementEndTag()) {
                    throw new Horde_ActiveSync_Exception('Protocol Error');
                }
            }
        }

        $cert_status = array();
        foreach ($certificates as $key => $certificate) {
            $cert_der = base64_decode($certificate);
            $cert_pem = "-----BEGIN CERTIFICATE-----\n"
                . chunk_split(base64_encode($cert_der), 64, "\n")
                . "-----END CERTIFICATE-----\n";

            // Parsable?
            if (!$parsed = openssl_x509_parse($cert_pem, false)) {
                $cert_status[$key] = self::STATUS_MISSING_INFO;
                continue;
            }

            // Valid times?
            $now = time();
            if ($parsed['validFrom_time_t'] >= $now || $parsed['validTo_time_t'] <= $now) {
                $cert_status[$key] = self::STATUS_EXPIRED;
                continue;
            }

            // Valid purpose/trusted?
            // @TODO: CRL support, CHAIN support
            $result = openssl_x509_checkpurpose($cert_pem, X509_PURPOSE_SMIME_SIGN, array($this->_activeSync->certPath));
            if ($result === false) {
                // @TODO:
                // checkpurpose returns false if either the purpose is invalid OR
                // the certificate is untrusted, so we should validate the
                // trust before we send back any errors.
                $cert_status[$key] = self::STATUS_PURPOSE_INVALID;
            } elseif ($results == -1) {
                // Unspecified error.
                $cert_status[$key] = self::STATUS_UNKNOWN;
            } else {
                // If checkpurpose passes, it's valid AND trusted.
                $cert_status[$key] = self::STATUS_SUCCESS;
            }
        }

        $this->_encoder->startWBXML();
        $this->_encoder->startTag(self::VALIDATECERT_VALIDATECERT);

        $this->_encoder->startTag(self::VALIDATECERT_STATUS);
        $this->_encoder->content(1);
        $this->_encoder->endTag();

        foreach ($certificates as $key => $certificate) {
            $this->_encoder->startTag(self::VALIDATECERT_CERTIFICATE);

            $this->_encoder->startTag(self::VALIDATECERT_STATUS);
            $this->_encoder->content($cert_status[$key]);
            $this->_encoder->endTag();

            $this->_encoder->endTag();
        }

        $this->_encoder->endTag();

        return true;
    }

}

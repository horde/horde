<?php
/**
 * Handler for Autoconfigure requests
 *
 * @copyright 2012 Horde LLC (http://www.horde.org/)
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @package ActiveSync
 */
/**
 * ActiveSync Handler for Autoconfiguration requests.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @package ActiveSync
 */
class Horde_ActiveSync_Request_Autodiscover extends Horde_ActiveSync_Request_Base
{
    /**
     * Handle request
     *
     * @return boolean
     */
    protected function _handle()
    {
        $input_stream = $this->_decoder->getStream();
        $parser = xml_parser_create();
        xml_parse_into_struct($parser, stream_get_contents($input_stream), $values);
        $email = $values[2]['value'];
        fwrite(
            $this->_encoder->getStream(),
            $this->_buildResponseString($this->_driver->autoDiscover($email)));

        return true;
    }

    protected function _buildResponseString($properties)
    {
        return '<?xml version="1.0" encoding="utf-8"?>
            <Autodiscover xmlns:autodiscover="http://schemas.microsoft.com/exchange/autodiscover/mobilesync/responseschema/2006">
                <autodiscover:Response>
                    <autodiscover:Culture>en:us</autodiscover:Culture>
                    <autodiscover:User>
                        <autodiscover:DisplayName>' . $properties['display_name'] . '</autodiscover:DisplayName>
                        <autodiscover:EMailAddress>' . $properties['email'] . '</autodiscover:EMailAddress>
                    </autodiscover:User>
                    <autodiscover:Action>
                        <autodiscover:Settings>
                            <autodiscover:Server>
                                <autodiscover:Type>MobileSync</autodiscover:Type>
                                <autodiscover:Url>' . $properties['url'] . '</autodiscover:Url>
                                <autodiscover:Name>' . $properties['url'] . '</autodiscover:Name>
                        </autodiscover:Settings>
                    </autodiscover:Action>
                </autodiscover:Response>
            </Autodiscover>';
    }

}
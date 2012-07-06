<?php
/**
 * Horde_ActiveSync_Request_Autodiscover::
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
 * ActiveSync Handler for Autodiscover requests.
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
class Horde_ActiveSync_Request_Autodiscover extends Horde_ActiveSync_Request_Base
{
    /**
     * Handle request
     *
     * @return text  The content type of the response (text/html).
     */
    public function handle()
    {
        $input_stream = $this->_decoder->getStream();
        $parser = xml_parser_create();
        xml_parse_into_struct($parser, stream_get_contents($input_stream), $values);
        $email = $values[2]['value'];
        $username = $this->_driver->getUsernameFromEmail($email);
        if (!$this->_activeSync->authenticate($username)) {
            fwrite(
                $this->_encoder->getStream(),
                $this->_buildFailureResponse($email));

            return 'text/xml';
        }
        fwrite(
            $this->_encoder->getStream(),
            $this->_buildResponseString($this->_driver->autoDiscover()));

        return 'text/xml';
    }

    protected function _handle()
    {
    }

    protected function _buildResponseString($properties)
    {
        return '<?xml version="1.0" encoding="utf-8"?>
            <Autodiscover xmlns:autodiscover="http://schemas.microsoft.com/exchange/autodiscover/mobilesync/responseschema/2006">
                <autodiscover:Response>
                    <autodiscover:Culture>' . $properties['culture'] . '</autodiscover:Culture>
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
                            </autodiscover:Server>
                        </autodiscover:Settings>
                    </autodiscover:Action>
                </autodiscover:Response>
            </Autodiscover>';
    }

    protected function _buildFailureResponse($email)
    {
        return '<?xml version="1.0" encoding="utf-8"?>
            <Autodiscover xmlns:autodiscover="http://schemas.microsoft.com/exchange/autodiscover/mobilesync/responseschema/2006">
            <autodiscover:Response>
                <autodiscover:Culture>en:us</autodiscover:Culture>
                <autodiscover:User>
                   <autodiscover:EMailAddress>' . $email . '</autodiscover:EMailAddress>
               </autodiscover:User>
               <autodiscover:Action>
                   <autodiscover:Error>
                       <Status>500</Status>
                       <Message>Unable to autoconfigure the supplied email address.</Message>
                       <DebugData>MailUser</DebugData>
                   </autodiscover:Error>
               </autodiscover:Action>
            </autodiscover:Response>
        </Autodiscover>';
    }

}

<?php
/**
 * Horde_ActiveSync_Request_Autodiscover::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2012-2014 Horde LLC (http://www.horde.org)
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
 * @copyright 2012-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 * @internal
 */
class Horde_ActiveSync_Request_Autodiscover extends Horde_ActiveSync_Request_Base
{
    /**
     * Handle request
     *
     * @return text  The content type of the response (text/xml).
     */
    public function handle(Horde_Controller_Request $request = null)
    {
        $parser = xml_parser_create();
        xml_parse_into_struct(
          $parser,
          $this->_decoder->getStream()->getString(),
          $values);

        // Get $_SERVER
        $server = $request->getServerVars();

        // Some broken clients *cough* android *cough* don't send the actual
        // XML data structure at all, but instead use the email address as
        // the username in the HTTP_AUTHENTICATION data. There are so many things
        // wrong with this, but try to work around it if we can.
        if (empty($values) && !empty($server['HTTP_AUTHORIZATION'])) {
            $hash = base64_decode(str_replace('Basic ', '', $server['HTTP_AUTHORIZATION']));
            if (strpos($hash, ':') !== false) {
                list($email, $pass) = explode(':', $hash, 2);
            }
        } elseif (empty($values)) {
            throw new Horde_Exception_AuthenticationFailure('No username provided.');
        } else {
            $email = $values[2]['value'];
        }

        // Need to override the username since AUTODISCOVER always uses email
        // address.
        $credentials = new Horde_ActiveSync_Credentials($this->_activeSync);
        $credentials->username = $email;

        if (!$this->_activeSync->authenticate($credentials)) {
            throw new Horde_Exception_AuthenticationFailure();
        }

        if (!empty($values)) {
            $params = array('request_schema' => trim($values[0]['attributes']['XMLNS']));
            // Response Schema is not in a set place.
            foreach ($values as $value) {
                if ($value['tag'] == 'ACCEPTABLERESPONSESCHEMA') {
                    $params['response_schema'] = trim($value['value']);
                    break;
                }
            }
        } else {
            // Assume broken clients want these schemas.
            $params = array(
              'request_schema' => 'http://schemas.microsoft.com/exchange/autodiscover/mobilesync/requestschema/2006',
              'response_schema' => 'http://schemas.microsoft.com/exchange/autodiscover/mobilesync/responseschema/2006'
            );
        }
        $results = $this->_driver->autoDiscover($params);
        if (empty($results['raw_xml'])) {
            $this->_encoder->getStream()->add($this->_buildResponseString($results));
        } else {
            // The backend is taking control of the XML.
            $this->_encoder->getStream()->add($results['raw_xml']);
        }

        return 'text/xml';
    }

    /**
     * Noop. This class overrides the handle method.
     */
    protected function _handle()
    {
    }

    /**
     * Build the appropriate response string to send back to the client.
     *
     * @param array $properties  An array containing any needed properties.
     *   Required properties for mobile sync:
     *   - request_schema:  The request schema sent by the client.
     *   - response_schema: The schema the client indicated it can accept.
     *   - culture: The culture value (normally 'en:en').
     *   - display_name:  The user's configured display name.
     *   - email: The user's email address.
     *   - url:  The url of the Microsoft-Servers-ActiveSync endpoint for this
     *           user to use.
     *
     *   Properties used for Outlook schema:
     *   - imap:  Array describing the IMAP server.
     *   - pop:   Array describing the POP3 server.
     *   - smtp:  Array describing the SMTP server.
     *
     *  @return string  The XML to return to the client.
     */
    protected function _buildResponseString($properties)
    {
        // Default response is for mobilesync.
        if (empty($properties['request_schema']) ||
            stripos($properties['request_schema'], 'autodiscover/mobilesync') !== false) {

            return '<?xml version="1.0" encoding="utf-8"?>
              <Autodiscover xmlns="http://schemas.microsoft.com/exchange/autodiscover/responseschema/2006">
                <Response xmlns="http://schemas.microsoft.com/exchange/autodiscover/mobilesync/responseschema/2006">
                  <Culture>' . $properties['culture'] . '</Culture>
                  <User>
                    <DisplayName>' . $properties['display_name'] . '</DisplayName>
                    <EMailAddress>' . $properties['email'] . '</EMailAddress>
                  </User>
                  <Action>
                    <Settings>
                      <Server>
                        <Type>MobileSync</Type>
                        <Url>' . $properties['url'] . '</Url>
                        <Name>' . $properties['url'] . '</Name>
                       </Server>
                    </Settings>
                  </Action>
                </Response>
              </Autodiscover>';
        } elseif (stripos($properties['request_schema'], 'autodiscover/outlook') !== false) {
            if (empty($properties['response_schema'])) {
                // Missing required response_schema
                return $this->_buildFailureResponse($properties['email'], '600', 'http://schemas.microsoft.com/exchange/autodiscover/outlook/responseschema/2006a');
            }

            $xml = '<Autodiscover xmlns="http://schemas.microsoft.com/exchange/autodiscover/responseschema/2006">
                <Response xmlns="' . $properties['response_schema'] . '">
                <User>
                    <DisplayName>' . $properties['display_name'] . '</DisplayName>
                </User>
                <Account>
                    <AccountType>email</AccountType>
                    <Action>settings</Action>';

            if (!empty($properties['imap'])) {
                $xml .= '<Protocol>
                    <Type>IMAP</Type>
                    <Server>' . $properties['imap']['host'] . '</Server>
                    <Port>' . $properties['imap']['port'] . '</Port>
                    <LoginName>' . $properties['username'] . '</LoginName>
                    <DomainRequired>off</DomainRequired>
                    <SPA>off</SPA>
                    <SSL>' . ($properties['imap']['ssl'] ? 'on' : 'off') . '</SSL>
                    <AuthRequired>on</AuthRequired>
                    </Protocol>';
            }
            if (!empty($properties['pop'])) {
                $xml .= '<Protocol>
                    <Type>POP3</Type>
                    <Server>' . $properties['pop']['host'] . '</Server>
                    <Port>' . $properties['pop']['port'] . '</Port>
                    <LoginName>' . $properties['username'] . '</LoginName>
                    <DomainRequired>off</DomainRequired>
                    <SPA>off</SPA>
                    <SSL>' . ($properties['pop']['ssl'] ? 'on' : 'off') . '</SSL>
                    <AuthRequired>on</AuthRequired>
                    </Protocol>';
            }
            if (!empty($properties['smtp'])) {
                $xml .= '<Protocol>
                    <Type>SMTP</Type>
                    <Server>' . $properties['smtp']['host'] . '</Server>
                    <Port>' . $properties['smtp']['port'] . '</Port>
                    <LoginName>' . $properties['username'] . '</LoginName>
                    <DomainRequired>off</DomainRequired>
                    <SPA>off</SPA>
                    <SSL>' . ($properties['smtp']['ssl'] ? 'on' : 'off') . '</SSL>
                    <AuthRequired>on</AuthRequired>
                    <UsePOPAuth>' . ($properties['smtp']['popauth'] ? 'on' : 'off') . '</UsePOPAuth>
                    </Protocol>';
            }
            $xml .= '</Account>
                </Response>
                </Autodiscover>';

            return $xml;
        } else {
          // Unknown request.
          return $this->_buildFailureResponse($properties['email'], '600', $properties['response_schema']);
        }
    }

    /**
     * Output failure response code.
     *
     * @param string $email   The email of the user attempting Autodiscover.
     * @param string $status  An appropriate status code for the error. E.g.,
     *                        600 - Invalid response.
     *                        601 - Provider not found for requested schema.
     * @param string $response_schema  The response schema value.
     *
     * @return string  The XML to send to the client.
     */
    protected function _buildFailureResponse($email, $status, $response_schema)
    {
        return '<?xml version="1.0" encoding="utf-8"?>
          <Autodiscover xmlns="http://schemas.microsoft.com/exchange/autodiscover/responseschema/2006">
            <Response xmlns="' . $response_schema . '">
              <Culture>en:us</Culture>
              <User>
                <EMailAddress>' . $email . '</EMailAddress>
              </User>
              <Action>
                <Error>
                  <Status>' . $status . '</Status>
                  <Message>Unable to autoconfigure the supplied email address.</Message>
                  <DebugData>MailUser</DebugData>
                </Error>
              </Action>
            </Response>
          </Autodiscover>';
    }

}

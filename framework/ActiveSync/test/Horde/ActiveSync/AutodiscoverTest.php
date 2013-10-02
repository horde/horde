<?php
/*
 * Unit tests for Horde_ActiveSync_Timezone utilities
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package ActiveSync
 */
class Horde_ActiveSync_AutoDiscoverTest extends Horde_Test_Case
{

    static protected $_server;
    static protected $_input;
    static protected $_driver;
    static protected $_request;

    public function setup()
    {
        self::$_driver = $this->getMockSkipConstructor('Horde_ActiveSync_Driver_Base');
        self::$_input = fopen('php://memory', 'wb+');
        $decoder = new Horde_ActiveSync_Wbxml_Decoder(self::$_input);
        $output = fopen('php://memory', 'wb+');
        $encoder = new Horde_ActiveSync_Wbxml_Encoder($output);
        $state = $this->getMockSkipConstructor('Horde_ActiveSync_State_Base');
        self::$_request = $this->getMockSkipConstructor('Horde_Controller_Request_Http');
        self::$_server = new Horde_ActiveSync(self::$_driver, $decoder, $encoder, $state, self::$_request);
    }


    /**
     * Tests autodiscover functionality when passed a proper XML data structure
     * containing an email address that needs to be mapped to a username.
     *
     */
    public function testAutodiscoverWithProperXML()
    {
        $request = <<<EOT
<?xml version="1.0" encoding="utf-8"?>
<Autodiscover xmlns="http://schemas.microsoft.com/exchange/autodiscover/mobilesync/requestschema/2006">
<Request>
<EMailAddress>mike@example.com</EMailAddress>
<AcceptableResponseSchema>
http://schemas.microsoft.com/exchange/autodiscover/mobilesync/responseschema/2006
</AcceptableResponseSchema>
</Request>
</Autodiscover>
EOT;
        fwrite(self::$_input, $request);
        rewind(self::$_input);

        // Mock the getUsernameFromEmail method to return 'mike' when 'mike@example.com'
        // is passed.
        self::$_driver->expects($this->once())
            ->method('getUsernameFromEmail')
            ->will($this->returnValueMap(array(array('mike@example.com', 'mike'))));

        // Mock authenticate to return true only if mike is passed as username.
        self::$_driver->expects($this->any())
            ->method('authenticate')
            ->will($this->returnValueMap(array(array('mike', '', null, true))));

        // Setup is called once, and must return true.
        self::$_driver->expects($this->once())
            ->method('setup')
            ->will($this->returnValue(true));

        // Checks that the correct schema was detected.
        $mock_driver_parameters = array(
            'request_schema' => 'http://schemas.microsoft.com/exchange/autodiscover/mobilesync/requestschema/2006',
            'response_schema' => 'http://schemas.microsoft.com/exchange/autodiscover/mobilesync/responseschema/2006');

        // ...and will only return this if it was.
        $mock_driver_results = array(
            'display_name' => 'Michael Rubinsky',
            'email' => 'mike@example.com',
            'culture' => 'en:en',
            'username' => 'mike',
            'url' => 'https://example.com/Microsoft-Server-ActiveSync'
        );

        self::$_driver->expects($this->once())
            ->method('autoDiscover')
            ->will($this->returnValueMap(array(array($mock_driver_parameters, $mock_driver_results))));

        self::$_server->handleRequest('Autodiscover', 'testdevice');

        // Test the results
        $expected = <<<EOT
<?xml version="1.0" encoding="utf-8"?>
              <Autodiscover xmlns="http://schemas.microsoft.com/exchange/autodiscover/responseschema/2006">
                <Response xmlns="http://schemas.microsoft.com/exchange/autodiscover/mobilesync/responseschema/2006">
                  <Culture>en:en</Culture>
                  <User>
                    <DisplayName>Michael Rubinsky</DisplayName>
                    <EMailAddress>mike@example.com</EMailAddress>
                  </User>
                  <Action>
                    <Settings>
                      <Server>
                        <Type>MobileSync</Type>
                        <Url>https://example.com/Microsoft-Server-ActiveSync</Url>
                        <Name>https://example.com/Microsoft-Server-ActiveSync</Name>
                       </Server>
                    </Settings>
                  </Action>
                </Response>
              </Autodiscover>
EOT;
        rewind(self::$_server->encoder->getStream());
        $this->assertEquals($expected, stream_get_contents(self::$_server->encoder->getStream()));

    }

    /**
     * Test workarounds for broken clients that don't send proper XML with
     * autodiscover requests. In this case, the user/email is taken from the
     * HTTP Basic auth data.
     */
    public function testAutodiscoverWithMissingXML()
    {
        // Basic auth: mike:password
        $auth = 'Basic bWlrZTpwYXNzd29yZA==';

        self::$_request->expects($this->any())
            ->method('getServerVars')
            ->will($this->returnValue(array('HTTP_AUTHORIZATION' => $auth)));

       // Mock the getUsernameFromEmail method to return 'mike' when 'mike'
        // is passed.
        self::$_driver->expects($this->once())
            ->method('getUsernameFromEmail')
            ->will($this->returnValueMap(array(array('mike', 'mike'))));

        // Mock authenticate to return true only if 'mike' is passed as username
        // and 'password' is passed as the password.
        self::$_driver->expects($this->any())
            ->method('authenticate')
            ->will($this->returnValueMap(array(array('mike', 'password', null, true))));

        // Setup is called once, and must return true.
        self::$_driver->expects($this->once())
            ->method('setup')
            ->will($this->returnValue(true));

        // Checks that the correct schema was detected.
        $mock_driver_parameters = array(
            'request_schema' => 'http://schemas.microsoft.com/exchange/autodiscover/mobilesync/requestschema/2006',
            'response_schema' => 'http://schemas.microsoft.com/exchange/autodiscover/mobilesync/responseschema/2006');

        // ...and will only return this if it was.
        $mock_driver_results = array(
            'display_name' => 'Michael Rubinsky',
            'email' => 'mike@example.com',
            'culture' => 'en:en',
            'username' => 'mike',
            'url' => 'https://example.com/Microsoft-Server-ActiveSync'
        );

        self::$_driver->expects($this->once())
            ->method('autoDiscover')
            ->will($this->returnValueMap(array(array($mock_driver_parameters, $mock_driver_results))));

        self::$_server->handleRequest('Autodiscover', 'testdevice');

        // Test the results
        $expected = <<<EOT
<?xml version="1.0" encoding="utf-8"?>
              <Autodiscover xmlns="http://schemas.microsoft.com/exchange/autodiscover/responseschema/2006">
                <Response xmlns="http://schemas.microsoft.com/exchange/autodiscover/mobilesync/responseschema/2006">
                  <Culture>en:en</Culture>
                  <User>
                    <DisplayName>Michael Rubinsky</DisplayName>
                    <EMailAddress>mike@example.com</EMailAddress>
                  </User>
                  <Action>
                    <Settings>
                      <Server>
                        <Type>MobileSync</Type>
                        <Url>https://example.com/Microsoft-Server-ActiveSync</Url>
                        <Name>https://example.com/Microsoft-Server-ActiveSync</Name>
                       </Server>
                    </Settings>
                  </Action>
                </Response>
              </Autodiscover>
EOT;
        rewind(self::$_server->encoder->getStream());
        $this->assertEquals($expected, stream_get_contents(self::$_server->encoder->getStream()));
    }

}

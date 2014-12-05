<?php
/*
 * Unit tests for Autodiscover functionality.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package ActiveSync
 */
class Horde_ActiveSync_AutoDiscoverTest extends Horde_Test_Case
{
    /**
     * Tests autodiscover functionality when passed a proper XML data structure
     * containing an email address that needs to be mapped to a username.
     *
     */
    public function testAutodiscoverWithProperXML()
    {
        $factory = new Horde_ActiveSync_Factory_TestServer();

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
        fwrite($factory->input, $request);
        rewind($factory->input);

        // Mock the getUsernameFromEmail method to return 'mike' when 'mike@example.com'
        // is passed.
        $factory->driver->expects($this->once())
            ->method('getUsernameFromEmail')
            ->will($this->returnValueMap(array(array('mike@example.com', 'mike'))));

        // Mock authenticate to return true only if mike is passed as username.
        $factory->driver->expects($this->any())
            ->method('authenticate')
            ->will($this->returnValueMap(array(array('mike', '', null, true))));

        // Setup is called once, and must return true.
        $factory->driver->expects($this->once())
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

        $factory->driver->expects($this->once())
            ->method('autoDiscover')
            ->will($this->returnValueMap(array(array($mock_driver_parameters, $mock_driver_results))));

        $factory->server->handleRequest('Autodiscover', 'testdevice');

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
        $factory->server->encoder->getStream()->rewind();
        $this->assertEquals($expected, $factory->server->encoder->getStream()->getString());
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
        $factory = new Horde_ActiveSync_Factory_TestServer();
        $factory->request->expects($this->any())
            ->method('getServerVars')
            ->will($this->returnValue(array('HTTP_AUTHORIZATION' => $auth)));

        // Mock the getUsernameFromEmail method to return 'mike' when 'mike'
        // is passed.
        $factory->driver->expects($this->once())
            ->method('getUsernameFromEmail')
            ->will($this->returnValueMap(array(array('mike', 'mike'))));

        // Mock authenticate to return true only if 'mike' is passed as username
        // and 'password' is passed as the password.
        $factory->driver->expects($this->any())
            ->method('authenticate')
            ->will($this->returnValueMap(array(array('mike', 'password', null, true))));

        // Setup is called once, and must return true.
        $factory->driver->expects($this->once())
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

        $factory->driver->expects($this->once())
            ->method('autoDiscover')
            ->will($this->returnValueMap(array(array($mock_driver_parameters, $mock_driver_results))));

        $factory->server->handleRequest('Autodiscover', 'testdevice');

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
        $factory->server->encoder->getStream()->rewind();
        $this->assertEquals($expected, $factory->server->encoder->getStream()->getString());
    }

}

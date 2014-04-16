<?php
/*
 * Unit tests for Horde_ActiveSync_Policies
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package ActiveSync
 */
class Horde_ActiveSync_Rfc822Test extends Horde_Test_Case
{
    public function testHeadersMultipartAlternativeAsString()
    {
        $fixture = file_get_contents(__DIR__ . '/fixtures/iOSMultipartAlternative.eml');
        $rfc822 = new Horde_ActiveSync_Rfc822($fixture);
        $test = $rfc822->getHeaders();
        $expected = array(
          'Subject' => 'Testing',
          'From' => 'mrubinsk@horde.org',
          'Content-Type' => 'multipart/alternative;
 boundary=Apple-Mail-B1C01B47-00D8-4AFB-8B65-DF81C4E4B47D',
          'Message-Id' => '<D492BB4F-6A2E-4E58-B607-4E8849A72919@horde.org>',
          'Date' => 'Tue, 1 Jan 2013 18:10:37 -0500',
          'To' => 'Michael Rubinsky <mike@theupstairsroom.com>',
          'Content-Transfer-Encoding' => '7bit',
          'Mime-Version' => '1.0 (1.0)',
          'User-Agent' => 'Horde Application Framework 5');

        $this->assertEquals($expected, $rfc822->getHeaders()->toArray());
      }

    public function testHeadersMultipartAlternativeAsStream()
    {
        $fixture = fopen(__DIR__ . '/fixtures/iOSMultipartAlternative.eml', 'r');
        $rfc822 = new Horde_ActiveSync_Rfc822($fixture);
        $test = $rfc822->getHeaders();
        $expected = array(
          'Subject' => 'Testing',
          'From' => 'mrubinsk@horde.org',
          'Content-Type' => 'multipart/alternative;
 boundary=Apple-Mail-B1C01B47-00D8-4AFB-8B65-DF81C4E4B47D',
          'Message-Id' => '<D492BB4F-6A2E-4E58-B607-4E8849A72919@horde.org>',
          'Date' => 'Tue, 1 Jan 2013 18:10:37 -0500',
          'To' => 'Michael Rubinsky <mike@theupstairsroom.com>',
          'Content-Transfer-Encoding' => '7bit',
          'Mime-Version' => '1.0 (1.0)',
          'User-Agent' => 'Horde Application Framework 5');

        $this->assertEquals($expected, $rfc822->getHeaders()->toArray());
        fclose($fixture);
    }

    public function testBaseMimePart()
    {
        $fixture = file_get_contents(__DIR__ . '/fixtures/iOSMultipartAlternative.eml');
        $rfc822 = new Horde_ActiveSync_Rfc822($fixture);
        $mimepart = $rfc822->getMimeObject();
        $expected =  array(
            'multipart/alternative',
            'text/plain',
            'text/html');

        $this->assertEquals($expected, $mimepart->contentTypeMap());
        $this->assertEquals(1, $mimepart->findBody('plain'));
        $this->assertEquals(2, $mimepart->findBody('html'));
    }

}
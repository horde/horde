<?php
/*
 * Unit tests for Horde_ActiveSync_Utils::
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package ActiveSync
 */
class Horde_ActiveSync_UtilsTest extends Horde_Test_Case
{
    public function testBase64Uri()
    {
        /* Provision Request for version 12.1 */
        $url = 'eRQJBBCuTs0Z9ZK6Vldwb/dM8JusBHx8TOgDUFBD';
        $results = Horde_ActiveSync_Utils::decodeBase64($url);
        $fixture = array(
            'ProtVer' => '12.1',
            'Cmd' => 'Provision',
            'Locale' => 1033,
            'DeviceId' => 'ae4ecd19f592ba5657706ff74cf09bac',
            'PolicyKey' => 3897326716,
            'DeviceType' => 'PPC'
        );
        $this->assertEquals($fixture, $results);

        /* Smart Forward */
        $url = 'eQIJBBCuTs0Z9ZK6Vldwb/dM8JusBHVeHIQDUFBDBwEBAwYxMTkyODEBBUlOQk9Y';
        $results = Horde_ActiveSync_Utils::decodeBase64($url);

        // This is binary data, test it separately.
        $fixture = array(
            'ProtVer' => '12.1',
            'Cmd' => 'SmartForward',
            'Locale' => 1033,
            'DeviceId' => 'ae4ecd19f592ba5657706ff74cf09bac',
            'PolicyKey' => 2216451701,
            'DeviceType' => 'PPC',
            'ItemId' => '119281',
            'CollectionId' => 'INBOX',
            'AcceptMultiPart' => false,
            'SaveInSent' => true
        );
        $this->assertEquals($fixture, $results);
    }

    public function testBodyTypePref()
    {
        $fixture = array(
            'bodyprefs' => array(Horde_ActiveSync::BODYPREF_TYPE_HTML => true, Horde_ActiveSync::BODYPREF_TYPE_MIME => true)
        );

        $this->assertEquals(Horde_ActiveSync::BODYPREF_TYPE_HTML, Horde_ActiveSync_Utils_Mime::getBodyTypePref($fixture));
        $this->assertEquals(Horde_ActiveSync::BODYPREF_TYPE_MIME, Horde_ActiveSync_Utils_Mime::getBodyTypePref($fixture, false));

        $fixture = array(
            'bodyprefs' => array(Horde_ActiveSync::BODYPREF_TYPE_HTML => true)
        );
        $this->assertEquals(Horde_ActiveSync::BODYPREF_TYPE_HTML, Horde_ActiveSync_Utils_Mime::getBodyTypePref($fixture));
        $this->assertEquals(Horde_ActiveSync::BODYPREF_TYPE_HTML, Horde_ActiveSync_Utils_Mime::getBodyTypePref($fixture, false));

        $fixture = array(
            'bodyprefs' => array(Horde_ActiveSync::BODYPREF_TYPE_HTML => true)
        );
        $this->assertEquals(Horde_ActiveSync::BODYPREF_TYPE_HTML, Horde_ActiveSync_Utils_Mime::getBodyTypePref($fixture));
        $this->assertEquals(Horde_ActiveSync::BODYPREF_TYPE_HTML, Horde_ActiveSync_Utils_Mime::getBodyTypePref($fixture, false));

        $fixture = array(
            'bodyprefs' => array(Horde_ActiveSync::BODYPREF_TYPE_MIME => true)
        );
        $this->assertEquals(Horde_ActiveSync::BODYPREF_TYPE_MIME, Horde_ActiveSync_Utils_Mime::getBodyTypePref($fixture));
        $this->assertEquals(Horde_ActiveSync::BODYPREF_TYPE_MIME, Horde_ActiveSync_Utils_Mime::getBodyTypePref($fixture, false));
    }
}
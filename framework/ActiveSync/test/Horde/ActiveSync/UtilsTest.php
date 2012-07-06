<?php
/*
 * Unit tests for Horde_ActiveSync_Timezone utilities
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
            'ProtVer' => 121,
            'Command' => 20,
            'Locale' => 1033,
            'DevIDLen' => 16,
            'DevID' => 'ae4ecd19f592ba5657706ff74cf09bac',
            'PolKeyLen' => 4,
            'PolKey' => 3897326716,
            'DevTypeLen' => 3,
            'DevType' => 'PPC'
        );
        $this->assertEquals($fixture, $results);

        /* Smart Forward */
        $url = 'eQIJBBCuTs0Z9ZK6Vldwb/dM8JusBHVeHIQDUFBDBwEBAwYxMTkyODEBBUlOQk9Y';
        $results = Horde_ActiveSync_Utils::decodeBase64($url);

        // This is binary data, test it separately.
        $this->assertEquals('01', bin2hex($results['Options']));
        unset($results['Options']);
        $fixture = array(
            'ProtVer' => 121,
            'Command' => 2,
            'Locale' => 1033,
            'DevIDLen' => 16,
            'DevID' => 'ae4ecd19f592ba5657706ff74cf09bac',
            'PolKeyLen' => 4,
            'PolKey' => 2216451701,
            'DevTypeLen' => 3,
            'DevType' => 'PPC',
            'ItemId' => 119281,
            'CollectionId' => 'INBOX'
        );
        $this->assertEquals($fixture, $results);
    }

}
<?php
class Horde_Test_Functional extends Horde_Test_Case
{
    /**
     * Test two XML strings for equivalency (e.g., identical up to reordering of attributes).
     */
    public function assertDomEquals($expected, $actual, $message = null)
    {
        $expectedDom = new DOMDocument();
        $expectedDom->loadXML($expected);

        $actualDom = new DOMDocument();
        $actualDom->loadXML($actual);

        $this->assertEquals($expectedDom->saveXML(), $actualDom->saveXML(), $message);
    }

}

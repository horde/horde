<?php
/**
 *
 */
class Kronolith_Resource
{
    /* ResponseType constants */
    const RESPONSETYPE_NONE = 0;
    const RESPONSETYPE_AUTO = 1;
    const RESPONSETYPE_ALWAYS_ACCEPT = 2;
    const RESPONSETYPE_ALWAYS_DECLINE = 3;
    const RESPONSETYPE_MANUAL = 4; // Send iTip - not sure how that would work without a user account for the resource.

    /**
     *
     */
    public function factory($driver, $params)
    {

    }

}
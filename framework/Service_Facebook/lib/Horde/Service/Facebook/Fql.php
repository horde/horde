<?php
/**
 * Execute FQL  queries
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Service_Facebook
 */
class Horde_Service_Facebook_Fql extends Horde_Service_Facebook_Base
{
    /**
     * Run a FQL query, optionally including the current session_key.
     *
     * http://developers.facebook.com/documentation.php?v=1.0&doc=fql
     *
     * @param string $query             The FQL to run.
     *
     * @return array Hashes containing results.
     */
    public function run($query)
    {
        $params = array('query' => $query);

        return $this->_facebook->callMethod('facebook.fql.query', $params);
    }

}
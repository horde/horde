<?php
/**
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Service_Scribd
 */

/**
 * Scribd response class
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Service_Scribd
 */
class Horde_Service_Scribd_Response extends Horde_Xml_Element
{
    /**
     * Constructor. Do basic error checking on the resposne.
     *
     * @param DOMElement $element The DOM element we're encapsulating.
     */
    public function __construct($element = null)
    {
        parent::__construct($element);

        if ($this['stat'] != 'ok') {
            throw new Horde_Service_Scribd_Exception($this->error['message'], $this->error['code']);
        }
    }

    /*
            if($result['stat'] == "ok"){

                //This is shifty. Works currently though.
                $result = $this->convert_simplexml_to_array($result);
                if(urlencode((string)$result) == "%0A%0A" && $this->error == 0){
                    $result = "1";
                    return $result;
                }else{
                    return $result;
                }
            }
    */

    public function getResultSet()
    {
        return new Horde_Service_Scribd_ResultSet($this->resultset);
    }

}

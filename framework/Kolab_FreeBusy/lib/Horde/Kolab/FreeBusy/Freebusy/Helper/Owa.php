<?php
/**
 * Parser for the Outlook web access XML format.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Mathieu Parent <math.parent@gmail.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Parser for the Outlook web access XML format.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 * Copyright 2011 Kolab Systems AG
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Mathieu Parent <math.parent@gmail.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Freebusy_Helper_Owa
{
    /** OWA status codes */
    const FREE        = 0;
    const TENTATIVE   = 1;
    const BUSY        = 2;
    const OUTOFOFFICE = 3;

    /**
     * The XML document this driver works with.
     *
     * @var DOMDocument
     */
    private $_document;

    /**
     * Constructor
     *
     * @param string|resource $input The input data.
     *
     * @throws Horde_Kolab_FreeBusy_Exception If the input is invalid.
     */
    public function __construct($input)
    {
        if (is_resource($input)) {
            rewind($input);
            $input = stream_get_contents($input);
        }
        $this->_document = new DOMDocument('1.0', 'UTF-8');
        $this->_document->preserveWhiteSpace = false;
        $this->_document->formatOutput       = true;
        $result = @$this->_document->loadXML($input);
        if (!$result || empty($this->_document->documentElement)) {
            throw new Horde_Kolab_FreeBusy_Exception('Invalid OWA input!');
        }
    }

    /**
     * Return the XML document.
     *
     * @return string The complete XML document.
     */
    public function __toString()
    {
        return $this->_document->saveXML();
    }

    /**
     * Convert the free/busy data from the XML to an array.
     *
     * @param Horde_Date $start    The start of the requested free/busy data.
     * @param Horde_Date $end      The end of the requested free/busy data.
     * @param int        $interval The interval of the data.
     *
     * @return array The data array representing the XML data.
     */
    public function convert($start, $end, $interval)
    {
        $noderoot = $this->_document->documentElement;
        if (empty($noderoot) || empty($noderoot->childNodes)) {
            throw new Horde_Kolab_FreeBusy_Exception('Invalid OWA input!');
        }

        $children = $noderoot->childNodes;
        if ($children->length != 1 || $children->item(0)->tagName != 'a:recipients') {
            throw new Horde_Kolab_FreeBusy_Exception('Invalid OWA input!');
        }

        $items = array();

        $nodes = $children->item(0)->childNodes;
        for ($i = 0; $i < $nodes->length; $i++) {
            $node = $nodes->item($i);
            if ($node->tagName != 'a:item') {
                continue;
            }
            $values = $node->childNodes;
            $item = array();
            for ($j = 0; $j < $values->length; $j++) {
                $item[$values->item($j)->tagName] = $values->item($j)->textContent;
            }
            $items[] = $item;
        }

        $object = array();
        $object['start-date'] = $start;
        $object['end-date'] = $end;
        
        foreach($items as $n => $item) {
            if(!empty($item['a:fbdata']) && !empty($item['a:email'])) {
                $fbdata = $item['a:fbdata'] . self::FREE;
                $prev = '0';
                $count = 0;
                $start_date = $start;
                $fbparsed = array();
                for($i = 0; $i < strlen($fbdata) ; $i++) {
                    if($fbdata[$i] === $prev) {
                        $count++;
                        continue;
                    } else {
                        if($count) {
                            $end_date = $start_date->add(
                                $count * $interval * 60
                            );
                            if($prev != self::FREE) {
                                $fbparsed[] = array(
                                    'start-date' => $start_date,
                                    'end-date' => $end_date,
                                    'show-time-as' => $this->_stateAsString(
                                        $prev
                                    )
                                );
                            }
                            $start_date = $end_date;
                        }
                        $count = 1;
                        $prev = $fbdata[$i];
                    }
                }
                $object[$item['a:email']] = $fbparsed;
            }
        }
        
        return $object;
    }
    
    /**
     * Return state value, based on state code
     *
     * @param string $code  State code.
     *
     * @return string  State string
     */
    private function _stateAsString($code)
    {
        switch($code) {
        case self::FREE:
            return 'free';
        case self::TENTATIVE:
            return 'tentative';
        case self::BUSY:
            return 'busy';
        case self::OUTOFOFFICE:
            return 'outofoffice';
        default:
            return 'unknown';
        }
    }
  
}
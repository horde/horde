<?php
/**
 * Horde_ActiveSync_Device_Ios::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2015-2016 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Contains constants and maps related to iOS devices.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2015-2016 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Device_Ios
{
    /**
     * Mapping of regexps to match a ios agent string.
     *
     * @var array
     */
    static public $VERSION_MAP = array(
        "801.293" => "4.0",
        "801.306" => "4.0.1",
        "801.400" => "4.0.2",
        "802.117" => "4.1",
        "802.118" => "4.1",
        "803.148" => "4.2.1",
        "803.14800001" => "4.2.1",
        "805.128" => "4.2.5",
        "805.200" => "4.2.6",
        "805.303" => "4.2.7",
        "805.401" => "4.2.8",
        "805.501" => "4.2.9",
        "805.600" => "4.2.10",
        "806.190" => "4.3",
        "806.191" => "4.3",
        "807.4" => "4.3.1",
        "808.7" => "4.3.2",
        "808.8" => "4.3.2",
        "810.2" => "4.3.3",
        "810.3" => "4.3.3",
        "811.2" => "4.3.4",
        "812.1" => "4.3.5",
        "901.334" => "5.0",
        "901.40\d+" => "5.0.1",
        "902.17\d+" => "5.1",
        "902.206" => "5.1.1",
        "1001.40\d+" => "6.0",
        "1001.52\d+" => "6.0.1",
        "1002.14\d+"=> "6.1",
        "1002.146" => "6.1.2",
        "1002.329" => "6.1.3",
        "1002.350" => "6.1.3",
        "1101.465" => "7.0",
        "1101.470"=>"7.0.1",
        "1101.47000001"=>"7.0.1",
        "1101.501"=>"7.0.2",
        "1102.511" => "7.0.3",
        "1102.55400001" => "7.0.4",
        "1102.601" => "7.0.5",
        "1102.651" => "7.0.6",
        "1104.167" => "7.1",
        "1104.169" => "7.1",
        "1104.201" => "7.1.1",
        "1104.257" => "7.1.2",
    );
}
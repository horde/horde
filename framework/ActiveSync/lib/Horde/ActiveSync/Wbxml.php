<?php
/**
 * ActiveSync specific WBXML handling. This (and all related code) needs to be
 * refactored to use XML_WBXML, or the H4 equivelant when it is written...
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_ActiveSync
 */

/**
 * File      :   wbxml.php
 * Project   :   Z-Push
 * Descr     :   WBXML mapping file
 *
 * Created   :   01.10.2007
 *
 * � Zarafa Deutschland GmbH, www.zarafaserver.de
 * This file is distributed under GPL v2.
 * Consult LICENSE file for details
 */
class Horde_ActiveSync_Wbxml
{
    // @TODO - debug should be a config parameter
    const DEBUG = false;
    const SWITCH_PAGE =     0x00;
    const END =             0x01;
    const ENTITY =          0x02;
    const STR_I =           0x03;
    const LITERAL =         0x04;
    const EXT_I_0 =         0x40;
    const EXT_I_1 =         0x41;
    const EXT_I_2 =         0x42;
    const PI =              0x43;
    const LITERAL_C =       0x44;
    const EXT_T_0 =         0x80;
    const EXT_T_1 =         0x81;
    const EXT_T_2 =         0x82;
    const STR_T =           0x83;
    const LITERAL_A =       0x84;
    const EXT_0 =           0xC0;
    const EXT_1 =           0xC1;
    const EXT_2 =           0xC2;
    const OPAQUE =          0xC3;
    const LITERAL_AC =      0xC4;

    const EN_TYPE =                1;
    const EN_TAG =                 2;
    const EN_CONTENT =             3;
    const EN_FLAGS =               4;
    const EN_ATTRIBUTES =          5;
    const EN_TYPE_STARTTAG =       1;
    const EN_TYPE_ENDTAG =         2;
    const EN_TYPE_CONTENT =        3;
    const EN_FLAGS_CONTENT =       1;
    const EN_FLAGS_ATTRIBUTES =    2;
}
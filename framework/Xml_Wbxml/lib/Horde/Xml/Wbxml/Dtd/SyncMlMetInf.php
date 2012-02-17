<?php
/**
 * From Binary XML Content Format Specification Version 1.3, 25 July 2001
 * found at http://www.wapforum.org
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Anthony Mills <amills@pyramid6.com>
 * @package Xml_Wbxml
 */
class Horde_Xml_Wbxml_Dtd_SyncMlMetInf extends Horde_Xml_Wbxml_Dtd
{
    public function init()
    {
        /* this code table has been extracted from libwbxml
         * (see http://libwbxml.aymerick.com/) by using
         *
         * grep '\"[^\"]*\", *0x.., 0x.. },' wbxml_tables.c
         * | sed -e 's#^.*\"\([^\"]*\)\", *\(0x..\), \(0x..\) },.*$#        \$this->setTag\(\3, \"\1\"\); // \2#g'
         */

        $this->setTag(0x05, "Anchor"); // 0x01
        $this->setTag(0x06, "EMI"); // 0x01
        $this->setTag(0x07, "Format"); // 0x01
        $this->setTag(0x08, "FreeID"); // 0x01
        $this->setTag(0x09, "FreeMem"); // 0x01
        $this->setTag(0x0a, "Last"); // 0x01
        $this->setTag(0x0b, "Mark"); // 0x01
        $this->setTag(0x0c, "MaxMsgSize"); // 0x01
        $this->setTag(0x15, "MaxObjSize"); // 0x01
        $this->setTag(0x0d, "Mem"); // 0x01
        $this->setTag(0x0e, "MetInf"); // 0x01
        $this->setTag(0x0f, "Next"); // 0x01
        $this->setTag(0x10, "NextNonce"); // 0x01
        $this->setTag(0x11, "SharedMem"); // 0x01
        $this->setTag(0x12, "Size"); // 0x01
        $this->setTag(0x13, "Type"); // 0x01
        $this->setTag(0x14, "Version"); // 0x01
        $this->setTag(0x15, "MaxObjSize"); // 0x01
        $this->setTag(0x16, "FieldLevel"); // 0x01

        if ($this->version == 1) {
            $this->setCodePage(0, Horde_Xml_Wbxml_Dtd::SYNCML_1_1, 'syncml:syncml1.1');
            $this->setCodePage(1, Horde_Xml_Wbxml_Dtd::METINF_1_1, 'syncml:metinf1.1');
            $this->setURI('syncml:metinf1.1');
        } elseif ($this->version == 2) {
            $this->setCodePage(0, Horde_Xml_Wbxml_Dtd::SYNCML_1_2, 'syncml:syncml1.2');
            $this->setCodePage(1, Horde_Xml_Wbxml_Dtd::METINF_1_2, 'syncml:metinf1.2');
            $this->setURI('syncml:metinf1.2');
        } else {
            $this->setCodePage(0, Horde_Xml_Wbxml_Dtd::SYNCML_1_0, 'syncml:syncml1.0');
            $this->setCodePage(1, Horde_Xml_Wbxml_Dtd::METINF_1_0, 'syncml:metinf1.0');
            $this->setURI('syncml:metinf1.0');
        }
    }
}

<?php
/**
 * Horde_Pdf package
 *
 * @license  http://www.horde.org/licenses/lgpl21
 * @category Horde
 * @package  Pdf
 */

/**
 * Font width definition
 *
 * @category Horde
 * @package  Pdf
 */
class Horde_Pdf_Font_Courierbi
{
    public function getWidths()
    {
        $fontWidths = array();
        for ($i = 0; $i <= 255; $i++) {
            $fontWidths['courierBI'][chr($i)] = 600;
        }
        return $fontWidths;
    }
}

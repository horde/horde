<?php
/**
 * Horde_Pdf package
 *
 * @license  http://opensource.org/licenses/lgpl-license.php
 * @category Horde
 * @package  Horde_Pdf
 */

/**
 * Font width definition
 *
 * @category Horde
 * @package  Horde_Pdf
 */
class Horde_Pdf_Font_Courier
{

    public function getWidths()
    {
        $fontWidths = array();
        for ($i = 0; $i <= 255; $i++) {
            $fontWidths['courier'][chr($i)] = 600;
        }
        $fontWidths['courierB']  = $fontWidths['courier'];
        $fontWidths['courierI']  = $fontWidths['courier'];
        $fontWidths['courierBI'] = $fontWidths['courier'];

        return $fontWidths;
    }

}

<?php
/**
 * Factory for getting list of all available pre-defined styles.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
class Ansel_Factory_Styles extends Horde_Core_Factory_Injector
{
    public function create (Horde_Injector $injector)
    {
        /* Brings in the $styles array in this scope only */
        $styles = Horde::loadConfiguration('styles.php', 'styles', 'ansel');

        /* No prettythumbs allowed at all by admin choice */
        if (empty($GLOBALS['conf']['image']['prettythumbs'])) {
            $test = $styles;
            foreach ($test as $key => $style) {
                if ($style['thumbstyle'] != 'Thumb') {
                    unset($styles[$key]);
                }
            }
        }

        /* Check if the browser / server has png support */
        if ($GLOBALS['conf']['image']['type'] != 'png') {
            $test = $styles;
            foreach ($test as $key => $style) {
                if (!empty($style['requires_png'])) {
                    if (!empty($style['fallback'])) {
                        $styles[$key] = $styles[$style['fallback']];
                    } else {
                        unset($styles[$key]);
                    }
                }
            }
        }

        return $styles;
    }

}

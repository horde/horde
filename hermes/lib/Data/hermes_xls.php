<?php

require_once HERMES_BASE . '/lib/Data/hermes_tsv.php';

/**
 * The Horde_Data_hermes_xls class extends Horde's TSV Data class with
 * Hermes-specific handling and a few tweaks for files to open
 * directly in Excel.
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Data
 */
class Horde_Data_hermes_xls extends Horde_Data_hermes_tsv {

    var $_extension = 'xls';
    var $_contentType = 'application/msexcel';

}

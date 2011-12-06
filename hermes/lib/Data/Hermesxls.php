<?php
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
class Hermes_Data_Hermesxls extends Hermes_Data_Hermestsv
{
    protected $_extension = 'xls';
    protected $_contentType = 'application/msexcel';
}

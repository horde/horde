<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2006-2011 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_View
 * @subpackage Helper
 */

/**
 * View helpers for URLs
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_View
 * @subpackage Helper
 */
class Horde_View_Helper_Number extends Horde_View_Helper_Base
{
    /**
     * Formats the bytes in $size into a more understandable representation.
     * Useful for reporting file sizes to users. This method returns NULL if
     * $size cannot be converted into a number. You can change the default
     * precision of 1 in $precision.
     *
     *   $this->numberToHumanSize(123)           => 123 Bytes
     *   $this->numberToHumanSize(1234)          => 1.2 KB
     *   $this->numberToHumanSize(12345)         => 12.1 KB
     *   $this->numberToHumanSize(1234567)       => 1.2 MB
     *   $this->numberToHumanSize(1234567890)    => 1.1 GB
     *   $this->numberToHumanSize(1234567890123) => 1.1 TB
     *   $this->numberToHumanSize(1234567, 2)    => 1.18 MB
     *
     * @param  integer|float  $size        Size to format
     * @param  integer        $preceision  Level of precision
     * @return string                      Formatted size value
     */
    public function numberToHumanSize($size, $precision = 1)
    {
        if (! is_numeric($size)) {
            return null;
        }

        if ($size == 1) {
            $size = '1 Byte';
        } elseif ($size < 1024) {
            $size = sprintf('%d Bytes', $size);
        } elseif ($size < 1048576) {
            $size = sprintf("%.{$precision}f KB", $size / 1024);
        } elseif ($size < 1073741824) {
            $size = sprintf("%.{$precision}f MB", $size / 1048576);
        } elseif ($size < 1099511627776) {
            $size = sprintf("%.{$precision}f GB", $size / 1073741824);
        } else {
            $size = sprintf("%.{$precision}f TB", $size / 1099511627776);
        }

        return str_replace('.0', '', $size);
    }

}

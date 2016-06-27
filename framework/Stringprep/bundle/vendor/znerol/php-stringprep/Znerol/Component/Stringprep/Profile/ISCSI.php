<?php

namespace Znerol\Component\Stringprep\Profile;
use Znerol\Component\Stringprep\Profile;

/**
 * Stringprep profile for iSCSI defined in RFC 3722
 */
class ISCSI extends Profile
{
  /**
   * Use RFC3454 table B.1
   */
  protected $removeZWS = true;

  /**
   * Remap characters using RFC3454 table B.2
   */
  protected $casefold = self::CASEFOLD_B_2;

  /**
   * Normalize to NFKC
   */
  protected $normalize = self::NORM_NFKC;

  /**
   * An array of tables from RFC3454 appendix C.
   */
  protected $prohibit = array(
    self::PROHIBIT_C_1_1,
    self::PROHIBIT_C_1_2,
    self::PROHIBIT_C_2_1,
    self::PROHIBIT_C_2_2,
    self::PROHIBIT_C_3,
    self::PROHIBIT_C_4,
    self::PROHIBIT_C_5,
    self::PROHIBIT_C_6,
    self::PROHIBIT_C_7,
    self::PROHIBIT_C_8,
    self::PROHIBIT_C_9
  );

  /**
   * If set, the mechanism for checking bidirectional strings described in RFC3454 
   * is applied.
   */
  protected $checkbidi = true;

  /**
   * Prohibit invalid codepoints.
   */
  protected function prohibit($codepoints) {
    parent::prohibit($codepoints);
    array_walk($codepoints, 'static::validateCodepoint', 'static::iscisProhibitFilter');
  }

  /**
   * RFC 3722, Section 6.1, 6.2
   */
  protected static function iscisProhibitFilter($cp) {
    if ($cp == 0x3002) return false;
    if ($cp >= 0x0000 && $cp <= 0x002C) return false;
    if ($cp == 0x002F) return false;
    if ($cp >= 0x003B && $cp <= 0x0040) return false;
    if ($cp >= 0x005B && $cp <= 0x0060) return false;
    if ($cp >= 0x007B && $cp <= 0x007F) return false;
    return true;
  }
}

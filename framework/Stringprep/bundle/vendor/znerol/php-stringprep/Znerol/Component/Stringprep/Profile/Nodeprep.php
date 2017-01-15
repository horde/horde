<?php

namespace Znerol\Component\Stringprep\Profile;
use Znerol\Component\Stringprep\Profile;

/**
 * XMPP Nodeprep profile for nameprep defined in RFC 6122, Appendix A
 */
class Nodeprep extends Profile
{
  /**
   * Use RFC3454 table B.1
   */
  protected $removeZWS = true;

  /**
   * Use RFC3454 table B.2
   */
  protected $casefold = self::CASEFOLD_B_2;

  /**
   * Normalize to NKFC
   */
  protected $normalize = self::NORM_NFKC;

  /**
   * Prohibited output
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
    array_walk($codepoints, 'static::validateCodepoint', 'static::nodeprepProhibitFilter');
  }

  /**
   * RFC 6122, Section A.5
   */
  protected static function nodeprepProhibitFilter($cp) {
    if ($cp == 0x0022) return false;
    if ($cp == 0x0026) return false;
    if ($cp == 0x0027) return false;
    if ($cp == 0x002F) return false;
    if ($cp == 0x003A) return false;
    if ($cp == 0x003C) return false;
    if ($cp == 0x003E) return false;
    if ($cp == 0x0040) return false;
    return true;
  }
}

<?php

namespace Znerol\Component\Stringprep\Profile;
use Znerol\Component\Stringprep\Profile;

/**
 * Stringprep profile for nameprep defined in RFC 3491
 */
class Nameprep extends Profile
{
  /**
   * If set to true the characters from RFC3454 table B.1 are removed from the 
   * output.
   */
  protected $removeZWS = true;

  /**
   * One of CASEFOLD_NONE, CASEFOLD_B2, CASEFOLD_B3.
   */
  protected $casefold = self::CASEFOLD_B_2;

  /**
   * Whether to apply string normalization (NFKC)
   */
  protected $normalize = self::NORM_NFKC;

  /**
   * An array of tables from RFC3454 appendix C.
   */
  protected $prohibit = array(
    self::PROHIBIT_C_1_2,
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
}

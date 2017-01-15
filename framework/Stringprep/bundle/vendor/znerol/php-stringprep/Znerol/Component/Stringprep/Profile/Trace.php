<?php

namespace Znerol\Component\Stringprep\Profile;
use Znerol\Component\Stringprep\Profile;

/**
 * The "trace" Profile of "Stringprep" as defined in RFC 4505
 */
class Trace extends Profile
{
  /**
   * Do not use RFC3454 table B.1
   */
  protected $removeZWS = false;

  /**
   * No casefolding
   */
  protected $casefold = self::CASEFOLD_NONE;

  /**
   * No normalization
   */
  protected $normalize = self::NORM_NONE;

  /**
   * Prohibited output
   */
  protected $prohibit = array(
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
   * Require checking bidirectional characters
   */
  protected $checkbidi = true;
}

<?php

namespace Znerol\Component\Stringprep\Profile;
use Znerol\Component\Stringprep\Profile;

/**
 * XMPP PolicyMIB profile for stringprep defined in RFC 4011, Section 9.1.1
 */
class PolicyMIB extends Profile
{
  /**
   * Use RFC3454 table B.1
   */
  protected $removeZWS = true;

  /**
   * No casefolding
   */
  protected $casefold = self::CASEFOLD_NONE;

  /**
   * The Unicode normalization used: Form KC
   */
  protected $normalize = self::NORM_NFKC;

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
   * Bidirectional character handling: not performed
   */
  protected $checkbidi = false;
}

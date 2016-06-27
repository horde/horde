<?php

namespace Znerol\Component\Stringprep\Profile;
use Znerol\Component\Stringprep\Profile;

/**
 * XMPP Resourceprep profile for stringprep defined in RFC 6122, Appendix B
 */
class Resourceprep extends Profile
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
   * Normalize to NKFC
   */
  protected $normalize = self::NORM_NFKC;

  /**
   * Prohibited output
   */
  protected $prohibit = array(
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
}

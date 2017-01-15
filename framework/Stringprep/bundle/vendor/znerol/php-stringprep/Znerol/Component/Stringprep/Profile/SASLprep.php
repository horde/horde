<?php

namespace Znerol\Component\Stringprep\Profile;
use Znerol\Component\Stringprep\Profile;

/**
 * SASLprep: Stringprep Profile for User Names and Passwords, RFC 4013
 */
class SASLprep extends Profile
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
   * Apply mapping (RFC 3454 section 3)
   */
  protected function map($codepoints) {
    $codepoints = parent::map($codepoints);
    $codepoints = static::applyMappingTable($codepoints, 'static::saslprepMapUnicodeSpaceToSpace');
    return $codepoints;
  }

  /**
   * In RFC 4013, Table C.1.2 of RFC 3454 is abused as a mapping table.
   */
  protected static function saslprepMapUnicodeSpaceToSpace($cp) {
    $map = array(
      0x0000A0 => array(0x000020),
      0x001680 => array(0x000020),
      0x002000 => array(0x000020),
      0x002001 => array(0x000020),
      0x002002 => array(0x000020),
      0x002003 => array(0x000020),
      0x002004 => array(0x000020),
      0x002005 => array(0x000020),
      0x002006 => array(0x000020),
      0x002007 => array(0x000020),
      0x002008 => array(0x000020),
      0x002009 => array(0x000020),
      0x00200A => array(0x000020),
      0x00200B => array(0x000020),
      0x00202F => array(0x000020),
      0x00205F => array(0x000020),
      0x003000 => array(0x000020),
    );
    return isset($map[$cp]) ? $map[$cp] : array($cp);
  }
}

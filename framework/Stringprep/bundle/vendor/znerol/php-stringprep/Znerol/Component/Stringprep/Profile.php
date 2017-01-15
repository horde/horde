<?php

namespace Znerol\Component\Stringprep;

use Znerol\Component\Stringprep\UnicodeUtil;
use Znerol\Component\Stringprep\ProfileException;
use Znerol\Component\Stringprep\RFC3454;

require_once('RFC3454/A_1.php');

class Profile{
  const MODE_QUERY = 0,
        MODE_STORE = 1;

  const CASEFOLD_NONE = 0,
        CASEFOLD_B_2 = 2,
        CASEFOLD_B_3 = 3;

  const NORM_NONE = 0,
        NORM_NFKC = 1;

  const PROHIBIT_C_1_1 = 'C_1_1',
        PROHIBIT_C_1_2 = 'C_1_2',
        PROHIBIT_C_2_1 = 'C_2_1',
        PROHIBIT_C_2_2 = 'C_2_2',
        PROHIBIT_C_3 = 'C_3',
        PROHIBIT_C_4 = 'C_4',
        PROHIBIT_C_5 = 'C_5',
        PROHIBIT_C_6 = 'C_6',
        PROHIBIT_C_7 = 'C_7',
        PROHIBIT_C_8 = 'C_8',
        PROHIBIT_C_9 = 'C_9';

  /**
   * If set to true the characters from RFC3454 table B.1 are removed from the 
   * output.
   */
  protected $removeZWS = true;

  /**
   * One of CASEFOLD_NONE, CASEFOLD_B2, CASEFOLD_B3.
   */
  protected $casefold = self::CASEFOLD_NONE;

  /**
   * Whether to apply string normalization (NFKC)
   */
  protected $normalize = self::NORM_NONE;

  /**
   * An array of tables from RFC3454 appendix C.
   */
  protected $prohibit = array();

  /**
   * If set, the mechanism for checking bidirectional strings described in RFC3454 
   * is applied.
   */
  protected $checkbidi = false;


  /**
   * Apply this profile to a given string.
   */
  public function apply($string, $encoding = 'UTF-8', $mode = self::MODE_STORE) {
    $codepoints = UnicodeUtil::stringToCodepoints($string);

    if ($mode == self::MODE_STORE) {
      $codepoints = array_filter($codepoints, 'Znerol\Component\Stringprep\RFC3454\A_1::filter');
    }

    $codepoints = $this->map($codepoints);
    $codepoints = $this->normalize($codepoints);
    $this->prohibit($codepoints);
    $this->checkbidi($codepoints);
    return UnicodeUtil::codepointsToString($codepoints);
  }


  /**
   * Apply mapping (RFC 3454 section 3)
   */
  protected function map($codepoints) {
    if ($this->removeZWS) {
      $codepoints = array_filter($codepoints, 'Znerol\Component\Stringprep\RFC3454\B_1::filter');
    }

    switch ($this->casefold) {
    case self::CASEFOLD_B_2:
      $codepoints = static::applyMappingTable($codepoints, 'Znerol\Component\Stringprep\RFC3454\B_2::map');
      break;
    case self::CASEFOLD_B_3:
      $codepoints = static::applyMappingTable($codepoints, 'Znerol\Component\Stringprep\RFC3454\B_3::map');
      break;
    }

    return $codepoints;
  }


  /**
   * Normalize string (RFC 3454 section 4)
   */
  protected function normalize($codepoints) {
    $string = UnicodeUtil::codepointsToString($codepoints, 'UTF-8');
    $string = \Normalizer::normalize($string, \Normalizer::FORM_KC);
    return UnicodeUtil::stringToCodepoints($string, 'UTF-8');
  }


  /**
   * Check for prohibited output (RFC 3454 section 5)
   */
  protected function prohibit($codepoints) {
    foreach ($this->prohibit as $prohibit) {
      array_walk($codepoints, 'static::validateCodepoint', 'Znerol\Component\Stringprep\RFC3454\\' . $prohibit. '::filter');
    }
  }


  /**
   * Check for bidirectional text (RFC 3454 section 6)
   *
   * * If a string contains any RandALCat character (Table D.1), the string 
   *   MUST NOT contain any LCat (Table D.2) character.
   * * If a string contains any RandALCat character, a RandALCat character MUST 
   *   be the first character of the string, and a RandALCat character MUST be 
   *   the last character of the string.
   */
  protected function checkbidi($codepoints) {
    if (!count($codepoints)) {
      return;
    }

    $RALmode = !RFC3454\D_1::filter(reset($codepoints));
    if ($RALmode) {
      if (RFC3454\D_1::filter($codepoints[count($codepoints)-1])) {
        throw new ProfileException('Invalid bidirectional text');
      }
      // Ensure that there are'nt any L characters in this string.
      array_walk($codepoints, 'static::validateCodepoint', 'Znerol\Component\Stringprep\RFC3454\D_2::filter');
    }
    else {
      // Ensure that there are'nt any R/AL characters in this string.
      array_walk($codepoints, 'static::validateCodepoint', 'Znerol\Component\Stringprep\RFC3454\D_1::filter');
    }
  }


  /**
   * Helper method: Apply a mapping table function to the given array of 
   * codepoints.
   */
  static protected function applyMappingTable($codepoints, $func) {
    if (count($codepoints)) {
      return call_user_func_array('array_merge', array_map($func, $codepoints));
    }
    else {
      return $codepoints;
    }
  }


  /**
   * Helper method: Check if the given codepoint is prohibited by a table.
   */
  static protected function validateCodepoint($codepoint, $index, $func) {
    if (!call_user_func($func, $codepoint)) {
      throw new ProfileException(sprintf('Codepoint %x prohibited', $codepoint));
    }
  }
}

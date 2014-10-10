<?php

namespace Znerol\Component\Stringprep;

class UnicodeUtil {
  /**
   * Convert the given string into an array of unicode code points.
   *
   * Because this method uses the PHP function unpack, it returns a 1-based 
   * array. If a zero-based array is important for your application, process 
   * the output of this function using e.g. array_values.
   */
  public static function stringToCodepoints($string, $encoding = 'UTF-8') {
    return unpack('V*', iconv($encoding, "UTF-32LE", $string));
  }

  /**
   * Convert an array of unicode code points into a string.
   */
  public static function codepointsToString($codepoints, $encoding = 'UTF-8') {
    return iconv("UTF-32LE", $encoding,
      call_user_func_array('pack', array_merge(array('V*'), $codepoints)));
  }
}

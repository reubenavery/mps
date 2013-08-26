<?php

/**
 * @file
 * Rich Rhee's b64 decoder.
 */

class MpsBase64 {

  /**
   * Decode, decompress, and unserialize a base64 string.
   *
   * @param string $string
   *   String to decode.
   *
   * @return mixed
   *   Base64 decoded object.
   */
  public static function decode($string) {
    return unserialize(@gzuncompress(stripslashes(base64_decode(strtr($string, '-_,', '+/=')))));
  }

  /**
   * Serialize, compress, and b64 encode an object.
   *
   * @param mixed $mixed
   *   Object to encode.
   *
   * @return string
   *   Base64 object encoded.
   */
  public static function encode($mixed) {
    return strtr(base64_encode(addslashes(@gzcompress(serialize($mixed)))), '+/=', '-_,');
  }
}

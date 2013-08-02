<?php
/*
 * @file
 * Rich Rhee's b64 decoder.
 */

class b64_Core {
  public static function decode($string) {
    /*//----- OLD Implementation
    return base64_decode(str_replace('_', '/', $string));
    */
    return unserialize(@gzuncompress(stripslashes(base64_decode(strtr($string, '-_,', '+/=')))));
  }
  public static function encode($string) {
    /*//----- OLD Implementation
    return base64_encode(str_replace('/', '_', $string));
    */
    return strtr(base64_encode(addslashes(@gzcompress(serialize($string)))), '+/=', '-_,');
  }
}
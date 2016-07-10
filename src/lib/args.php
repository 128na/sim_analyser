<?php
/**
 * コマンドライン引数をいい感じに取得する
 * @author  128Na
 * @version 2016.Jul.10
 * @since   PHP5.4
 * @license WTFPL (http://www.wtfpl.net/txt/copying/)
 */
class Args {

	/**
	 * オプションが指定されているかを返す
	 * @param  string $option 探すオプション名
	 * @return boolean オプションがあればtrue、なければfalse
	 */
	public static function has($option) {
        global $argv;
        foreach ($argv as $a) {
            if ($a === $option) return true;
        }
        return false;
	}

	/**
	 * オプションが指定されているかを返す
	 * @param  string $option 探すオプション名
	 * @return string|null オプションがあればその文字列、なければnullを返す
	 */
	public static function get($option) {
        global $argv;
        foreach ($argv as $i => $a) {
            if ($a === $option) return self::exists($argv[$i+1]);
        }
        return null;
	}

	/**
	 * PHP7から実装された??演算子の代わり
	 */
	private static function exists(&$item, $default = null) {
		return isset($item) ? $item : $default;
	}

}

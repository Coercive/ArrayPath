<?php
namespace Coercive\Utility\ArrayPath;

use ArrayAccess;
use ArrayObject;

/**
 * Class ArrayPath
 * PHP Version 	7
 *
 * @package     Coercive\Utility\ArrayPath
 * @link        https://github.com/Coercive/ArrayPath
 *
 * @original    Marcos Sader alias xmarcos
 * @see         https://github.com/xmarcos/DotContainer
 *
 * @author  	Anthony Moral <contact@coercive.fr>
 * @copyright   (c) 2017 - 2018 Anthony Moral
 * @license 	http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
class ArrayPath extends ArrayObject {

	const DEFAULT_SEPARATOR = '.';

	/** @var string */
	static private $_sSeparator;

	/**
	 * CUSTOM SEPARATOR
	 *
	 * @param string $sSeparator [optional]
	 * @return void
	 */
	public function setSeparator($sSeparator = self::DEFAULT_SEPARATOR) {
		self::$_sSeparator = $sSeparator;
	}

	/**
	 * GETTER SEPARATOR
	 *
	 * @return string
	 */
	public function getSeparator() {
		return self::$_sSeparator ?: self::DEFAULT_SEPARATOR;
	}

	/**
	 * INIT
	 *
	 * @param array|null $aData
	 * @return ArrayPath
	 */
	public static function init($aData = null) {
		return is_array($aData) || (is_object($aData) && $aData instanceof ArrayAccess) ? new static($aData) : new static;
	}

	/**
	 * GET PATH
	 *
	 * @param string $sPath
	 * @param null $mDefault [optional]
	 * @return array|mixed
	 */
	public function get($sPath = '', $mDefault = null) {

		# GET ALL
		if($sPath === null || $sPath === '') { return $this->getArrayCopy(); }

		# PARSE KEYS LIST
		$aKeys = $this->parse($sPath);

		return array_reduce($aKeys, function ($carry, $item) use ($mDefault) {
			return isset($carry[$item]) ? $carry[$item] : $mDefault;
		}, $this->getArrayCopy());
	}

	/**
	 * VERIFY PATH EXIST
	 *
	 * @param string $sPath
	 * @return bool
	 */
	public function has($sPath) {
		$sControl = md5(uniqid(__CLASS__, true));
		return $this->get($sPath, $sControl) !== $sControl;
	}

	/**
	 * SET
	 *
	 * @param string $sPath
	 * @param mixed $mValue
	 * @return $this
	 */
	public function set($sPath, $mValue = null) {
		$this->exchangeArray(
			array_replace_recursive($this->getArrayCopy(), $this->buildTree($sPath, $mValue))
		);
		return $this;
	}

	/**
	 * DELETE
	 *
	 * @param string $sPath
	 * @return ArrayPath
	 */
	public function delete($sPath) {
		if ($this->has($sPath)) {
			$this->exchangeArray(
				array_replace_recursive($this->getArrayCopy(), $this->buildTree($sPath, null))
			);
		}
		return $this;
	}

	/**
	 * RESET
	 *
	 * @return ArrayPath
	 */
	public function reset() {
		$this->exchangeArray([]);
		return $this;
	}

	/**
	 * TREE
	 *
	 * @param string $sPath
	 * @param mixed $mValue [optional]
	 * @return array
	 */
	private function buildTree($sPath, $mValue = null) {

		# PARSE KEYS LIST
		$aKeys = $this->parse($sPath);

		# INIT
		$aTree = [];
		$aCopy = & $aTree;

		# PREPARE
		while (count($aKeys)) {
			$sKey  = array_shift($aKeys);
			$aCopy = & $aCopy[$sKey];
		}
		$aCopy = $mValue;

		return $aTree;
	}

	/**
	 * PARSE
	 *
	 * @param string $sPath
	 * @return array
	 */
	private function parse($sPath) {

		# GET PATH LIST
		$aPaths = explode(self::getSeparator(), (string) $sPath);
		$aParts = array_filter($aPaths, 'strlen');

		return array_reduce($aParts, function ($carry, $item) {
			$carry[] = ctype_digit($item) ? intval($item) : $item;
			return $carry;
		}, []);
	}

}

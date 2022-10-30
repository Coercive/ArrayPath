<?php
namespace Coercive\Utility\ArrayPath;

use ArrayAccess;
use ArrayObject;

/**
 * Class ArrayPath
 *
 * @package     Coercive\Utility\ArrayPath
 * @link        https://github.com/Coercive/ArrayPath
 *
 * @original    Marcos Sader alias xmarcos
 * @see         https://github.com/xmarcos/DotContainer
 *
 * @author  	Anthony Moral <contact@coercive.fr>
 * @copyright   (c) 2022 Anthony Moral
 * @license 	MIT
 */
class ArrayPath extends ArrayObject
{
	const DEFAULT_SEPARATOR = '.';

	/** @var string */
	private string $separator = self::DEFAULT_SEPARATOR;

	/**
	 * Parse path 001.002.003 into array of keys [001,002,003]
	 *
	 * @param string $path
	 * @return array
	 */
	private function parse(string $path): array
	{
		$parts = explode($this->getSeparator(), $path);
		return array_filter($parts, 'strlen');
	}

	/**
	 * TREE
	 *
	 * @param string $path
	 * @param mixed $value [optional]
	 * @return array
	 */
	private function buildTree(string $path, $value = null): array
	{
		# Parse keys list
		$keys = $this->parse($path);

		# Create subarray for each key
		$tree = [];
		$copy = &$tree;
		while (count($keys)) {
			$key = array_shift($keys);
			$copy = &$copy[$key];
		}

		# Add the given value to the last created position
		$copy = $value;

		return $tree;
	}

	/**
	 * @param array $keys
	 * @param array $array
	 * @param bool|null $exist
	 * @return mixed|null
	 */
	private function reduce(array $keys, array $array, ?bool &$exist = null)
	{
		$exist = false;
		foreach($keys as $key) {
			array_shift($keys);
			if(array_key_exists($key, $array)) {
				$subarray = $array[$key];
				if($keys) {
					if(is_array($subarray) || $subarray instanceof ArrayAccess) {
						return $this->reduce($keys, $subarray, $exist);
					}
					return null;
				}
				else {
					$exist = true;
					return $subarray;
				}
			}
			else {
				break;
			}
		}
		return null;
	}

	/**
	 * @param array $keys
	 * @param array $array
	 * @return mixed|null
	 */
	private function remove(array $keys, array $array): array
	{
		foreach($keys as $key) {
			array_shift($keys);
			if(array_key_exists($key, $array)) {
				if($keys) {
					$subarray = $array[$key];
					if(is_array($subarray) || $subarray instanceof ArrayAccess) {
						$array[$key] = $this->remove($keys, $subarray);
					}
				}
				else {
					unset($array[$key]);
				}
				return $array;
			}
			else {
				break;
			}
		}
		return $array;
	}

	/**
	 * INIT
	 *
	 * @param array|ArrayAccess|null $data [optional]
	 * @param string $separator [optional]
	 * @return ArrayPath
	 */
	static public function init($data = null, string $separator = self::DEFAULT_SEPARATOR): ArrayPath
	{
		$instance = is_array($data) || $data instanceof ArrayAccess ? new static($data) : new static;
		$instance->setSeparator($separator);
		return $instance;
	}

	/**
	 * CUSTOM SEPARATOR
	 *
	 * @param string $separator [optional]
	 * @return ArrayPath
	 */
	public function setSeparator(string $separator = self::DEFAULT_SEPARATOR): ArrayPath
	{
		$this->separator = $separator;
		return $this;
	}

	/**
	 * GETTER SEPARATOR
	 *
	 * @return string
	 */
	public function getSeparator(): string
	{
		return $this->separator ?: self::DEFAULT_SEPARATOR;
	}

	/**
	 * GET PATH
	 *
	 * @param string $path
	 * @param null $default [optional]
	 * @param bool|null $exist [optional]
	 * @return mixed
	 */
	public function get(string $path = '', $default = null, ?bool &$exist = null)
	{
		# No data
		if(!$copy = $this->getArrayCopy()) {
			$exist = false;
			return $default;
		}

		# Root path
		if ($path === '') {
			$exist = true;
			return $copy;
		}

		# Parse keys list
		$keys = $this->parse($path);
		$value = $this->reduce($keys, $copy, $exist);
		return null === $value ? $default : $value;
	}

	/**
	 * VERIFY PATH EXIST
	 *
	 * @param string $path
	 * @return bool
	 */
	public function has(string $path): bool
	{
		$this->get($path, null, $exist);
		return !!$exist;
	}

	/**
	 * SET
	 *
	 * @param string $path
	 * @param mixed $value
	 * @return $this
	 */
	public function set(string $path, $value = null): ArrayPath
	{
		$this->exchangeArray(
			array_replace_recursive($this->getArrayCopy(), $this->buildTree($path, $value))
		);
		return $this;
	}

	/**
	 * DELETE
	 *
	 * @param string $path
	 * @return ArrayPath
	 */
	public function delete(string $path): ArrayPath
	{
		$this->exchangeArray(
			$this->remove($this->parse($path), $this->getArrayCopy())
		);
		return $this;
	}

	/**
	 * RESET
	 *
	 * @return ArrayPath
	 */
	public function reset(): ArrayPath
	{
		$this->exchangeArray([]);
		return $this;
	}
}
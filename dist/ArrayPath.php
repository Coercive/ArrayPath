<?php
namespace Coercive\Utility\ArrayPath;

use ArrayAccess;
use ArrayIterator;
use ArrayObject;
use Traversable;

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
	public const DEFAULT_SEPARATOR = '.';

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
	 * Array or ArrayAccess object
	 *
	 * @param mixed $data
	 * @return bool
	 */
	private function isContainer($data): bool
	{
		return is_array($data) || $data instanceof ArrayAccess;
	}

	/**
	 * Since PHP 8.0, array_key_exists() no longer accepts objects
	 *
	 * @param array|ArrayAccess $data
	 * @param string|int $key
	 * @return bool
	 */
	private function keyExists($data, $key): bool
	{
		return is_array($data) ? array_key_exists($key, $data) : $data->offsetExists($key);
	}

	/**
	 * @param array $keys
	 * @param array|ArrayAccess $data
	 * @param bool|null $exist
	 * @return mixed|null
	 */
	private function reduce(array $keys, $data, ?bool &$exist = null)
	{
		$exist = false;
		if(!$keys) {
			return null;
		}
		foreach($keys as $key) {
			if(!$this->isContainer($data) || !$this->keyExists($data, $key)) {
				return null;
			}
			$data = $data[$key];
		}
		$exist = true;
		return $data;
	}

	/**
	 * @param array $keys
	 * @param array|ArrayAccess $data
	 * @return array|ArrayAccess
	 */
	private function remove(array $keys, $data)
	{
		if(!$keys) {
			return $data;
		}
		$key = array_shift($keys);
		if(!$this->keyExists($data, $key)) {
			return $data;
		}
		if($keys) {
			if($this->isContainer($data[$key])) {
				$data[$key] = $this->remove($keys, $data[$key]);
			}
		}
		else {
			unset($data[$key]);
		}
		return $data;
	}

	/**
	 * Always store a plain array : an ArrayObject wrapping an object exposes its properties, not its offsets
	 *
	 * @param mixed $data
	 * @return array
	 */
	static private function toArray($data): array
	{
		if(is_array($data)) {
			return $data;
		}
		if($data instanceof ArrayObject || $data instanceof ArrayIterator) {
			return $data->getArrayCopy();
		}
		if($data instanceof Traversable) {
			return iterator_to_array($data);
		}
		if($data instanceof ArrayAccess) {
			return get_object_vars($data);
		}
		return [];
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
		$instance = new static(self::toArray($data));
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
	 * @param mixed $default [optional]
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
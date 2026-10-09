<?php
namespace Coercive\Utility\ArrayPath\Tests;

use ArrayObject;
use Coercive\Utility\ArrayPath\ArrayPath;
use PHPUnit\Framework\TestCase;

final class ArrayPathTest extends TestCase
{
	private function example(): array
	{
		return [
			'1' => [
				'2' => [
					'3' => ['content'],
				],
			],
			'a' => [
				'null' => null,
				'zero' => 0,
				'false' => false,
			],
		];
	}

	public function testGet(): void
	{
		$handler = ArrayPath::init($this->example());

		$this->assertSame(['content'], $handler->get('1.2.3'));
		$this->assertSame('content', $handler->get('1.2.3.0'));
		$this->assertSame(['3' => ['content']], $handler->get('1.2'));
		$this->assertSame($this->example(), $handler->get());
		$this->assertSame($this->example(), $handler->get(''));
	}

	public function testGetDefault(): void
	{
		$handler = ArrayPath::init($this->example());

		$this->assertNull($handler->get('1.2.3.4'));
		$this->assertSame('default', $handler->get('1.2.3.4', 'default'));
		$this->assertSame('default', $handler->get('1.2.3.0.x', 'default'));
		$this->assertSame('default', $handler->get('...', 'default'));
		$this->assertSame('default', ArrayPath::init()->get('1', 'default'));
	}

	public function testGetFalsyValues(): void
	{
		$handler = ArrayPath::init($this->example());

		$this->assertSame(0, $handler->get('a.zero', 'default'));
		$this->assertFalse($handler->get('a.false', 'default'));

		# A null value returns the default, but the path exists
		$this->assertSame('default', $handler->get('a.null', 'default', $exist));
		$this->assertTrue($exist);
	}

	public function testExistReference(): void
	{
		$handler = ArrayPath::init($this->example());

		$handler->get('1.2.3', null, $exist);
		$this->assertTrue($exist);

		$handler->get('1.2.3.4', null, $exist);
		$this->assertFalse($exist);

		ArrayPath::init()->get('', null, $exist);
		$this->assertFalse($exist);
	}

	public function testHas(): void
	{
		$handler = ArrayPath::init($this->example());

		$this->assertTrue($handler->has('1'));
		$this->assertTrue($handler->has('1.2.3'));
		$this->assertTrue($handler->has('a.null'));
		$this->assertFalse($handler->has('1.2.3.4'));
		$this->assertFalse($handler->has('x'));
		$this->assertFalse($handler->has('1.2.3.0.x'));
	}

	public function testSet(): void
	{
		$handler = ArrayPath::init($this->example());

		$handler->set('1.2.3', ['new-content']);
		$this->assertSame(['new-content'], $handler->get('1.2.3'));

		$handler->set('x.y.z', 'value');
		$this->assertSame(['y' => ['z' => 'value']], $handler->get('x'));

		# Siblings are kept
		$this->assertSame(0, $handler->get('a.zero'));

		$this->assertSame(['k' => 'v'], ArrayPath::init()->set('k', 'v')->getArrayCopy());
	}

	public function testDelete(): void
	{
		$handler = ArrayPath::init($this->example());

		$handler->delete('1.2.3');
		$this->assertFalse($handler->has('1.2.3'));
		$this->assertSame([], $handler->get('1.2'));

		# Unknown path : nothing changes
		$handler->delete('1.9.9');
		$handler->delete('a.zero.x');
		$this->assertSame(0, $handler->get('a.zero'));

		$handler->delete('a');
		$this->assertSame(['1'], array_map('strval', array_keys($handler->getArrayCopy())));
	}

	public function testReset(): void
	{
		$handler = ArrayPath::init($this->example())->reset();
		$this->assertSame([], $handler->getArrayCopy());
		$this->assertFalse($handler->has('1'));
	}

	public function testSeparator(): void
	{
		$handler = ArrayPath::init($this->example(), '@');
		$this->assertSame('@', $handler->getSeparator());
		$this->assertSame(['content'], $handler->get('1@2@3'));
		$this->assertNull($handler->get('1.2.3'));

		$handler->setSeparator('/');
		$this->assertSame(['content'], $handler->get('1/2/3'));

		# Empty separator falls back to default
		$handler->setSeparator('');
		$this->assertSame(ArrayPath::DEFAULT_SEPARATOR, $handler->getSeparator());
		$this->assertSame(['content'], $handler->get('1.2.3'));
	}

	public function testInitFromArrayObject(): void
	{
		$handler = ArrayPath::init(new ArrayObject($this->example()));
		$this->assertSame(['content'], $handler->get('1.2.3'));
		$this->assertSame($this->example(), $handler->getArrayCopy());
	}

	public function testNestedArrayAccess(): void
	{
		$handler = ArrayPath::init([
			'a' => new ArrayObject([
				'b' => new ArrayObject(['c' => 'value', 'd' => 'other']),
			]),
		]);

		$this->assertSame('value', $handler->get('a.b.c'));
		$this->assertTrue($handler->has('a.b.c'));
		$this->assertFalse($handler->has('a.b.x'));
		$this->assertFalse($handler->has('a.x.c'));
		$this->assertSame('default', $handler->get('a.b.x', 'default'));

		$handler->delete('a.b.c');
		$this->assertFalse($handler->has('a.b.c'));
		$this->assertSame('other', $handler->get('a.b.d'));
	}
}

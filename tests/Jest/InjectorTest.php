<?php

/*
 * This file is part of the Jest package.
 *
 * (c) Jeff Turcotte <jeff.turcotte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jest;

class InjectorTest extends \PHPUnit_Framework_TestCase
{
	public function testSetValidOffset()
	{
		$injector = new Injector();

		$injector['Closure'] = function() {
			return new Injector();
		};
		$injector['CallableObject'] = new DummyFactory();

		$this->assertInstanceOf('Jest\\Injector', $injector['Closure']);
		$this->assertInstanceOf('Jest\\Injector', $injector['CallableObject']);
	}

	/**
     * @expectedException \InvalidArgumentException
	 */
	public function testSetInvalidOffset()
	{
		$injector = new Injector();
		$injector['Injector'] = 'Not Allowed';
	}

	/**
     * @expectedException \InvalidArgumentException
	 */
	public function testGetInvalidOffset()
	{
		$injector = new Injector();
		$injector['Invalid'];
	}

	public function testIsset()
	{
		$injector = new Injector();
		$injector['Test'] = function() {};

		$this->assertEquals(TRUE, isset($injector['Test']));
	}

	public function testUnset()
	{
		$injector = new Injector();
		$injector['Test'] = function() {};
		unset($injector['Test']);

		$this->assertEquals(FALSE, isset($injector['Test']));
	}

	public function testInvoke()
	{
		$test = $this;

		$injector = new Injector();
		$injector['Jest\Injector'] = new DummyFactory();
		$injector['Closure'] = function () { return function() {}; };

		$injector->invoke(
			function(\Closure $func, Injector $injector) use ($test) {
				$test->assertInstanceOf('Closure', $func);
				$test->assertInstanceOf('Jest\\Injector', $injector);
			}
		);

		$dummyClass = new DummyClass(function(){});

		$test->assertInstanceOf('Closure', $injector->invoke(array($dummyClass, 'method')));
		$test->assertInstanceOf('Closure', $injector->invoke('globalFunction'));
		$test->assertInstanceOf('Closure', $injector->invoke('\Jest\DummyClass::staticMethod'));
		$test->assertInstanceOf('Closure', $injector->invoke(array('\Jest\DummyClass', 'staticMethod')));
	}

	/**
     * @expectedException \Exception
	 */
	public function testInvalidDependency()
	{
		$injector = new Injector();
		$injector['Jest\Injector'] = function(Injector $injector) {};

		$injector->invoke(function(Injector $injector) {});
	}
}

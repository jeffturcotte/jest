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

/**
 * Jest, a dependency injector.
 *
 * Jest uses type casting and reflection to determine
 * which dependencies need to be injected into the
 * specified callable;
 *
 * Usage:
 *
 * $injector = new Jest\Injector();
 *
 * $injector['Request'] = function() {
 *     return new Request();
 * };
 *
 * $injector['Session'] = function(Request $req) {
 *     return new Session($req);
 * };
 *
 * $value = $injector->invoke(function(Request $req, Session $sess) {
 *     return array($req, $sess);
 * })
 *
 * @package Jest
 * @author  Jeff Turcotte <jeff.turcotte@gmail.com>
 * @version 1.0.0
 */
class Injector implements \ArrayAccess
{
	protected $factories = [];
	protected $resolving = [];
	protected $instances = [];

	/**
	 * Invoke a callable and injects dependencies
	 *
	 * @param $callable mixed
	 *     The Closure or object to inject dependencies into
	 *
	 * @return mixed
	 *     The value return from the callable
	 */
	public function invoke(Callable $callable)
	{
		$reflection = $this->reflectCallable($callable);

		$args = [];

		foreach($reflection->getParameters() as $param) {
			$type = $param->getClass()->getName();

			if (in_array($type, $this->resolving)) {
				throw new \LogicException("Recursive dependency: $type is currently instatiating.");
			}

			$args[] = ($param->allowsNull() && !isset($this[$type])) ? null : $this[$type];
		}

		return call_user_func_array($callable, $args);
	}


	/**
	 * Confirms if a class has been set
	 *
	 * @param $class string
	 *     The type to check
	 * 
	 * @return boolean
	 */
	public function offsetExists($class)
	{
		return isset($this->factories[$class]);
	}


	/**
	 * Unsets a registered class
	 *
	 * @param $class string
	 *     The class to unset
	 */
	public function offsetUnset($class)
	{
		unset($this->factories[$class]);
	}


	/**
	 * get a dependency for the supplied class
	 *
	 * @param $type string
	 *     The type to get
	 *
	 * @return mixed
	 *     The dependency/type value
	 */
	public function offsetGet($class)
	{
		if (!isset($this->factories[$class]) && !isset($this->instances[$class])) {
			throw new \InvalidArgumentException("$class has not been defined");
		}

		if (isset($this->instances[$class])) {
			return $this->instances[$class];
		}

		array_push($this->resolving, $class);
		$object = $this->invoke($this->factories[$class]);
		array_pop($this->resolving);

		return $object;
	}


	/**
	 * Registers a dependency for injection
	 *
	 * @throws \InvalidArgumentException
	 *
	 * @param $class string
	 *     The class to register
	 *
	 * @param $factory mixed A callable or object
	 *     The factory used to create the dependency or the instance of the dependency
	 */
	public function offsetSet($class, $factory)
	{
		if (is_callable($factory)) {
			$this->factories[$class] = $factory;
		} else if (is_object($factory)) {
			$this->instances[$class] = $factory;
		} else {
			throw new \InvalidArgumentException("Dependency supplied is neither a callable or an object");
		}
	}


	/**
	 * Reflect a callable
	 *
	 * @param $callable Callable
	 *     The callable to reflect
	 *
	 * @return ReflectionFunction|ReflectionMethod
	 */
	protected function reflectCallable($callable)
	{
		if (is_string($callable) && strpos($callable, '::')) {
			$callable = explode('::', $callable, 2);
		}

		if (is_a($callable, 'Closure')) {
			$reflection = new \ReflectionFunction($callable);
		} else if (is_object($callable)) {
			$reflection = new \ReflectionMethod(get_class($callable), '__invoke');
		} else if (is_array($callable) && count($callable) == 2) {
			$reflection = new \ReflectionMethod((is_object($callable[0]) ? get_class($callable[0]) : $callable[0]), $callable[1]);
		} else if (is_string($callable) && function_exists($callable)) {
			$reflection = new \ReflectionFunction($callable);
		}

		return $reflection;
	}
}

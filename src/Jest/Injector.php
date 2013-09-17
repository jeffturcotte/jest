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
 * $injector['Request'] = $injector->share(function() {
 *     return new Request();
 * });
 *
 * $injector['Session'] = $injector->share(function(Request $req) {
 *     return new Session($req);
 * });
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
	protected $factories = array();
	protected $resolving = array();


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
		$arguments  = array();

		if (is_string($callable) && strpos($callable, '::')) {
			$callable = explode('::', $callable, 2);
		}

		$reflection = $this->reflectCallable($callable);
		
		// collect the arguments for calling
		foreach($reflection->getParameters() as $param) {
			$type = $param->getClass()->getName();

			if (in_array($type, $this->resolving)) {
				throw new \LogicException("$type is currently being resolved and cannot be used.");
			}

		    $arguments[] = ($param->allowsNull() && !isset($this[$type])) ? null : $this[$type];
		}

		return call_user_func_array($callable, $arguments);
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
		if (!isset($this->factories[$class])) {
			throw new \InvalidArgumentException("$class has not been defined");
		}

		array_push($this->resolving, $class);
		$object = $this->invoke($this->factories[$class]);
		array_pop($this->resolving);

		return $object;
	}


	/**
	 * Registers a dependency that is recreated for
	 * every injection
	 *
	 * @param $class string
	 *     The class to register
	 *
	 * @param $factory Callable
	 *     The factory used to create the dependency
	 */
	public function offsetSet($class, $factory)
	{
		if (!is_callable($factory)) {
			$factory = function() use ($factory) {
				return $factory;
			};
		}

		$this->factories[$class] = $factory;
	}


	/**
	 * A helper factory wrapper for sharing a dependency
	 *
	 * @param $class Callable
	 *     The factory to be registered
	 *
	 * @return Closure
	 *     The factory used to create the dependency
	 */
	public function share(Callable $factory)
	{
		$self = $this;

		return function() use ($self, $factory) {
			static $instance;

			if (is_null($instance)) {
				$instance = $self->invoke($factory);
			}

			return $instance;
		};
	}


	/**
	 * Reflect a callable
	 *
	 * @param $callable Callable
	 *     The callable to reflect
	 *
	 * @return ReflectionFunction|ReflectionMethod
	 */
	protected function reflectCallable(Callable $callable)
	{
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

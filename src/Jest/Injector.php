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
 */
class Injector implements \ArrayAccess
{
	protected $factories = array();
	protected $resolving = array();


	/**
	 * Constructor
	 *
	 * @param Injector A jest injector from which to copy factories
	 */
	public function __construct(Injector $injector = NULL)
	{
		if ($injector) {
			$this->factories = $jest->factories;	
		}
	}


	/**
	 * Instantiates an class and injects dependencies
	 *
	 * @param $class The class to instantiate
	 *
	 * @return mixed The instance
	 */
	public function create($class) {
		if (!class_exists($class)) {
			throw new \InvalidArgumentException("Class {$class} does not exist.");
		}

		$reflection = new \ReflectionClass($class);

		if (!$reflection->isInstantiable()) {
			throw new \InvalidArgumentException("{$class} is not instantiable.");
		}

		$arguments = array();

		if ($reflection->hasMethod('__construct')) {
			$constructor = $reflection->getMethod('__construct');
			$arguments   = $this->gatherDependencyArguments($constructor->getParameters());
		}

		return $reflection->newInstanceArgs($arguments);
	}


	/**
	 * Gets all dependences to be injected.
	 * 
	 * @param $parameters array An array of ReflectionParameter objects
	 *
	 * @return array The 
	 */
	protected function gatherDependencyArguments(array $parameters)
	{
		$arguments = array();

		foreach($parameters as $param) {
			$type = $param->getClass()->getName();

			if (in_array($type, $this->resolving)) {
				throw new \LogicException(
					"$type is currently being resolved and cannot be used."
				);
			}

			// If the argument allows NULL and hasn't been configured then pass NULL
			if ($param->allowsNull() && !isset($this[$type])) {
				$arguments[] = NULL;
				continue;	
			}

			$arguments[] = $this[$type];
		}

		return $arguments;
	}


	/**
	 * Invoke a callable and injects dependencies
	 *
	 * @param $callable mixed The Closure or object to inject dependencies into
	 *
	 * @return mixed The value return from the callable
	 */
	public function invoke($callable)
	{
		if (is_string($callable) && strpos($callable, '::')) {
			$callable = explode('::', $callable, 2);
		}

		try {
			if (is_a($callable, 'Closure')) {
				$reflection = new \ReflectionFunction(
					$callable
				);
			} else if (is_object($callable)) {
				$reflection = new \ReflectionMethod(
					get_class($callable),
					'__invoke'
				);
			} else if (is_array($callable) && count($callable) == 2) {
				$reflection = new \ReflectionMethod(
					(is_object($callable[0]) ? get_class($callable[0]) : $callable[0]),
					$callable[1]
				);
			} else if (is_string($callable)) {
				$reflection = new \ReflectionFunction(
					$callable
				);
			} else {
				throw new \ReflectionException();
			}
		} catch (\ReflectionException $e) {
			var_dump($e->getMessage());
			throw new \InvalidArgumentException(
				'Supplied argument does not appear to be callable.'
			);
		}
		
		$arguments = $this->gatherDependencyArguments(
			$reflection->getParameters()
		);

		return call_user_func_array($callable, $arguments);
	}


	/**
	 * Confirms if a type has been set
	 *
	 * @param $type string The type to check
	 * 
	 * @return Boolean
	 */
	public function offsetExists($type)
	{
		return isset($this->factories[$type]);
	}


	/**
	 * Registers a dependency
	 *
	 * @param $type string      The dependency type to register
	 * @param $factory Callable The factory used to create the dependency
	 */
	public function offsetSet($type, $factory)
	{
		if (!(is_object($factory) && is_callable($factory))) {
			throw new \InvalidArgumentException('Factory must be a callable object.');
		}

		$this->factories[$type] = $factory;
	}


	/**
	 * Creates an object for a type
	 *
	 * @param $type string The type to get
	 *
	 * @return mixed The dependency/type value
	 */
	public function offsetGet($type)
	{
		if (!isset($this->factories[$type])) {
			throw new \InvalidArgumentException("Type $type has not been defined");
		}

		array_push($this->resolving, $type);
		$object = $this->invoke($this->factories[$type]);
		array_pop($this->resolving);

		return $object;
	}


	/**
	 * Unsets a type
	 *
	 * @param $type string The type to unset
	 */
	public function offsetUnset($type)
	{
		unset($this->factories[$type]);
	}


	/**
	 * Shares a dependency type across all injections
	 *
	 * @param $factory Callable The factory to instantiate the class
	 *
	 * @return mixed An instance of the type
	 */
	public function share($factory)
	{
		$self = $this;

		return function() use ($factory, $self) {
			static $instance;

			return $instance = (is_null($instance))
				? $self->invoke($factory)
				: $instance;
		};
	}
}

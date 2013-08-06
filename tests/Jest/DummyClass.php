<?php

/*
 * This file is part of the Jest package.
 *
 * (c)  Jeff Turcotte <jeff.turcotte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jest;

class DummyClass
{
	public function __construct(\Closure $closure) {
		$this->closure = $closure;
	}

	public function method(\Closure $closure) {
		return $closure;
	}

	static public function staticMethod(\Closure $closure) {
		return $closure;
	}
}


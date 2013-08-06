<?php
require __DIR__ . '/../src/Jest/Injector.php';
require __DIR__ . '/Jest/DummyFactory.php';
require __DIR__ . '/Jest/DummyClass.php';

function globalFunction(\Closure $closure) {
	return $closure;
}

# Jest

A simple dependency injection library

[![Build Status](https://travis-ci.org/jeffturcotte/jest.png)](https://travis-ci.org/jeffturcotte/jest)

Jest looks to find the middle ground between the simplicity offered by tiny
service locators like Pimple and the real dependency injection libraries like PHP-DI
provide.

Jest uses an API very similar to Pimple and uses type casting and reflection 
to determine which dependencies need to be injected into the specified class
or PHP callable (Closure, function, or method).

##  Usage:

```(php)
$injector = new Jest\Injector();

// configure your shared dependencies

$injector['Request'] = function() {
	return new Request();
};

$injector['Session'] = function(Request $req) {
	return new Session($req);
};

// invoke a callable with the dependencies

$returnValue = $injector->invoke(function(Request $req, Session $sess) {
	return array($req, $sess);
});


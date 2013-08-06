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

$injector['Request'] = $injector->share(function() {
	return new Request();
});

$injector['Session'] = $injector->share(function(Request $req) {
	return new Session($req);
});

// configure a non-shared dependency

$injector['Response'] = function(Data $data) {
	return new Response($data);
}

// invoke a callable with the dependencies

$returnValue = $injector->invoke(function(Request $req, Session $sess) {
	return array($req, $sess);
});

// instantiate a class with the dependencies

class User {
	public function __construct(Session $sess) {
		
	}
}

$object = $injector->create('User');
```


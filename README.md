# injected

Automatic mocked dependency injection for testing

### What is this?

`InjectedTrait` allows you to easily create classes with all of their dependencies mocked out for testing purposes.

### Why?

The following pattern is very common:

1. Create a class `A` with its dependencies (service objects) passed in through the constructor
2. Use these services in various functions internal to the object
3. Create tests that mock out each of `A`'s dependencies, asserting that they are called as expected.

A lot of the testing logic ends up being boilerplate for constructing an object. `InjectedTrait` aims to remove this boilerplate entirely, letting you focus on what matters: the tests themselves.

### Getting Started

Use `composer` to get the latest release (you'll probably only need it for development):

```
$ composer require sellerlabs/injected --dev
```

### Example Usage

Suppose we're developing a web app, and we want to email users when they sign up. For a simple example, let's assume a user is defined entirely by their email address. When they sign up, naturally we want to send them a thank you email. Furthermore, we'd like to test that the emails are really being sent _without actually sending them_.

First, let's define an email service:

```php
class EmailService
{
    public function email($address, $content)
    {
        // Send an email to $address with body $content
    }
}
```

(In a real application, `email` would send out an email -- we're not concerned with the implementation here, though!)

Let's also define a `UserController` which handles the extremely simple sign up process:

```php
class UserController
{
    private $service;

    public function __construct(EmailService $service)
    {
        $this->service = $service;
    }

    public function signUp($emailAddress)
    {
        $this->service->email($emailAddress, 'Thanks for signing up!');

        return $emailAddress;
    }
}
```

Here, we provide the `EmailService` dependency through the constructor, and use it during our (incredibly simple) signup process.

To test this class, we'll have to do one of two things:

1. Actually send an email out, and make sure it was sent somehow, or
2. Mock the `EmailService` object using something like `Mockery` and make sure that `email` is called with the expected arguments.

`InjectedTrait` allows you to painlessly achieve option 2. Let's take a look:

```php
use SellerLabs\Injected\InjectedTrait;

/**
 * Class InjectedExample
 *
 * // 1. These are helpful annotations for IDEs and language tools
 * @property MockInterface $service
 * @method UserController make()
 *
 * @author Benjamin Kovach <benjamin@roundsphere.com>
 */
class InjectedExample extends PHPUnit_Framework_TestCase
{
    // 2. Use our trait
    use InjectedTrait;

    // 3. Provide the name of the class to test
    protected $className = UserController::class;

    public function testSignUp()
    {
        // 4. Make a controller with mocked dependencies
        $controller = $this->make();
        $address = 'email@test.me';

        // 5. We can access any mocked dependency of the class as a property
        $this->service->shouldReceive('email')
            ->withArgs(
                [
                    $address,
                    'Thanks for signing up!'
                ]
            );

        $result = $controller->signUp($address);

        $this->assertEquals($address, $result);
    }
}
```

Every class using `InjectedTrait` is required to have the `$className` property, which is used to locate the class that is being tested. `InjectedTrait` provides a single public method, `make`, which constructs an object of this type, but mocks its dependencies out and saves them as properties to the test class itself.

So, in `testSignUp`, we're constructing the controller using `make()`, which gives us access to a mocked `EmailService` type object called `$service`. This is because it's defined that way in the `UserController`'s constructor:

```php
public function __construct(EmailService $service)
{
    $this->service = $service;
}
```

For the duration of the test case, the `$service` member variable is bound to this mocked `EmailService`, which allows us to make expectations about what happens with it when the `signUp` method of the controller gets called. We use `Mockery` in order to create the mocked objects. There are some annotations in the class comment, which help with IDE autocompletion for these classes since the mock properties are declared dynamically.

This example lives in `tests/InjectedExample.php`. Feel free to poke around!

The impact of this trait may seem relatively small, but when working on large applications where classes have several dependencies, this makes testing _much_ easier.

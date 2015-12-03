<?php

namespace Tests\SellerLabs\Injected;

use Mockery\MockInterface;
use PHPUnit_Framework_TestCase;
use SellerLabs\Injected\InjectedTrait;

class EmailService
{
    public function email($address, $content)
    {
        // Do some stuff
    }
}

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

        // Dummy response
        return $emailAddress;
    }
}

/**
 * Class InjectedExample
 *
 * @property MockInterface $service
 *
 * @method UserController make()
 *
 * @author Benjamin Kovach <benjamin@roundsphere.com>
 */
class InjectedExample extends PHPUnit_Framework_TestCase
{
    use InjectedTrait;

    protected $className = UserController::class;

    public function testSignUp()
    {
        $controller = $this->make();
        $address = 'email@test.me';

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

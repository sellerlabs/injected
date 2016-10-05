<?php

namespace SellerLabs\Injected;

use Exception;
use ReflectionClass;
use Mockery;

/**
 * Class InjectedTrait
 *
 * Allows automatic dependency injection of a class that needs to be tested,
 * with easy access to its mocked fields.
 *
 * @property string $className
 *
 * @package Tests\SellerLabs\Snagshout\Support\Traits
 * @author Benjamin Kovach <benjamin@roundsphere.com>
 */
trait InjectedTrait
{
    /**
     * Get a mapping of class name => member name dependencies.
     *
     * Important: these must be ordered in the way the class accepts its
     * dependencies.
     *
     * @return array
     *
     * @throws Exception
     */
    protected function getDependencies()
    {
        $constructor = (new ReflectionClass($this->className))
            ->getConstructor();

        $dependencies = [];

        if (!is_null($constructor)) {
            foreach ($constructor->getParameters() as $param) {
                $dependencies[$param->getClass()->getName()] = $param->getName();
            }
        }

        return $dependencies;
    }

    /**
     * Make an instance of $this->className
     *
     * @param array $parameters
     *
     * @return mixed
     *
     * @throws Exception
     */
    protected function make(array $parameters = [])
    {
        $dependencies = $this->mockDependencies();
        $dependencies = array_merge($dependencies, $parameters);

        // Note: Must be defined in trait-using class
        $className = $this->className;

        return new $className(
            ...array_values($dependencies)
        );
    }

    /**
     * Mock all dependencies that were not set yet
     *
     * @return array
     *
     * @throws Exception
     */
    protected function mockDependencies()
    {
        $dependencies = $this->getDependencies();

        foreach ($dependencies as $interface => $memberName) {
            if (!isset($this->$memberName)) {
                $this->$memberName = Mockery::mock($interface);
            }

            // Update with the actual instance.
            $dependencies[$interface] = $this->$memberName;
        }

        return $dependencies;
    }
}

<?php

declare(strict_types=1);

namespace WhirlwindApplicationTesting;

use DG\BypassFinals;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Whirlwind\App\Application\Application;
use WhirlwindApplicationTesting\Traits\InteractWithContainer;
use WhirlwindApplicationTesting\Traits\InteractWithFixtures;
use WhirlwindApplicationTesting\Traits\MakesHttpRequests;
use WhirlwindApplicationTesting\Util\ContainerAwareApplication;

abstract class RestTestCase extends TestCase
{
    use InteractWithContainer;
    use MakesHttpRequests;

    /**
     * @var Application
     */
    protected Application $app;
    /**
     * @var ContainerInterface
     */
    protected ContainerInterface $container;
    /**
     * @var array
     */
    protected array $serverParams = [];

    /**
     * @return void
     */
    protected function setUp(): void
    {
        BypassFinals::enable();
        parent::setUp();
        $this->serverParams = $_SERVER;
        $this->app = ContainerAwareApplication::createFromInstance($this->createApplication());
        $this->container = $this->app->getContainer();
        $this->setUpTraits();
    }

    /**
     * @return Application
     */
    abstract protected function createApplication(): Application;

    /**
     * @return void
     */
    protected function setUpTraits(): void
    {
        $results = [];

        $classes =  \array_reverse(\class_parents(static::class)) + [static::class => static::class];
        foreach ($classes as $class) {
            $results += $this->traitUsesRecursive($class);
        }

        $uses = \array_flip(\array_unique($results));

        if (isset($uses[InteractWithFixtures::class])) {
            $this->initFixtures();
        }
    }

    private function traitUsesRecursive(string $trait): array
    {
        $traits = class_uses($trait) ?: [];

        foreach ($traits as $trait) {
            $traits += $this->traitUsesRecursive($trait);
        }

        return $traits;
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->app, $this->container);
        $_SERVER = $this->serverParams;

        if ($container = \Mockery::getContainer()) {
            $this->addToAssertionCount($container->mockery_getExpectationCount());
        }

        \Mockery::close();
    }
}

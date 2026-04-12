<?php
define('PHPUNIT_TEST', true);
require_once __DIR__ . '/../testCounter.php';

use PHPUnit\Framework\TestCase;

class CounterTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset singleton instance before each test
        $reflection = new ReflectionClass(Counter::class);
        $instance = $reflection->getProperty('instance');
        $instance->setValue(null, null);
    }

    // --- ArrayCounter Tests ---

    public function testArrayCounterGetReturnsZeroForNewKey()
    {
        $counter = new ArrayCounter();
        $this->assertEquals(0, $counter->get('new_key'));
    }

    public function testArrayCounterSetAndGet()
    {
        $counter = new ArrayCounter();
        $counter->set('visits', 10);
        $this->assertEquals(10, $counter->get('visits'));
    }

    public function testArrayCounterIncrement()
    {
        $counter = new ArrayCounter();
        $this->assertEquals(1, $counter->increment('hits', 1));
        $this->assertEquals(2, $counter->increment('hits', 1));
    }

    public function testArrayCounterDecrement()
    {
        $counter = new ArrayCounter();
        $counter->set('score', 10);
        $this->assertEquals(9, $counter->decrement('score', 1));
        $this->assertEquals(7, $counter->decrement('score', 2));
    }

    public function testArrayCounterReset()
    {
        $counter = new ArrayCounter();
        $counter->set('temp', 50);
        $counter->reset('temp');
        $this->assertEquals(0, $counter->get('temp'));
    }

    // --- Counter (Singleton + DI) Tests ---

    public function testGetInstanceReturnsCounterInstance()
    {
        $arrayCounter = new ArrayCounter();
        $counter = Counter::getInstance($arrayCounter);
        $this->assertInstanceOf(Counter::class, $counter);
    }

    public function testSingletonAlwaysReturnsSameInstance()
    {
        $arrayCounter = new ArrayCounter();
        $c1 = Counter::getInstance($arrayCounter);
        $c2 = Counter::getInstance($arrayCounter);
        $this->assertSame($c1, $c2);
    }

    public function testSingletonSharesState()
    {
        $arrayCounter = new ArrayCounter();
        $c1 = Counter::getInstance($arrayCounter);
        $c2 = Counter::getInstance($arrayCounter);
        
        $c1->increment();
        $this->assertEquals(1, $c2->get());
    }

    public function testCounterIncrement()
    {
        $arrayCounter = new ArrayCounter();
        $counter = Counter::getInstance($arrayCounter);
        
        $this->assertEquals(1, $counter->increment('test_inc'));
        $this->assertEquals(3, $counter->increment('test_inc', 2));
    }

    public function testCounterDecrement()
    {
        $arrayCounter = new ArrayCounter();
        $counter = Counter::getInstance($arrayCounter);
        $counter->increment('score', 10);
        
        $this->assertEquals(9, $counter->decrement('score'));
        $this->assertEquals(4, $counter->decrement('score', 5));
    }

    public function testCounterGet()
    {
        $arrayCounter = new ArrayCounter();
        $counter = Counter::getInstance($arrayCounter);
        $counter->increment('views');
        
        $this->assertEquals(1, $counter->get('views'));
    }

    public function testCounterReset()
    {
        $arrayCounter = new ArrayCounter();
        $counter = Counter::getInstance($arrayCounter);
        $counter->increment('temp', 10);
        $counter->reset('temp');
        
        $this->assertEquals(0, $counter->get('temp'));
    }

    public function testConstructorIsPrivate()
    {
        $reflection = new ReflectionClass(Counter::class);
        $constructor = $reflection->getConstructor();
        $this->assertTrue($constructor->isPrivate());
    }

    public function testCloneIsPrivate()
    {
        $reflection = new ReflectionClass(Counter::class);
        $clone = $reflection->getMethod('__clone');
        $this->assertTrue($clone->isPrivate());
    }

    public function testArrayCounterImplementsInterface()
    {
        $counter = new ArrayCounter();
        $this->assertInstanceOf(CounterInterface::class, $counter);
    }

    public function testDifferentKeysHaveIndependentValues()
    {
        $arrayCounter = new ArrayCounter();
        $counter = Counter::getInstance($arrayCounter);
        
        $counter->increment('page_views');
        $counter->increment('downloads', 5);
        
        $this->assertEquals(1, $counter->get('page_views'));
        $this->assertEquals(5, $counter->get('downloads'));
    }
}

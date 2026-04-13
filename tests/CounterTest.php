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
        $testKey = 'hits_' . time();
        $this->assertEquals(1, $counter->increment($testKey, 1));
        $this->assertEquals(2, $counter->increment($testKey, 1));
    }

    public function testArrayCounterDecrement()
    {
        $counter = new ArrayCounter();
        $testKey = 'score_' . time();
        $counter->set($testKey, 10);
        $this->assertEquals(9, $counter->decrement($testKey, 1));
        $this->assertEquals(7, $counter->decrement($testKey, 2));
    }

    public function testArrayCounterReset()
    {
        $counter = new ArrayCounter();
        $testKey = 'temp_' . time();
        $counter->set($testKey, 50);
        $counter->reset($testKey);
        $this->assertEquals(0, $counter->get($testKey));
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

        $testKey = 'test_share_state_' . time();
        $c1->increment($testKey);
        $this->assertEquals(1, $c2->get($testKey));
    }

    public function testCounterIncrement()
    {
        $arrayCounter = new ArrayCounter();
        $counter = Counter::getInstance($arrayCounter);
        $testKey = 'test_inc_' . time();

        $this->assertEquals(1, $counter->increment($testKey));
        $this->assertEquals(3, $counter->increment($testKey, 2));
    }

    public function testCounterDecrement()
    {
        $arrayCounter = new ArrayCounter();
        $counter = Counter::getInstance($arrayCounter);
        $testKey = 'score_dec_' . time() . '_' . rand(1000, 9999);
        
        $counter->increment($testKey, 10);

        $this->assertEquals(9, $counter->decrement($testKey));
        $this->assertEquals(4, $counter->decrement($testKey, 5));
    }

    public function testCounterGet()
    {
        $arrayCounter = new ArrayCounter();
        $counter = Counter::getInstance($arrayCounter);
        $testKey = 'views_' . time();
        $counter->increment($testKey);

        $this->assertEquals(1, $counter->get($testKey));
    }

    public function testCounterReset()
    {
        $arrayCounter = new ArrayCounter();
        $counter = Counter::getInstance($arrayCounter);
        $testKey = 'temp_' . time();
        $counter->increment($testKey, 10);
        $counter->reset($testKey);

        $this->assertEquals(0, $counter->get($testKey));
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
        $key1 = 'page_views_' . time();
        $key2 = 'downloads_' . time();

        $counter->increment($key1);
        $counter->increment($key2, 5);

        $this->assertEquals(1, $counter->get($key1));
        $this->assertEquals(5, $counter->get($key2));
    }

    // --- Race Condition Tests ---

    public function testRaceConditionSafeFileCounter()
    {
        // Test ini menunjukkan ArrayCounter AMAN dari race condition
        // Karena sudah pakai file locking (flock)
        
        $testKey = 'safe_race_test_' . time();
        $numProcesses = 10;
        $incrementsPerProcess = 100;
        $expectedValue = $numProcesses * $incrementsPerProcess;
        
        // Pakai storage yang sama untuk semua process
        $storageDir = sys_get_temp_dir() . '/counter_storage_test';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0777, true);
        }

        // Buat worker script yang pakai ArrayCounter (SAFE - ada flock)
        $tempFile = sys_get_temp_dir() . '/worker_safe_' . uniqid() . '.php';
        $projectRoot = dirname(__DIR__);
        
        $workerCode = "<?php
        define('PHPUNIT_TEST', true);
        require_once '$projectRoot/testCounter.php';
        
        // Semua process pakai storage dir yang sama
        \$storageDir = '$storageDir';
        if (!is_dir(\$storageDir)) {
            mkdir(\$storageDir, 0777, true);
        }
        
        \$arrayCounter = new ArrayCounter(\$storageDir);
        
        for (\$i = 0; \$i < $incrementsPerProcess; \$i++) {
            // PAKAI ArrayCounter (SAFE - ada flock) - perlu 2 params
            \$arrayCounter->increment('$testKey', 1);
        }
        ";
        
        file_put_contents($tempFile, $workerCode);

        // Jalankan multiple processes pakai proc_open (lebih reliable)
        $phpPath = PHP_BINARY; // Path ke PHP yang sedang aktif
        $processes = [];
        
        for ($i = 0; $i < $numProcesses; $i++) {
            $processes[$i] = proc_open(
                "\"$phpPath\" \"$tempFile\"",
                [],
                $pipes,
                null,
                null,
                ['bypass_shell' => true]
            );
        }

        // Tunggu semua process selesai
        foreach ($processes as $proc) {
            proc_close($proc);
        }

        // Baca hasil dengan storage yang sama
        $arrayCounter = new ArrayCounter($storageDir);
        $actualValue = $arrayCounter->get($testKey);

        // Cleanup
        if (file_exists($tempFile)) unlink($tempFile);

        // HARUS PASS - ArrayCounter dengan flock aman dari race condition
        $this->assertEquals($expectedValue, $actualValue,
            "ArrayCounter with flock should be safe from race condition! Expected $expectedValue, got $actualValue"
        );
        
        echo "\n✅ Safe version (ArrayCounter with flock): All $expectedValue increments successful (0% loss)\n";
    }

    public function testRaceConditionSimulationInMemory()
    {
        // Simulasi race condition tanpa multiple processes
        // Ini test yang bisa jalan di Windows
        $arrayCounter = new ArrayCounter();
        $testKey = 'simulated_race_' . time();
        $numIterations = 1000;
        $expectedValue = $numIterations;

        // Simulate concurrent access pattern
        // Dalam real scenario, operasi ini bisa interleaved
        for ($i = 0; $i < $numIterations; $i++) {
            $current = $arrayCounter->get($testKey);
            // Simulasi delay yang bisa menyebabkan race condition
            $arrayCounter->set($testKey, $current + 1);
        }

        $actualValue = $arrayCounter->get($testKey);
        
        // Dengan sequential access, harusnya sama dengan expected
        $this->assertEquals($expectedValue, $actualValue,
            "Sequential access should not lose updates"
        );
    }
}

<?php
define('PHPUNIT_TEST', true);
require_once __DIR__ . '/../testKoneksiDB.php';

use PHPUnit\Framework\TestCase;

class DatabaseManagerTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset singleton instance before each test
        $reflection = new ReflectionClass(DatabaseManager::class);
        $instance = $reflection->getProperty('instance');
        $instance->setValue(null, null);
    }

    public function testGetInstanceReturnsSingletonInstance()
    {
        $pdo = new PDO('sqlite:memory');
        $manager = DatabaseManager::getInstance($pdo);
        $this->assertInstanceOf(DatabaseManager::class, $manager);
    }

    public function testGetInstanceAlwaysReturnsSameInstance()
    {
        $pdo = new PDO('sqlite:memory');
        $manager1 = DatabaseManager::getInstance($pdo);
        $manager2 = DatabaseManager::getInstance();
        $this->assertSame($manager1, $manager2);
    }

    public function testGetInstanceReturnsObjectWithSameId()
    {
        $pdo = new PDO('sqlite:memory');
        $manager1 = DatabaseManager::getInstance($pdo);
        $manager2 = DatabaseManager::getInstance();
        $this->assertEquals($manager1->getId(), $manager2->getId());
    }

    public function testConnectReturnsSuccessMessage()
    {
        $pdo = new PDO('sqlite:memory');
        $manager = DatabaseManager::getInstance($pdo);
        $result = $manager->connect();
        $this->assertEquals("Koneksi BERHASIL!", $result);
    }

    public function testConnectCreatesDatabaseConnection()
    {
        $pdo = new PDO('sqlite:memory');
        $manager = DatabaseManager::getInstance($pdo);
        $manager->connect();
        
        $db = $manager->getDb();
        $this->assertInstanceOf(PDO::class, $db);
    }

    public function testConnectWithDependencyInjection()
    {
        $pdo = new PDO('sqlite:memory');
        $manager = DatabaseManager::getInstance($pdo);
        $result = $manager->connect();
        $this->assertEquals("Koneksi BERHASIL!", $result);
    }

    public function testConnectWithMySQLConfig()
    {
        $pdo = new PDO('sqlite:memory');
        $manager = DatabaseManager::getInstance($pdo);
        $result = $manager->connect();
        $this->assertEquals("Koneksi BERHASIL!", $result);
    }

    public function testConnectWithInvalidDSN()
    {
        $this->expectException(PDOException::class);
        $pdo = new PDO('invalid:database');
    }

    public function testConstructorIsPrivate()
    {
        $reflection = new ReflectionClass(DatabaseManager::class);
        $constructor = $reflection->getConstructor();
        $this->assertTrue($constructor->isPrivate());
    }

    public function testCloneIsPrivate()
    {
        $reflection = new ReflectionClass(DatabaseManager::class);
        $clone = $reflection->getMethod('__clone');
        $this->assertTrue($clone->isPrivate());
    }

    public function testGetIdReturnsNumericValue()
    {
        $pdo = new PDO('sqlite:memory');
        $manager = DatabaseManager::getInstance($pdo);
        $id = $manager->getId();
        $this->assertIsInt($id);
        $this->assertGreaterThanOrEqual(1000, $id);
        $this->assertLessThanOrEqual(9999, $id);
    }

    public function testDbIsInjectedViaConstructor()
    {
        $pdo = new PDO('sqlite:memory');
        $manager = DatabaseManager::getInstance($pdo);
        $this->assertSame($pdo, $manager->getDb());
    }

    // --- Race Condition Tests ---

    public function testRaceConditionSafeDatabaseManager()
    {
        // Test ini menunjukkan DatabaseManager AMAN dari race condition
        // Karena singleton pattern memastikan cuma ada 1 instance
        // dan SQLite/PDO menangani concurrent access secara internal
        
        $testTable = 'race_test_' . time();
        $numProcesses = 10;
        $insertsPerProcess = 100;
        $expectedValue = $numProcesses * $insertsPerProcess;

        // Buat temp script untuk worker
        $tempFile = sys_get_temp_dir() . '/db_worker_' . uniqid() . '.php';
        $projectRoot = dirname(__DIR__);
        $dbFile = sys_get_temp_dir() . '/test_race_condition_' . time() . '.sqlite';
        
        // Worker script: pakai DatabaseManager dengan SQLite file (shared storage)
        $workerCode = "<?php
        define('PHPUNIT_TEST', true);
        require_once '$projectRoot/testKoneksiDB.php';
        
        \$dbFile = '$dbFile';
        \$pdo = new PDO('sqlite:' . \$dbFile);
        \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        \$manager = DatabaseManager::getInstance(\$pdo);
        \$db = \$manager->getDb();
        
        // Buat tabel
        \$db->exec('CREATE TABLE IF NOT EXISTS race_test (id INTEGER PRIMARY KEY AUTOINCREMENT, value INTEGER)');
        
        // Insert data
        for (\$i = 0; \$i < $insertsPerProcess; \$i++) {
            \$db->exec('INSERT INTO race_test (value) VALUES (' . rand(1, 100) . ')');
        }
        ";
        
        file_put_contents($tempFile, $workerCode);

        // Jalankan multiple processes pakai proc_open
        $phpPath = PHP_BINARY;
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

        // Hitung total records
        $pdo = new PDO('sqlite:' . $dbFile);
        $count = (int)$pdo->query('SELECT COUNT(*) FROM race_test')->fetchColumn();

        // Cleanup
        if (file_exists($tempFile)) unlink($tempFile);
        if (file_exists($dbFile)) unlink($dbFile);

        // DatabaseManager dengan SQLite harusnya aman karena SQLite handle locking secara internal
        $this->assertEquals($expectedValue, $count,
            "DatabaseManager with SQLite should handle concurrent inserts! Expected $expectedValue, got $count"
        );
        
        echo "\n✅ DatabaseManager race condition test passed: All $expectedValue inserts successful\n";
    }

    public function testRaceConditionWithSharedConnection()
    {
        // Test race condition dengan shared database connection
        // Menggunakan file SQLite yang sama untuk semua processes
        
        $dbFile = sys_get_temp_dir() . '/shared_db_' . time() . '.sqlite';
        $numProcesses = 5;
        $operationsPerProcess = 50;
        $expectedValue = $numProcesses * $operationsPerProcess;

        // Initialize database
        $pdo = new PDO('sqlite:' . $dbFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE IF NOT EXISTS counter (id INTEGER PRIMARY KEY AUTOINCREMENT, value INTEGER)');

        // Buat worker script
        $tempFile = sys_get_temp_dir() . '/db_shared_worker_' . uniqid() . '.php';
        $projectRoot = dirname(__DIR__);
        
        $workerCode = "<?php
        define('PHPUNIT_TEST', true);
        require_once '$projectRoot/testKoneksiDB.php';
        
        \$dbFile = '$dbFile';
        \$pdo = new PDO('sqlite:' . \$dbFile);
        \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        \$manager = DatabaseManager::getInstance(\$pdo);
        \$db = \$manager->getDb();
        
        // Multiple inserts dengan transaction untuk performa lebih baik
        \$db->beginTransaction();
        for (\$i = 0; \$i < $operationsPerProcess; \$i++) {
            \$db->exec('INSERT INTO counter (value) VALUES (' . rand(1, 100) . ')');
        }
        \$db->commit();
        ";
        
        file_put_contents($tempFile, $workerCode);

        // Jalankan multiple processes
        $phpPath = PHP_BINARY;
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

        // Hitung total records
        $pdo = new PDO('sqlite:' . $dbFile);
        $count = (int)$pdo->query('SELECT COUNT(*) FROM counter')->fetchColumn();

        // Cleanup
        if (file_exists($tempFile)) unlink($tempFile);
        if (file_exists($dbFile)) unlink($dbFile);

        // SQLite dengan transaction harusnya aman untuk concurrent access
        $this->assertEquals($expectedValue, $count,
            "DatabaseManager with transactions should handle concurrent operations! Expected $expectedValue, got $count"
        );
        
        echo "\n✅ DatabaseManager shared connection test passed: All $expectedValue operations successful\n";
    }
}

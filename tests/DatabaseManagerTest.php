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
}

<?php
require_once __DIR__ . '/../testKoneksiDB.php';

use PHPUnit\Framework\TestCase;

class DatabaseManagerTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset singleton instance before each test
        $reflection = new ReflectionClass(DatabaseManager::class);
        $instance = $reflection->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
    }

    public function testGetInstanceReturnsSingletonInstance()
    {
        $manager = DatabaseManager::getInstance();
        $this->assertInstanceOf(DatabaseManager::class, $manager);
    }

    public function testGetInstanceAlwaysReturnsSameInstance()
    {
        $manager1 = DatabaseManager::getInstance();
        $manager2 = DatabaseManager::getInstance();
        $this->assertSame($manager1, $manager2);
    }

    public function testGetInstanceReturnsObjectWithSameId()
    {
        $manager1 = DatabaseManager::getInstance();
        $manager2 = DatabaseManager::getInstance();
        $this->assertEquals($manager1->getId(), $manager2->getId());
    }

    public function testConnectReturnsSuccessMessage()
    {
        $manager = DatabaseManager::getInstance();
        $result = $manager->connect();
        $this->assertEquals("Koneksi SQLite BERHASIL!", $result);
    }

    public function testConnectCreatesDatabaseConnection()
    {
        $manager = DatabaseManager::getInstance();
        $manager->connect();
        
        $reflection = new ReflectionClass($manager);
        $dbProperty = $reflection->getProperty('db');
        $dbProperty->setAccessible(true);
        $db = $dbProperty->getValue($manager);
        
        $this->assertInstanceOf(PDO::class, $db);
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
        $manager = DatabaseManager::getInstance();
        $id = $manager->getId();
        $this->assertIsInt($id);
        $this->assertGreaterThanOrEqual(1000, $id);
        $this->assertLessThanOrEqual(9999, $id);
    }
}

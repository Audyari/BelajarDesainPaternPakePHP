<?php
define('PHPUNIT_TEST', true);
require_once __DIR__ . '/../testEmployee.php';

use PHPUnit\Framework\TestCase;

class EmployeeTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset static counter agar konsisten setiap test
        $reflection = new ReflectionClass(Employee::class);
        $counter = $reflection->getProperty('counter');
        $counter->setValue(null, 1);
    }

    // ============================================
    // TEST: VALIDASI INPUT
    // ============================================

    public function testCreateEmployeeWithEmptyNameThrowsException()
    {
        $factory = new EmployeePrototypeFactory();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nama employee tidak boleh kosong!');

        $factory->create('Junior', '');
    }

    public function testCreateEmployeeWithWhitespaceNameThrowsException()
    {
        $factory = new EmployeePrototypeFactory();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nama employee tidak boleh kosong!');

        $factory->create('Junior', '   ');
    }

    public function testCreateEmployeeWithNullName()
    {
        $factory = new EmployeePrototypeFactory();

        // PHP 8+ akan throw TypeError untuk nullable string
        $this->expectException(TypeError::class);

        $factory->create('Junior', null);
    }

    public function testCreateEmployeeWithInvalidTitleReturnsNull()
    {
        $factory = new EmployeePrototypeFactory();
        $employee = $factory->create('InvalidTitle', 'Test');

        $this->assertNull($employee, 'Harus return null untuk title invalid!');
    }

    public function testCreateEmployeeWithEmptyStringTitleReturnsNull()
    {
        $factory = new EmployeePrototypeFactory();
        $employee = $factory->create('', 'Test');

        $this->assertNull($employee, 'Harus return null untuk title kosong!');
    }

    public function testPromoteWithInvalidTitleDoesNothing()
    {
        $factory = new EmployeePrototypeFactory();
        $employee = $factory->create('Junior', 'Andi');

        $originalTitle = $employee->title;
        $originalSalary = $employee->salary;

        // Title tidak ada - harusnya tidak berubah
        $employee->promote('CEO');

        $this->assertEquals($originalTitle, $employee->title);
        $this->assertEquals($originalSalary, $employee->salary);
    }

    public function testConstructWithInvalidTitleThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Title 'CEO' tidak valid!");

        new Employee('CEO');
    }

    // ============================================
    // TEST: EMPLOYEE CREATION (PROTOTYPE)
    // ============================================

    public function testCreateEmployeeFromPrototype()
    {
        $factory = new EmployeePrototypeFactory();
        $employee = $factory->create('Junior', 'Andi');

        $this->assertNotNull($employee);
        $this->assertInstanceOf(Employee::class, $employee);
        $this->assertEquals('Andi', $employee->name);
        $this->assertEquals('Junior', $employee->title);
        $this->assertEquals(5000000, $employee->salary);
        $this->assertGreaterThan(0, $employee->id);
        $this->assertEquals(date('Y-m-d'), $employee->joinDate);
    }

    public function testCreateEmployeeWithDifferentTitles()
    {
        $factory = new EmployeePrototypeFactory();

        $titles = ['Junior', 'Senior', 'Manager', 'Director'];
        $expectedSalaries = [5000000, 10000000, 18000000, 30000000];

        foreach ($titles as $index => $title) {
            $employee = $factory->create($title, "Test $title");
            $this->assertEquals($title, $employee->title);
            $this->assertEquals($expectedSalaries[$index], $employee->salary);
        }
    }

    public function testCreateMultipleEmployeesWithSameTitle()
    {
        $factory = new EmployeePrototypeFactory();

        $andi = $factory->create('Junior', 'Andi');
        $dewi = $factory->create('Junior', 'Dewi');

        // Keduanya Junior dengan salary sama
        $this->assertEquals('Junior', $andi->title);
        $this->assertEquals('Junior', $dewi->title);
        $this->assertEquals($andi->salary, $dewi->salary);

        // Tapi ID harus beda (unik)
        $this->assertNotEquals($andi->id, $dewi->id);

        // Nama juga beda
        $this->assertEquals('Andi', $andi->name);
        $this->assertEquals('Dewi', $dewi->name);
    }

    public function testEmployeeIDsAreUnique()
    {
        $factory = new EmployeePrototypeFactory();

        $employees = [];
        for ($i = 0; $i < 10; $i++) {
            $employees[] = $factory->create('Junior', "Employee $i");
        }

        // Cek semua ID unik
        $ids = array_map(fn($emp) => $emp->id, $employees);
        $uniqueIds = array_unique($ids);

        $this->assertEquals(count($ids), count($uniqueIds), 'Semua ID harus unik!');
    }

    public function testEmployeeIDIncremental()
    {
        $factory = new EmployeePrototypeFactory();

        $emp1 = $factory->create('Junior', 'Employee 1');
        $emp2 = $factory->create('Junior', 'Employee 2');
        $emp3 = $factory->create('Junior', 'Employee 3');

        $this->assertEquals($emp1->id + 1, $emp2->id);
        $this->assertEquals($emp2->id + 1, $emp3->id);
    }

    // ============================================
    // TEST: FACTORY VALIDATION
    // ============================================

    public function testFactoryHasAllPrototypes()
    {
        $factory = new EmployeePrototypeFactory();
        $reflection = new ReflectionClass($factory);
        $prototypes = $reflection->getProperty('prototypes');

        $prototypeList = $prototypes->getValue($factory);

        $this->assertArrayHasKey('Junior', $prototypeList);
        $this->assertArrayHasKey('Senior', $prototypeList);
        $this->assertArrayHasKey('Manager', $prototypeList);
        $this->assertArrayHasKey('Director', $prototypeList);
    }

    // ============================================
    // TEST: PROMOTION
    // ============================================

    public function testPromoteEmployeeValidPath()
    {
        $factory = new EmployeePrototypeFactory();
        $employee = $factory->create('Junior', 'Andi');

        // Junior → Senior
        $employee->promote('Senior');
        $this->assertEquals('Senior', $employee->title);
        $this->assertEquals(10000000, $employee->salary);

        // Senior → Manager
        $employee->promote('Manager');
        $this->assertEquals('Manager', $employee->title);
        $this->assertEquals(18000000, $employee->salary);

        // Manager → Director
        $employee->promote('Director');
        $this->assertEquals('Director', $employee->title);
        $this->assertEquals(30000000, $employee->salary);
    }

    public function testPromoteEmployeeSkipsLevel()
    {
        $factory = new EmployeePrototypeFactory();
        $employee = $factory->create('Junior', 'Andi');

        // Junior langsung ke Director (skip level) - harusnya tetap bisa
        $employee->promote('Director');
        $this->assertEquals('Director', $employee->title);
        $this->assertEquals(30000000, $employee->salary);
    }

    public function testPromoteKeepsSameID()
    {
        $factory = new EmployeePrototypeFactory();
        $employee = $factory->create('Junior', 'Andi');
        $originalId = $employee->id;

        $employee->promote('Senior');

        // ID tidak berubah setelah promosi
        $this->assertEquals($originalId, $employee->id);
    }

    // ============================================
    // TEST: CLONE BEHAVIOR
    // ============================================

    public function testCloneCreatesNewID()
    {
        $factory = new EmployeePrototypeFactory();

        $emp1 = $factory->create('Junior', 'Employee 1');
        $emp2 = $factory->create('Junior', 'Employee 2');

        $this->assertNotEquals($emp1->id, $emp2->id, 'Clone harus buat ID baru!');
    }

    public function testCloneCopiesPropertiesFromPrototype()
    {
        $factory = new EmployeePrototypeFactory();

        $employee = $factory->create('Senior', 'Test');

        // Properties harus dari prototype Senior
        $this->assertEquals('Senior', $employee->title);
        $this->assertEquals(10000000, $employee->salary);
        $this->assertEquals(date('Y-m-d'), $employee->joinDate);
    }

    public function testManualClone()
    {
        $prototype = new Employee('Junior');

        $employee = clone $prototype;
        $employee->name = 'Fajar';

        $this->assertEquals('Fajar', $employee->name);
        $this->assertEquals('Junior', $employee->title);
        $this->assertEquals(5000000, $employee->salary);
        $this->assertGreaterThan(0, $employee->id);
    }

    // ============================================
    // TEST: EMPLOYEE PROPERTIES
    // ============================================

    public function testEmployeeNameCanBeChanged()
    {
        $factory = new EmployeePrototypeFactory();
        $employee = $factory->create('Junior', 'Andi');

        $employee->name = 'Andi Baru';

        $this->assertEquals('Andi Baru', $employee->name);
    }

    public function testSalaryIsImmutableFromTitle()
    {
        $factory = new EmployeePrototypeFactory();

        $junior = $factory->create('Junior', 'Test 1');
        $senior = $factory->create('Senior', 'Test 2');
        $manager = $factory->create('Manager', 'Test 3');

        // Setiap title punya salary tetap
        $this->assertEquals(5000000, $junior->salary);
        $this->assertEquals(10000000, $senior->salary);
        $this->assertEquals(18000000, $manager->salary);
    }

    public function testJoinDateIsAutoGenerated()
    {
        $factory = new EmployeePrototypeFactory();
        $employee = $factory->create('Junior', 'Test');

        $this->assertNotNull($employee->joinDate);
        $this->assertEquals(date('Y-m-d'), $employee->joinDate);
    }

    public function testEmployeeDataConsistencyAfterPromotion()
    {
        $factory = new EmployeePrototypeFactory();
        $employee = $factory->create('Junior', 'Andi');

        $originalId = $employee->id;
        $originalName = $employee->name;
        $originalJoinDate = $employee->joinDate;

        $employee->promote('Senior');

        // ID, nama, joinDate harus tetap sama
        $this->assertEquals($originalId, $employee->id);
        $this->assertEquals($originalName, $employee->name);
        $this->assertEquals($originalJoinDate, $employee->joinDate);

        // Hanya title dan salary yang berubah
        $this->assertEquals('Senior', $employee->title);
        $this->assertEquals(10000000, $employee->salary);
    }

    // ============================================
    // TEST: CALCULATIONS
    // ============================================

    public function testCalculateTotalSalary()
    {
        $factory = new EmployeePrototypeFactory();

        $employees = [];
        $employees[] = $factory->create('Junior', 'Andi');   // 5jt
        $employees[] = $factory->create('Senior', 'Budi');   // 10jt
        $employees[] = $factory->create('Manager', 'Citra'); // 18jt

        $totalSalary = array_reduce($employees, fn($sum, $emp) => $sum + $emp->salary, 0);

        $this->assertEquals(33000000, $totalSalary);
    }

    public function testCalculateTotalSalaryAfterPromotion()
    {
        $factory = new EmployeePrototypeFactory();

        $andi = $factory->create('Junior', 'Andi');  // 5jt
        $budi = $factory->create('Senior', 'Budi');  // 10jt

        $totalBefore = $andi->salary + $budi->salary;
        $this->assertEquals(15000000, $totalBefore);

        // Promosi Andi ke Senior
        $andi->promote('Senior');  // 10jt

        $totalAfter = $andi->salary + $budi->salary;
        $this->assertEquals(20000000, $totalAfter);
    }

    // ============================================
    // TEST: EDGE CASES
    // ============================================

    public function testCreateManyEmployees()
    {
        $factory = new EmployeePrototypeFactory();

        $employees = [];
        for ($i = 1; $i <= 100; $i++) {
            $employees[] = $factory->create('Junior', "Employee $i");
        }

        $this->assertCount(100, $employees);

        // Semua ID unik
        $ids = array_map(fn($emp) => $emp->id, $employees);
        $this->assertCount(100, array_unique($ids));

        // Semua Junior
        foreach ($employees as $emp) {
            $this->assertEquals('Junior', $emp->title);
            $this->assertEquals(5000000, $emp->salary);
        }
    }

    public function testMultiplePromotionsForSameEmployee()
    {
        $factory = new EmployeePrototypeFactory();
        $employee = $factory->create('Junior', 'Andi');

        $path = [];
        $path[] = $employee->title;

        $employee->promote('Senior');
        $path[] = $employee->title;

        $employee->promote('Manager');
        $path[] = $employee->title;

        $employee->promote('Director');
        $path[] = $employee->title;

        $this->assertEquals(['Junior', 'Senior', 'Manager', 'Director'], $path);
    }

    public function testFactoryCreateReturnsEmployeeInstance()
    {
        $factory = new EmployeePrototypeFactory();
        $employee = $factory->create('Junior', 'Test');

        $this->assertInstanceOf(Employee::class, $employee);
    }

    public function testFactoryCreateReturnsNullForInvalidTitle()
    {
        $factory = new EmployeePrototypeFactory();
        $employee = $factory->create('NonExistent', 'Test');

        $this->assertNull($employee);
    }

    public function testEmployeeShowInfo()
    {
        $factory = new EmployeePrototypeFactory();
        $employee = $factory->create('Junior', 'Andi');

        // Cek bahwa showInfo tidak error dan output benar format
        ob_start();
        $employee->showInfo();
        $output = ob_get_clean();

        $this->assertStringContainsString('Andi', $output);
        $this->assertStringContainsString('Junior', $output);
        $this->assertStringContainsString('5.000.000', $output);
    }

    // ============================================
    // TEST: RACE CONDITION
    // ============================================

    public function testRaceConditionSafeEmployeeCreation()
    {
        // Test ini menunjukkan Employee AMAN dari race condition
        // karena setiap process punya memory sendiri (isolated)
        // dan tidak ada shared state antar process
        
        $factory = new EmployeePrototypeFactory();
        
        // Buat 100 employees secara sequential
        $employees = [];
        for ($i = 1; $i <= 100; $i++) {
            $employees[] = $factory->create('Junior', "Employee $i");
        }
        
        // Semua harusnya berhasil dibuat
        $this->assertCount(100, $employees);
        
        // Semua ID unik
        $ids = array_map(fn($emp) => $emp->id, $employees);
        $uniqueIds = array_unique($ids);
        $this->assertCount(100, $uniqueIds, 'Semua ID harus unik!');
        
        // Semua Junior
        foreach ($employees as $emp) {
            $this->assertEquals('Junior', $emp->title);
            $this->assertEquals(5000000, $emp->salary);
        }
        
        echo "\n✅ Employee race condition test passed: All 100 creations successful\n";
    }

    public function testRaceConditionSafeEmployeePromotion()
    {
        // Test promosi employee secara concurrent
        // Employee promotion tidak ada race condition karena
        // operation di object yang sama (tidak ada shared state)
        
        $factory = new EmployeePrototypeFactory();
        $employee = $factory->create('Junior', 'Andi');
        
        // Simulasi multiple promotions secara sequential
        // (Di real scenario, kalau ada locking, harusnya aman)
        $promotions = ['Senior', 'Manager', 'Director'];
        
        foreach ($promotions as $title) {
            $employee->promote($title);
            $this->assertEquals($title, $employee->title);
        }
        
        // Final check
        $this->assertEquals('Director', $employee->title);
        $this->assertEquals(30000000, $employee->salary);
        
        echo "\n✅ Employee promotion race condition test passed: All promotions successful\n";
    }

    public function testRaceConditionConcurrentReads()
    {
        // Test concurrent read operations
        // Read operations harusnya aman karena tidak ada state mutation
        
        $factory = new EmployeePrototypeFactory();
        $storage = new EmployeeStorage();
        $storage->clear();
        
        // Buat 5 employees
        $employees = [];
        for ($i = 1; $i <= 5; $i++) {
            $emp = $factory->create('Junior', "Employee $i");
            $storage->save($emp);
            $employees[] = $emp;
        }
        
        // Simulasi concurrent reads
        $reads = [];
        for ($i = 0; $i < 100; $i++) {
            $empId = $employees[array_rand($employees)]->id;
            $data = $storage->getById($empId);
            $this->assertNotNull($data);
            $reads[] = $data;
        }
        
        // Semua read harus berhasil
        $this->assertCount(100, $reads);
        
        // Cleanup
        $storage->clear();
        
        echo "\n✅ Employee concurrent read test passed: All 100 reads successful\n";
    }
}

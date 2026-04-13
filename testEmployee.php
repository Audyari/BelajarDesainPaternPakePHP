<?php

// ============================================
// APLIKASI KEPEGAWAIAN - Prototype Pattern
// Clone dari template, gak perlu buat dari nol
// ============================================

class Employee
{
    public int $id;
    public string $name;
    public string $title;
    public int $salary;
    public string $joinDate;

    // Daftar jabatan dan gaji (prototype templates)
    private const SALARY_MAP = [
        'Junior' => 5000000,
        'Senior' => 10000000,
        'Manager' => 18000000,
        'Director' => 30000000,
    ];

    // Static counter untuk ID unik
    private static int $counter = 1;

    // Constructor untuk buat prototype
    public function __construct(?string $title = null)
    {
        if ($title !== null) {
            if (!isset(self::SALARY_MAP[$title])) {
                throw new InvalidArgumentException("Title '$title' tidak valid! Pilih: " . implode(', ', array_keys(self::SALARY_MAP)));
            }
            
            $this->id = self::$counter++;
            $this->title = $title;
            $this->salary = self::SALARY_MAP[$title];
            $this->joinDate = date('Y-m-d');
        }
    }

    // Clone magic method: pastikan ID tetap unik saat di-clone
    public function __clone(): void
    {
        $this->id = self::$counter++;  // ID baru saat clone
    }

    // Promosi: ganti title, gaji otomatis ikut berubah
    public function promote(string $newTitle): void
    {
        if (!isset(self::SALARY_MAP[$newTitle])) {
            return;
        }

        $this->title = $newTitle;
        $this->salary = self::SALARY_MAP[$newTitle];
    }

    // Tampilkan info employee
    public function showInfo(): void
    {
        echo sprintf(
            "ID: %d | Nama: %s | Jabatan: %s | Gaji: Rp %s | Join: %s\n",
            $this->id,
            $this->name,
            $this->title,
            number_format($this->salary, 0, ',', '.'),
            $this->joinDate
        );
    }
}

// ============================================
// PROTOTYPE FACTORY
// Simpan template untuk setiap title
// ============================================
class EmployeePrototypeFactory
{
    private array $prototypes = [];

    public function __construct()
    {
        // Buat 1 prototype untuk setiap title
        foreach (['Junior', 'Senior', 'Manager', 'Director'] as $title) {
            $this->prototypes[$title] = new Employee($title);
        }
    }

    // Clone dari prototype, tinggal isi nama
    public function create(string $title, string $name): ?Employee
    {
        // Validasi title
        if (!isset($this->prototypes[$title])) {
            return null;
        }

        // Validasi nama tidak boleh kosong
        if (trim($name) === '') {
            throw new InvalidArgumentException("Nama employee tidak boleh kosong!");
        }

        // Clone dari prototype
        $employee = clone $this->prototypes[$title];
        $employee->name = $name;  // Cuma ini yang perlu diganti

        return $employee;
    }
}

// ============================================
// EMPLOYEE STORAGE (File-based with locking)
// Untuk simpan data employee ke file
// ============================================
class EmployeeStorage
{
    private string $storageDir;

    public function __construct(?string $storageDir = null)
    {
        $this->storageDir = $storageDir ?? sys_get_temp_dir() . '/employee_storage';
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0777, true);
        }
    }

    public function save(Employee $employee): void
    {
        $file = $this->getFilePath($employee->id);
        $data = json_encode([
            'id' => $employee->id,
            'name' => $employee->name,
            'title' => $employee->title,
            'salary' => $employee->salary,
            'joinDate' => $employee->joinDate,
        ], JSON_PRETTY_PRINT);

        file_put_contents($file, $data, LOCK_EX);
    }

    public function getById(int $id): ?array
    {
        $file = $this->getFilePath($id);
        if (!file_exists($file)) {
            return null;
        }

        $content = file_get_contents($file);
        return json_decode($content, true);
    }

    public function getAll(): array
    {
        $employees = [];
        $files = glob($this->storageDir . '/*.json');

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $employees[] = json_decode($content, true);
        }

        return $employees;
    }

    public function getTotalSalary(): int
    {
        $employees = $this->getAll();
        return array_reduce($employees, fn($sum, $emp) => $sum + $emp['salary'], 0);
    }

    public function clear(): void
    {
        $files = glob($this->storageDir . '/*.json');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    private function getFilePath(int $id): string
    {
        return $this->storageDir . "/employee_$id.json";
    }
}

// ============================================
// EKSEKUSI DEMO
// ============================================
if (!defined('PHPUNIT_TEST')) {
    echo "=== Aplikasi Kepegawaian (Prototype Pattern) ===\n\n";

    // 1. Buat factory dengan semua prototype
    $factory = new EmployeePrototypeFactory();
    echo "✅ Prototype factory siap dengan 4 templates (Junior, Senior, Manager, Director)\n\n";

    // 2. Buat employees dari prototype (lebih cepat!)
    $employees = [];
    $employees[] = $factory->create('Junior', 'Andi');
    $employees[] = $factory->create('Senior', 'Budi');
    $employees[] = $factory->create('Manager', 'Citra');
    $employees[] = $factory->create('Junior', 'Dewi');  // Clone dari prototype yang sama
    $employees[] = $factory->create('Director', 'Eko');

    // 3. Tampilkan semua
    echo "📋 Daftar Employees:\n";
    foreach ($employees as $emp) {
        $emp->showInfo();
    }

    // 4. Promosi Andi
    echo "\n🎉 Promosi Andi (Junior → Senior):\n";
    $employees[0]->promote('Senior');
    echo "  ";
    $employees[0]->showInfo();

    // 5. Hitung total gaji
    $totalGaji = 0;
    foreach ($employees as $emp) {
        $totalGaji += $emp->salary;
    }
    echo "\n💰 Total Gaji Semua Karyawan: Rp " . number_format($totalGaji, 0, ',', '.') . "\n";

    // 6. Demo: clone manual dari prototype
    echo "\n🔄 Demo Clone Manual:\n";
    $juniorPrototype = new Employee('Junior');
    
    $fajar = clone $juniorPrototype;
    $fajar->name = 'Fajar';
    echo "✅ Fajar di-clone dari prototype Junior\n";
    echo "  ";
    $fajar->showInfo();
}

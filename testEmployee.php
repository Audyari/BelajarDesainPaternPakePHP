<?php

// ============================================
// APLIKASI KEPEGAWAIAN - Versi Simple
// Tanpa Design Pattern, mudah dipahami
// ============================================

class Employee
{
    public int $id;
    public string $name;
    public string $title;
    public int $salary;
    public string $joinDate;

    // Daftar jabatan dan gaji (simple array)
    private const SALARY_MAP = [
        'Junior' => 5000000,
        'Senior' => 10000000,
        'Manager' => 18000000,
        'Director' => 30000000,
    ];

    public function __construct(string $name, string $title)
    {
        static $autoId = 1;
        
        $this->id = $autoId++;
        $this->name = $name;
        $this->title = $title;
        $this->salary = self::SALARY_MAP[$title]; // Otomatis dari title
        $this->joinDate = date('Y-m-d');
    }

    // Promosi: ganti title, gaji otomatis ikut berubah
    public function promote(string $newTitle): void
    {
        if (!isset(self::SALARY_MAP[$newTitle])) {
            echo "❌ Title '$newTitle' tidak valid!\n";
            return;
        }

        $this->title = $newTitle;
        $this->salary = self::SALARY_MAP[$newTitle];
        echo "✅ {$this->name} dipromosikan ke $newTitle\n";
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
// EKSEKUSI DEMO
// ============================================
if (!defined('PHPUNIT_TEST')) {
    echo "=== Aplikasi Kepegawaian (Versi Simple) ===\n\n";

    // 1. Buat employees
    $employees = [];
    $employees[] = new Employee("Andi", "Junior");
    $employees[] = new Employee("Budi", "Senior");
    $employees[] = new Employee("Citra", "Manager");
    $employees[] = new Employee("Dewi", "Junior");
    $employees[] = new Employee("Eko", "Director");

    // 2. Tampilkan semua
    echo "📋 Daftar Employees:\n";
    foreach ($employees as $emp) {
        $emp->showInfo();
    }

    // 3. Promosi Andi
    echo "\n🎉 Promosi Andi (Junior → Senior):\n";
    $employees[0]->promote('Senior');
    echo "  ";
    $employees[0]->showInfo();

    // 4. Hitung total gaji
    $totalGaji = 0;
    foreach ($employees as $emp) {
        $totalGaji += $emp->salary;
    }
    echo "\n💰 Total Gaji Semua Karyawan: Rp " . number_format($totalGaji, 0, ',', '.') . "\n";
}

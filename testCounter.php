<?php

// Interface = kontrak. Semua counter storage HARUS punya method ini
interface CounterInterface
{
    public function get(string $key): int;
    public function set(string $key, int $value): void;
    public function increment(string $key, int $step): int;
    public function decrement(string $key, int $step): int;
    public function reset(string $key): void;
}

// Implementasi pake array (in-memory)
class ArrayCounter implements CounterInterface
{
    private array $data = [];

    public function get(string $key): int
    {
        return $this->data[$key] ?? 0;
    }

    public function set(string $key, int $value): void
    {
        $this->data[$key] = $value;
    }

    public function increment(string $key, int $step): int
    {
        $value = $this->get($key) + $step;
        $this->set($key, $value);
        return $value;
    }

    public function decrement(string $key, int $step): int
    {
        $value = $this->get($key) - $step;
        $this->set($key, $value);
        return $value;
    }

    public function reset(string $key): void
    {
        $this->set($key, 0);
    }
}

// Singleton: Class ini cuma boleh punya 1 instance sepanjang program
class Counter
{
    private static ?Counter $instance = null; // Nyimpen 1 instance
    private CounterInterface $counter;        // Dependency (DI)

    // Private constructor = gak bisa pake 'new Counter()' dari luar
    private function __construct(CounterInterface $counter)
    {
        $this->counter = $counter;  // Dependency Injection terjadi di sini
    }

    private function __clone() {}

    // Satu-satunya cara dapetin instance Counter
    public static function getInstance(CounterInterface $counter): Counter
    {
        if (self::$instance === null) {
            self::$instance = new self($counter);  // Baru dibuat pertama kali
        }
        return self::$instance;
    }

    public function increment(string $key = 'default', int $step = 1): int
    {
        return $this->counter->increment($key, $step);
    }

    public function decrement(string $key = 'default', int $step = 1): int
    {
        return $this->counter->decrement($key, $step);
    }

    public function get(string $key = 'default'): int
    {
        return $this->counter->get($key);
    }

    public function reset(string $key = 'default'): void
    {
        $this->counter->reset($key);
    }
}

// ============================================
// EKSEKUSI
// ============================================
echo "=== Counter Demo (Singleton + DI) ===\n";
$arrayCounter = new ArrayCounter();
$counter = Counter::getInstance($arrayCounter);

$counter2 = Counter::getInstance($arrayCounter); // Instance sama

echo "Counter: " . $counter->get() . "\n";
echo "Increment: " . $counter->increment() . "\n";
echo "Increment: " . $counter2->increment() . "\n";
echo "Increment: " . $counter->increment() . "\n";
echo "Decrement: " . $counter->decrement() . "\n";
echo "Current: " . $counter->get() . "\n";
echo "Current (counter2): " . $counter2->get() . "\n";

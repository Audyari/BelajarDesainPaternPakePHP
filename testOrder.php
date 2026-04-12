<?php

// Order Item
class OrderItem
{
    public function __construct(
        private string $product,
        private int $quantity,
        private float $price
    ) {}

    public function getProduct(): string
    {
        return $this->product;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getTotal(): float
    {
        return $this->quantity * $this->price;
    }
}

// Order class
class Order
{
    private array $items = [];
    private string $status = 'pending';
    private float $taxRate = 0.1;
    private ?string $customerId = null;
    private ?string $shippingAddress = null;
    private ?string $billingAddress = null;
    private ?string $paymentMethod = null;
    private ?string $voucherCode = null;
    private float $discount = 0;
    private float $shippingCost = 0;
    private ?string $notes = null;
    private bool $isGift = false;
    private ?string $giftMessage = null;

    // Constructor internal (diakses via builder)
    public function __construct() {}

    // Getter methods
    public function getItems(): array
    {
        return $this->items;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    public function getShippingAddress(): ?string
    {
        return $this->shippingAddress;
    }

    public function getBillingAddress(): ?string
    {
        return $this->billingAddress;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function getVoucherCode(): ?string
    {
        return $this->voucherCode;
    }

    public function getDiscount(): float
    {
        return $this->discount;
    }

    public function getShippingCost(): float
    {
        return $this->shippingCost;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function isGift(): bool
    {
        return $this->isGift;
    }

    public function getGiftMessage(): ?string
    {
        return $this->giftMessage;
    }

    public function getTaxRate(): float
    {
        return $this->taxRate;
    }

    public function getSubtotal(): float
    {
        return array_sum(array_map(fn($item) => $item->getTotal(), $this->items));
    }

    public function getTax(): float
    {
        return $this->getSubtotal() * $this->taxRate;
    }

    public function getTotal(): float
    {
        return $this->getSubtotal() + $this->getTax() + $this->shippingCost - $this->discount;
    }

    public function getItemCount(): int
    {
        return count($this->items);
    }

    // Builder Pattern
    public static function builder(): OrderBuilder
    {
        return new OrderBuilder();
    }
}

// Order Builder
class OrderBuilder
{
    private Order $order;

    public function __construct()
    {
        $this->order = new Order();
    }

    public function setCustomerId(string $customerId): self
    {
        $reflection = new ReflectionClass($this->order);
        $prop = $reflection->getProperty('customerId');
        $prop->setValue($this->order, $customerId);
        return $this;
    }

    public function addItem(string $product, int $quantity, float $price): self
    {
        if ($quantity <= 0 || $price < 0) {
            throw new InvalidArgumentException("Quantity harus > 0, price harus >= 0");
        }
        $reflection = new ReflectionClass($this->order);
        $prop = $reflection->getProperty('items');
        $items = $prop->getValue($this->order);
        $items[] = new OrderItem($product, $quantity, $price);
        $prop->setValue($this->order, $items);
        return $this;
    }

    public function setTaxRate(float $taxRate): self
    {
        $reflection = new ReflectionClass($this->order);
        $prop = $reflection->getProperty('taxRate');
        $prop->setValue($this->order, $taxRate);
        return $this;
    }

    public function setShippingAddress(string $address): self
    {
        $reflection = new ReflectionClass($this->order);
        $prop = $reflection->getProperty('shippingAddress');
        $prop->setValue($this->order, $address);
        return $this;
    }

    public function setBillingAddress(string $address): self
    {
        $reflection = new ReflectionClass($this->order);
        $prop = $reflection->getProperty('billingAddress');
        $prop->setValue($this->order, $address);
        return $this;
    }

    public function setPaymentMethod(string $method): self
    {
        $reflection = new ReflectionClass($this->order);
        $prop = $reflection->getProperty('paymentMethod');
        $prop->setValue($this->order, $method);
        return $this;
    }

    public function setVoucherCode(string $code): self
    {
        $reflection = new ReflectionClass($this->order);
        $prop = $reflection->getProperty('voucherCode');
        $prop->setValue($this->order, $code);
        return $this;
    }

    public function setDiscount(float $discount): self
    {
        if ($discount < 0) {
            throw new InvalidArgumentException("Discount tidak boleh negatif");
        }
        $reflection = new ReflectionClass($this->order);
        $prop = $reflection->getProperty('discount');
        $prop->setValue($this->order, $discount);
        return $this;
    }

    public function setShippingCost(float $cost): self
    {
        if ($cost < 0) {
            throw new InvalidArgumentException("Shipping cost tidak boleh negatif");
        }
        $reflection = new ReflectionClass($this->order);
        $prop = $reflection->getProperty('shippingCost');
        $prop->setValue($this->order, $cost);
        return $this;
    }

    public function setNotes(string $notes): self
    {
        $reflection = new ReflectionClass($this->order);
        $prop = $reflection->getProperty('notes');
        $prop->setValue($this->order, $notes);
        return $this;
    }

    public function setGift(bool $isGift, ?string $message = null): self
    {
        $reflection = new ReflectionClass($this->order);
        $prop = $reflection->getProperty('isGift');
        $prop->setValue($this->order, $isGift);
        
        if ($isGift && $message !== null) {
            $prop = $reflection->getProperty('giftMessage');
            $prop->setValue($this->order, $message);
        }
        return $this;
    }

    public function build(): Order
    {
        return $this->order;
    }
}

// ============================================
// EKSEKUSI
// ============================================
echo "=== Builder Pattern Demo ===\n";

// Contoh 1: Order simpel (cuma item + tax)
echo "\n--- Simple Order ---\n";
$simpleOrder = Order::builder()
    ->addItem('Laptop', 1, 10000000)
    ->build();

echo "Total: Rp " . number_format($simpleOrder->getTotal(), 0, ',', '.') . "\n";

// Contoh 2: Order lengkap (10+ komponen opsional)
echo "\n--- Full Order ---\n";
$fullOrder = Order::builder()
    ->setCustomerId('CUST-001')
    ->addItem('Laptop', 1, 10000000)
    ->addItem('Mouse', 2, 150000)
    ->setTaxRate(0.1)
    ->setShippingAddress('Jl. Merdeka No. 1, Jakarta')
    ->setBillingAddress('Jl. Sudirman No. 5, Jakarta')
    ->setPaymentMethod('Credit Card')
    ->setVoucherCode('DISKON10')
    ->setDiscount(100000)
    ->setShippingCost(50000)
    ->setNotes('Antar sebelum jam 5 sore')
    ->setGift(true, 'Selamat ulang tahun!')
    ->build();

echo "Customer: " . $fullOrder->getCustomerId() . "\n";
echo "Items: " . $fullOrder->getItemCount() . "\n";
echo "Subtotal: Rp " . number_format($fullOrder->getSubtotal(), 0, ',', '.') . "\n";
echo "Tax (10%): Rp " . number_format($fullOrder->getTax(), 0, ',', '.') . "\n";
echo "Discount: Rp " . number_format($fullOrder->getDiscount(), 0, ',', '.') . "\n";
echo "Shipping: Rp " . number_format($fullOrder->getShippingCost(), 0, ',', '.') . "\n";
echo "Total: Rp " . number_format($fullOrder->getTotal(), 0, ',', '.') . "\n";
echo "Payment: " . $fullOrder->getPaymentMethod() . "\n";
echo "Ship to: " . $fullOrder->getShippingAddress() . "\n";
echo "Gift: " . ($fullOrder->isGift() ? 'Yes - ' . $fullOrder->getGiftMessage() : 'No') . "\n";
echo "Notes: " . $fullOrder->getNotes() . "\n";

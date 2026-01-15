<?php


namespace App\Livewire;

use Midtrans\Snap;
use App\Models\Order;
use App\Models\Address;
use Livewire\Component;
use Midtrans\Config;
use Illuminate\Http\Request;
use Livewire\Attributes\Title;
use App\Helpers\CartManagement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

#[Title('Payment')]
class PaymentPage extends Component {

    public $first_name;
    public $last_name;
    public $phone;
    public $street_address;
    public $city;
    public $state;
    public $zip_code;
    public $payment_method = 'midtrans'; // Default to midtrans
    
    public $cart_items = [];
    public $grand_total = 0;
    public $snapToken;

    public function mount()
    {
        $this->cart_items = CartManagement::getCartItemsFromCookie();
        if (count($this->cart_items) == 0) {
            return redirect('/products');
        }
        $this->grand_total = CartManagement::calculateGrandTotal($this->cart_items);

        // Pre-fill user data if available (Optional, but good UX)
        $user = Auth::user();
        if ($user) {
            $this->first_name = $this->first_name ?? $user->name; // Simple split could be better but name is usually one field
        }
    }

    public function createTransaction() {
        $this->cart_items = CartManagement::getCartItemsFromCookie();
        $this->grand_total = CartManagement::calculateGrandTotal($this->cart_items);

        $user = Auth::user();

        $order = new Order();
        $order->user_id = $user->id;
        $order->grand_total = $this->grand_total;
        $order->payment_method = $this->payment_method;
        $order->payment_status = 'pending';
        $order->status = 'new';
        $order->currency = 'idr';
        $order->shipping_amount = 0;
        $order->shipping_method = 'none';
        $order->notes = 'Order placed by ' . $user->name;
        
        // Save Address - For now just creating the object as per original code
        $address = new Address();
        $address->first_name = $this->first_name;
        $address->last_name = $this->last_name;
        $address->phone = $this->phone;
        $address->street_address = $this->street_address;
        $address->city = $this->city;
        $address->state = $this->state;
        $address->zip_code = $this->zip_code;
        
        // Midtrans Config
        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3ds');

        $params = [
            'transaction_details' => [
                'order_id' => uniqid(),
                'gross_amount' => (int) $this->grand_total,
            ],
            'customer_details' => [
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $user->email,
                'phone' => $this->phone,
            ],
        ];

        $this->snapToken = Snap::getSnapToken($params);
        
        $this->dispatch('snap-token-generated', token: $this->snapToken);
    }

    public function render()
    {
        return view('livewire.payment-page', [
            'grand_total' => $this->grand_total,
            'cart_items' => $this->cart_items,
        ]);
    }
}


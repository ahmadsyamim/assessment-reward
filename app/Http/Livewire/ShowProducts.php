<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Product;
use App\Models\Reward;
use App\Models\Order;
use App\Models\OrderProduct;

class ShowProducts extends Component
{
    public $page = 'products';
    public $cart = [];
    public $total = 0;
    public $total_points = 0;
    public $points = 0;
    public $available_points = 0;
    public $use_points = 0;
    public function mount()
    {
        $this->cart = Collect(session('cart')) ?? Collect();
    }
    public function addtocart($product_id)
    {
        $product = Product::find($product_id);
        $this->cart[] = $product;

        $this->updatecart($this->cart);
        session()->flash('message', 'Product added to cart.');
    }

    public function removefromcart($product_id)
    {
        $cart = Collect($this->cart);
        $selected = []; 
        foreach ($cart as $key => $item) {
            if ($item['id'] == $product_id) {
                $selected[] = $cart->pull($key);
            }
        }
        $this->updatecart($cart);
        session(['cart' => $this->cart]);
        session()->flash('message', 'Product removed from cart.');
    }
    public function setpage($type)
    {
        $this->page = $type;
    }
    public function updated($propertyName)
    {
        if ($propertyName == 'use_points') 
        {
            if ($this->use_points > $this->getreward()) {
                session()->flash('use_points', 'Maximum point is :'.$this->getreward());
                $this->use_points = $this->getreward();
            } 
        }
    }
    public function checkout()
    {
        if (!count($this->cart)) {
            session()->flash('error', 'Cart is empty.');
            return false;
        }
        
        $data = [
            'user_id' => auth()->user()->id,
            'subtotal' => $this->total,
            'subtotal_point' => $this->use_points,
            'points' => $this->total_points,
            'order_status' => 'Complete', // Asssume/Simulate payment success/complete
        ];

        $order = Order::create($data);
        
        foreach ($this->cart as $cart) {
            $cart_id = $cart['id'];
            $dataProd[$cart_id] = [
                'order_id' => $order->id,
                'product_id' => $cart_id,
                'subtotal' => isset($dataProd[$cart_id]['subtotal']) ? $dataProd[$cart_id]['subtotal']+$cart['normal_price'] : $cart['normal_price'] ,
                'name' => $cart['name'],
                'quantity' => isset($dataProd[$cart_id]['quantity']) ? $dataProd[$cart_id]['quantity']+1 : 1,
                'normal_price' => $cart['normal_price'],
                'promotion_price' => $cart['promotion_price'],
            ];
        }
        if (count($dataProd)) {
            foreach ($dataProd as $d) {
                $data = $d;
                $orderProd = OrderProduct::create($data);
            }
        }

        // Update reward
        $this->updateReward($this->use_points);
        
        // Add Reward
        $this->addReward($order->id);

        $this->resetcart();

        $this->page='product';

        session()->flash('message', 'Order created.');
    }
    public function updateReward($use_points)
    {
        $point = $use_points;
        $reward = Reward::where('user_id',auth()->user()->id)->where('point','<>','point_used')->whereDate('expiry_date', '>', now())->get();
        foreach ($reward as $r) {
            $avail = $r->point - $r->point_used;
            $cpoint = $point;
            $point = $point - $avail;
            if ($point >= 0) {
                // Overpoint
                $cpoint = $r->point;
                Reward::where('id', $r->id)
                    ->update(['point_used' => $r->point]);
                continue;
            } else if ($point < 0) {
                // Lowpoint
                Reward::where('id', $r->id)
                    ->update(['point_used' => abs($cpoint)]);
                break;
            }
        }
        
    }
    public function addReward($order_id)
    {
        $order = Order::find($order_id);

        if ($order->order_status == 'Complete')
        {
            $rewardData = [
                'user_id' => auth()->user()->id,
                'expiry_date' => date('Y-m-d', strtotime('+1 years')),
                'point' => floor($this->cart->sum('normal_price')),
            ];
            $reward = Reward::create($rewardData);
        }
    }
    public function resetcart() 
    {
        $this->cart = Collect();
        $this->use_points = 0;
        session(['cart' => $this->cart]);
    }
    public function updatecart($cart = false) 
    {
        if ($cart) {
            $this->cart = $cart;
            session(['cart' => $this->cart]);    
        }
        $this->total = $this->cart->sum('normal_price');
        $this->total_points = floor($this->cart->sum('normal_price'));

    }
    public function getreward() 
    {
        $add = Reward::where('user_id',auth()->user()->id)->where('point','<>','point_used')->whereDate('expiry_date', '>', now())->get()->sum('point');
        $minus = Reward::where('user_id',auth()->user()->id)->where('point','<>','point_used')->whereDate('expiry_date', '>', now())->get()->sum('point_used');
        $total = $add - $minus;
        if ($total < 0) {
            dd('error');
        }
        return $total;
    }
    public function render()
    {
        return view('livewire.show-products', [
            'products' => Product::all(),
            'reward' => $this->getreward(),
        ]);
    }
}

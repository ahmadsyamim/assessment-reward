<div>
    @if (session()->has('error'))
    <div class="ui negative message">
        {{ session('error') }}
    </div>
    @endif
    @if (session()->has('message'))
    <div class="ui positive message">
        {{ session('message') }}
    </div>
    @endif

    <div class="ui compact menu">
        <a class="item" wire:click="setpage('product')">
            <i class="icon"></i> Products
        </a>
        <a class="item" wire:click="setpage('cart')">
            <i class="icon cart"></i> Cart
            <div class="floating ui blue label">{{count($cart)}}</div>
        </a>
        <a class="item" wire:click="setpage('checkout')">
            <i class="icon shipping fast"></i> Checkout
        </a>
    </div>
    @if ($page == 'checkout')
    <div>
        <div class="ui card">
            <div class="content">
                Checkout
            </div>
            <div class="image">
            </div>
            <div class="content">
                @if (count($cart)) 
                <div>
                    Total: USD {{$cart->sum('normal_price')}}
                </div>
                <div>
                    Point: {{floor($cart->sum('normal_price'))}}
                </div>
                <div>
                    Available point: {{$reward}}
                </div>
                @if ($reward)
                <div>
                    @if (session()->has('use_points'))
                    <div class="ui negative message">
                        {{ session('use_points') }}
                    </div>
                    @endif
                    {{-- @error('use_points') <div class="ui negative message">{{ $message }}</div> @enderror --}}
                </div>
                <div class="ui input">
                    Use point: <input type="text" name="use_points" wire:model="use_points" >
                </div>
                <div>
                    Price after deduction: USD {{ $cart->sum('normal_price') ? (int)$cart->sum('normal_price') - ((int)$use_points/100): 0 }}
                </div>
                <div>
                    {{$use_points}} = {{(int)$use_points/100}}
                </div>
                @endif


                <div>
                    <button class="ui button" wire:click="checkout">Confirm checkout</button>
                </div>
                <span class="right floated">
                </span>
                @else
                Cart is empty
                @endif

            </div>
        </div>

    </div>
    @elseif ($page == 'cart')
    <div class="ui cards">
        @if (count($cart)) 
        @foreach ($cart as $product)
        <div class="ui card">
            <div class="content">
                {{$product['name']}}
            </div>
            <div class="image">
                <img class="" src="https://fomantic-ui.com/images/wireframe/image.png">
            </div>
            <div class="content">
                <span class="right floated">
                    <button wire:click="removefromcart({{$product['id']}})">Remove from cart</button>
                </span>
                <i class="dollar icon"></i>
                {{$product['normal_price']}}

                <i class="dollar icon"></i>
                {{$product['promotion_price']}}

            </div>
        </div>
        @endforeach
        @else
        <div class="ui card">
            <div class="content">
        Cart is empty
            </div>
        </div>
        @endif
    </div>
    @else
    <div class="ui cards">
        @foreach ($products as $product)
        <div class="ui card">
            <div class="content">
                {{$product->name}}
            </div>
            <div class="image">
                <img class="" src="https://fomantic-ui.com/images/wireframe/image.png">
            </div>
            <div class="content">
                <span class="right floated">
                    <button wire:click="addtocart({{$product->id}})">Add to cart</button>
                </span>
                <i class="dollar icon"></i>
                {{$product->normal_price}}

                <i class="dollar icon"></i>
                {{$product->promotion_price}}

            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

<?php

namespace App\Http\Controllers;

use App\Mail\NewAccountCredentials;
use App\Mail\OrderConfirmation;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\ManualPayment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\PaymentTransaction;
use App\Models\Setting;
use App\Models\ShippingZoneDistrict;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    /**
     * Session key holding the one-time login details of an account created during
     * checkout for a customer who gave us no email address. Consumed — and cleared —
     * by the confirmation screen.
     */
    public const NEW_CREDENTIALS_KEY = 'checkout.new_credentials';

    public function index()
    {
        $cartItems = $this->cartItems()
            ->with(['product.translations', 'product.primaryImage', 'variant.options'])
            ->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('cart_error', __('front.cart_empty'));
        }

        $subtotal = $cartItems->sum('line_total');

        $addresses = auth()->check()
            ? auth()->user()->addresses()->latest()->get()
            : collect();

        // All districts for shipping dropdown (sorted alphabetically)
        $districts = ShippingZoneDistrict::orderBy('district_name')->pluck('district_name')->unique()->values();

        // Enabled payment methods from settings
        $paymentMethods = [];
        if (Setting::get('cod_enabled', '1') == '1') {
            $paymentMethods[] = 'cod';
        }
        if (Setting::get('bkash_merchant_number', '')) {
            $paymentMethods[] = 'bkash';
        }
        if (Setting::get('nagad_merchant_number', '')) {
            $paymentMethods[] = 'nagad';
        }
        if (Setting::get('sslcommerz_store_id', '')) {
            $paymentMethods[] = 'sslcommerz';
        }

        // Fallback: always show COD if nothing configured
        if (empty($paymentMethods)) {
            $paymentMethods[] = 'cod';
        }

        $bkashNumber = Setting::get('bkash_merchant_number', '');
        $bkashName = Setting::get('bkash_merchant_name', '');
        $nagadNumber = Setting::get('nagad_merchant_number', '');
        $nagadName = Setting::get('nagad_merchant_name', '');

        $couponSession = session('checkout.coupon');

        $locale = app()->getLocale();

        return view('checkout.index', compact(
            'cartItems', 'subtotal', 'addresses', 'districts',
            'paymentMethods', 'bkashNumber', 'bkashName', 'nagadNumber', 'nagadName',
            'couponSession', 'locale'
        ));
    }

    public function shippingRate(Request $request)
    {
        $request->validate(['district' => 'required|string']);

        $subtotal = $this->cartItems()
            ->with(['product', 'variant'])
            ->get()
            ->sum('line_total');

        $zoneDistrict = ShippingZoneDistrict::with('zone')
            ->where('district_name', $request->district)
            ->whereHas('zone', fn ($q) => $q->where('is_active', true))
            ->first();

        if (! $zoneDistrict) {
            return response()->json(['available' => false, 'rates' => []]);
        }

        $rates = $zoneDistrict->zone->rates()->active()->get()->map(function ($rate) use ($subtotal) {
            $cost = $rate->isFreeFor($subtotal) ? 0 : (float) $rate->cost;

            return [
                'id' => $rate->id,
                'method_name' => $rate->method_name,
                'cost' => $cost,
                'estimated_days_min' => $rate->estimated_days_min,
                'estimated_days_max' => $rate->estimated_days_max,
            ];
        });

        if ($rates->isEmpty()) {
            return response()->json(['available' => false, 'rates' => []]);
        }

        return response()->json(['available' => true, 'rates' => $rates]);
    }

    public function applyCoupon(Request $request)
    {
        $request->validate(['coupon_code' => 'required|string|max:50']);

        $subtotal = $this->cartItems()
            ->with(['product', 'variant'])
            ->get()
            ->sum('line_total');

        $coupon = Coupon::active()->where('code', strtoupper($request->coupon_code))->first();

        if (! $coupon || ! $coupon->isValidFor($subtotal, auth()->id() ?? 0)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => __('front.coupon_invalid')], 422);
            }

            return back()->with('coupon_error', __('front.coupon_invalid'));
        }

        $discount = $coupon->calculateDiscount($subtotal);

        session(['checkout.coupon' => [
            'id' => $coupon->id,
            'code' => $coupon->code,
            'discount' => $discount,
        ]]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'code' => $coupon->code, 'discount' => $discount]);
        }

        return back()->with('coupon_success', __('front.coupon_applied', ['code' => $coupon->code]));
    }

    public function removeCoupon()
    {
        session()->forget('checkout.coupon');

        return back();
    }

    public function placeOrder(Request $request)
    {
        // Normalise the phone up front — it doubles as the customer's account identifier.
        $request->merge([
            'ship_phone' => PhoneNumber::normalize($request->input('ship_phone')) ?? trim((string) $request->input('ship_phone', '')),
            'email' => trim((string) $request->input('email', '')) ?: null,
        ]);

        $rules = [];

        // Email is optional for everyone; it is only used to send order updates.
        if (! auth()->check()) {
            $rules['email'] = ['nullable', 'email:rfc', 'max:191'];
        }

        $rules['ship_name'] = ['required', 'string', 'max:191'];
        $rules['ship_phone'] = ['required', 'string', 'regex:'.PhoneNumber::REGEX];
        $rules['ship_address'] = ['required', 'string'];
        $rules['ship_district'] = ['required', 'string', 'max:100'];
        $rules['notes'] = ['nullable', 'string', 'max:1000'];
        $rules['shipping_rate_id'] = ['nullable', 'exists:shipping_rates,id'];
        $rules['payment_method'] = ['required', 'in:cod,bkash,nagad,sslcommerz'];
        $rules['sender_number'] = ['required_if:payment_method,bkash,nagad', 'excluded_if:payment_method,sslcommerz', 'nullable', 'string', 'max:20'];
        $rules['transaction_id'] = ['required_if:payment_method,bkash,nagad', 'excluded_if:payment_method,sslcommerz', 'nullable', 'string', 'max:100'];
        $rules['screenshot'] = ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'];

        $messages = [
            'ship_phone.regex' => __('front.phone_invalid'),
        ];

        $request->validate($rules, $messages);

        // Auto-register or sign in guest users before order placement — the phone
        // number entered in the delivery address is the account identifier.
        if (! auth()->check()) {
            $sessionId = session()->getId();
            $user = User::where('phone', $request->ship_phone)->first();

            if (! $user) {
                $plainPassword = Str::random(12);

                $user = User::create([
                    'name' => $request->ship_name,
                    'phone' => $request->ship_phone,
                    'email' => $this->claimableEmail($request->email),
                    'password' => Hash::make($plainPassword),
                ]);

                // Credentials can only be delivered when an email was supplied.
                // Without one, they are surfaced once on the confirmation screen.
                if ($user->hasEmail()) {
                    try {
                        Mail::to($user->email)->queue(new NewAccountCredentials($user, $plainPassword));
                    } catch (\Throwable) {
                    }
                } else {
                    session([self::NEW_CREDENTIALS_KEY => [
                        'phone' => $user->phone,
                        'password' => $plainPassword,
                    ]]);
                }
            } elseif (! $user->hasEmail() && $email = $this->claimableEmail($request->email)) {
                // Existing phone-only account supplying an email for the first time.
                $user->update(['email' => $email]);
            }

            auth()->login($user);

            // Migrate guest session cart to the user account
            CartItem::where('session_id', $sessionId)
                ->update(['user_id' => $user->id, 'session_id' => null]);
        }

        $cartItems = auth()->user()->cartItems()
            ->with(['product', 'variant.options'])
            ->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('cart_error', __('front.cart_empty'));
        }

        // Re-validate stock
        foreach ($cartItems as $item) {
            $stock = $item->variant ? $item->variant->stock : $item->product->stock;
            if ($item->quantity > $stock) {
                return back()->with('checkout_error', __('front.insufficient_stock').': '.($item->product->getTranslation('en')?->name ?? $item->product->sku));
            }
        }

        // Calculate totals
        $subtotal = $cartItems->sum('line_total');
        $couponSession = session('checkout.coupon');
        $discountAmount = $couponSession ? (float) $couponSession['discount'] : 0;

        $shippingAmount = 0;
        if ($request->shipping_rate_id) {
            $rate = \App\Models\ShippingRate::find($request->shipping_rate_id);
            if ($rate) {
                $shippingAmount = $rate->isFreeFor($subtotal) ? 0 : (float) $rate->cost;
            }
        }

        $total = max(0, $subtotal - $discountAmount + $shippingAmount);

        $order = DB::transaction(function () use ($request, $cartItems, $subtotal, $discountAmount, $shippingAmount, $total, $couponSession) {

            // 1. Create order
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => auth()->id(),
                'coupon_id' => $couponSession ? $couponSession['id'] : null,
                'shipping_rate_id' => $request->shipping_rate_id,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'shipping_amount' => $shippingAmount,
                'tax_amount' => 0,
                'total_amount' => $total,
                'status' => Order::STATUS_PENDING,
                'payment_status' => Order::PAYMENT_UNPAID,
                'payment_method' => $request->payment_method,
                'ship_name' => $request->ship_name,
                'ship_phone' => $request->ship_phone,
                'ship_address' => $request->ship_address,
                'ship_district' => $request->ship_district,
                'notes' => $request->notes,
            ]);

            // 2. Create order items + decrement stock
            foreach ($cartItems as $item) {
                $unitPrice = $item->variant ? $item->variant->effective_price : $item->product->current_price;
                $productName = $item->product->getTranslation('en')?->name ?? $item->product->sku;
                // Build variant label, substituting the size value with the custom measurement when applicable
                $variantLabel = null;
                if ($item->variant) {
                    $variantLabel = $item->variant->options
                        ->map(function ($opt) use ($item) {
                            if ($item->custom_size && strtolower($opt->option_name) === 'size') {
                                return "{$opt->option_name}: {$item->custom_size}";
                            }

                            return "{$opt->option_name}: {$opt->option_value}";
                        })
                        ->implode(' / ');
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'product_name' => $productName,
                    'variant_label' => $variantLabel,
                    'custom_size' => $item->custom_size ?: null,
                    'unit_price' => $unitPrice,
                    'quantity' => $item->quantity,
                    'subtotal' => $unitPrice * $item->quantity,
                ]);

                // Decrement stock
                if ($item->variant) {
                    $item->variant->decrement('stock', $item->quantity);
                } else {
                    $item->product->decrement('stock', $item->quantity);
                }
            }

            // 3. Coupon usage
            if ($couponSession) {
                CouponUsage::create([
                    'coupon_id' => $couponSession['id'],
                    'user_id' => auth()->id(),
                    'order_id' => $order->id,
                    'discount_amount' => $couponSession['discount'],
                    'created_at' => now(),
                ]);
                Coupon::where('id', $couponSession['id'])->increment('used_count');
            }

            // 4. Payment record
            if ($request->payment_method === 'cod') {
                PaymentTransaction::create([
                    'order_id' => $order->id,
                    'gateway' => 'cod',
                    'amount' => $order->total_amount,
                    'currency' => 'BDT',
                    'status' => 'pending',
                ]);
            } elseif ($request->payment_method === 'sslcommerz') {
                // PaymentTransaction created in PaymentController after gateway init succeeds
            } else {
                // bkash or nagad manual payment
                $screenshotPath = null;
                if ($request->hasFile('screenshot')) {
                    $screenshotPath = $request->file('screenshot')->store('payments', 'public');
                }

                ManualPayment::create([
                    'order_id' => $order->id,
                    'method' => $request->payment_method,
                    'sender_number' => $request->sender_number,
                    'transaction_id' => $request->transaction_id,
                    'amount' => $order->total_amount,
                    'screenshot_path' => $screenshotPath,
                    'status' => ManualPayment::STATUS_PENDING,
                ]);

                // Update payment_status to pending_verification
                $order->update(['payment_status' => Order::PAYMENT_PENDING_VERIFICATION]);
            }

            // 5. Status history
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => Order::STATUS_PENDING,
                'notes' => 'Order placed by customer.',
                'changed_by' => auth()->id(),
                'created_at' => now(),
            ]);

            // 6. Clear cart
            auth()->user()->cartItems()->delete();

            // 7. Clear coupon session
            session()->forget('checkout.coupon');

            return $order;
        });

        // SSLCommerz: redirect to payment gateway (email sent after successful payment)
        if ($request->payment_method === 'sslcommerz') {
            session(['sslcommerz_order_id' => $order->id]);

            return redirect()->route('payment.initiate');
        }

        // Queue the order confirmation email only when we have an address to send it to
        // (non-fatal if the queue is unavailable)
        $order->load(['items', 'user']);

        if ($order->user?->hasEmail()) {
            try {
                Mail::to($order->user->email)->queue(new OrderConfirmation($order));
            } catch (\Throwable) {
                // Non-fatal – order is placed even if email dispatch fails
            }
        }

        return redirect()->route('checkout.confirmation', $order)->with('order_placed', true);
    }

    /**
     * Return the supplied email only when no other account already owns it, so
     * guest checkout can never collide with the users.email unique index.
     */
    private function claimableEmail(?string $email): ?string
    {
        $email = $email ? strtolower(trim($email)) : null;

        if (! $email || User::where('email', $email)->exists()) {
            return null;
        }

        return $email;
    }

    private function cartItems()
    {
        if (auth()->check()) {
            return CartItem::where('user_id', auth()->id());
        }

        return CartItem::where('session_id', session()->getId());
    }

    public function confirmation(Order $order)
    {
        // Security: only the owner can view
        abort_if($order->user_id !== auth()->id(), 403);

        $order->load(['items.product', 'items.variant', 'manualPayment']);

        // Shown once, then gone — the customer has no email to fall back on.
        $newCredentials = session()->pull(self::NEW_CREDENTIALS_KEY);

        return view('checkout.confirmation', compact('order', 'newCredentials'));
    }
}

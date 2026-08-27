<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function index()
    {
        $cartItems = $this->cartQuery()
            ->with(['product.translations', 'product.primaryImage', 'variant.options'])
            ->get();

        $subtotal = $cartItems->sum('line_total');

        return view('cart.index', compact('cartItems', 'subtotal'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id'  => 'required|exists:products,id',
            'variant_id'  => 'nullable|exists:product_variants,id',
            'quantity'    => 'integer|min:1|max:999',
            'custom_size' => 'nullable|string|max:255',
        ]);

        $variantId = $request->integer('variant_id') ?: null;
        $qty = (int) $request->input('quantity', 1);

        if ($variantId) {
            $variant = ProductVariant::findOrFail($variantId);
            $stock = $variant->stock;
        } else {
            $product = Product::findOrFail($request->product_id);

            if ($product->variants()->where('is_active', true)->exists()) {
                return back()->with('cart_error', __('front.select_variant_first'));
            }

            $stock = $product->stock;
        }

        if ($stock <= 0) {
            return back()->with('cart_error', __('front.insufficient_stock'));
        }

        $existing = $this->cartQuery()
            ->where('product_id', $request->product_id)
            ->where('variant_id', $variantId)
            ->first();

        $currentInCart = $existing ? $existing->quantity : 0;
        $newTotal = $currentInCart + $qty;

        if ($newTotal > $stock) {
            $canAdd = $stock - $currentInCart;
            if ($canAdd <= 0) {
                return back()->with('cart_error', __('front.stock_limit_reached'));
            }
            $qty = $canAdd;
        }

        $customSize = $request->input('custom_size') ?: null;

        if ($existing) {
            $existing->increment('quantity', $qty);
            if ($customSize !== null) {
                $existing->update(['custom_size' => $customSize]);
            }
        } else {
            $data = [
                'product_id'  => $request->product_id,
                'variant_id'  => $variantId,
                'quantity'    => $qty,
                'custom_size' => $customSize,
            ];

            if (auth()->check()) {
                $data['user_id'] = auth()->id();
            } else {
                $data['session_id'] = session()->getId();
            }

            CartItem::create($data);
        }

        // Flash pixel AddToCart data for the next page load
        $pixelProduct = $product ?? Product::find($request->product_id);
        if ($pixelProduct) {
            $pixelPrice = ($variantId && isset($variant)) ? $variant->effective_price : ($pixelProduct->current_price ?? 0);
            session()->flash('_pixel_atc', [
                'content_id'   => (string) $request->product_id,
                'content_name' => $pixelProduct->name,
                'value'        => (float) $pixelPrice,
                'quantity'     => $qty,
                // Deduplication id — the same add can never be reported twice.
                'event_id'     => 'atc_'.Str::uuid()->toString(),
            ]);
        }

        if ($request->boolean('buy_now')) {
            return redirect()->route('checkout.index');
        }

        return back()->with('cart_success', __('front.added_to_cart'));
    }

    public function update(Request $request, CartItem $cartItem)
    {
        $this->authorizeItem($cartItem);

        $request->validate(['quantity' => 'required|integer|min:1|max:999']);

        $stock = $cartItem->variant
            ? $cartItem->variant->stock
            : $cartItem->product->stock;

        $cartItem->update(['quantity' => min((int) $request->quantity, $stock)]);

        return back();
    }

    public function destroy(CartItem $cartItem)
    {
        $this->authorizeItem($cartItem);

        $cartItem->delete();

        return back()->with('cart_success', __('front.item_removed'));
    }

    private function cartQuery()
    {
        if (auth()->check()) {
            return CartItem::where('user_id', auth()->id());
        }

        return CartItem::where('session_id', session()->getId());
    }

    private function authorizeItem(CartItem $cartItem): void
    {
        if (auth()->check()) {
            abort_if($cartItem->user_id !== auth()->id(), 403);
        } else {
            abort_if($cartItem->session_id !== session()->getId(), 403);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cartItems = Cart::with('product')
            ->where('user_id', auth()->id())
            ->get();

        return response()->json($cartItems);
    }

    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:1',
        ]);

        // Cek stok
        $product = Product::find($request->product_id);
        if ($product->stocks->sum('qty') < $request->qty) {
            return response()->json(['message' => 'Stok tidak cukup'], 400);
        }

        // Cek apakah produk sudah ada di keranjang
        $existingCart = Cart::where('user_id', auth()->id())
            ->where('product_id', $request->product_id)
            ->first();

        if ($existingCart) {
            $existingCart->update(['qty' => $existingCart->qty + $request->qty]);
        } else {
            Cart::create([
                'user_id' => auth()->id(),
                'product_id' => $request->product_id,
                'qty' => $request->qty,
            ]);
        }

        return response()->json(['message' => 'Produk berhasil ditambahkan ke keranjang']);
    }

    public function updateCart(Request $request, $id)
    {
        $request->validate([
            'qty' => 'required|integer|min:1',
        ]);

        $cart = Cart::where('user_id', auth()->id())->findOrFail($id);

        // Cek stok
        $product = Product::find($cart->product_id);
        if ($product->stocks->sum('qty') < $request->qty) {
            return response()->json(['message' => 'Stok tidak cukup'], 400);
        }

        $cart->update(['qty' => $request->qty]);

        return response()->json(['message' => 'Keranjang berhasil diupdate']);
    }

    public function removeFromCart($id)
    {
        $cart = Cart::where('user_id', auth()->id())->findOrFail($id);
        $cart->delete();

        return response()->json(['message' => 'Produk berhasil dihapus dari keranjang']);
    }
}

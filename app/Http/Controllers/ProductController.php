<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|min:10|max:15',
            'unique_code' => 'required|unique:products',
            'type' => 'required|in:1,2,3',
            'production_date' => 'required|date',
            'expiration_duration' => 'required',
        ]);

        try {
            $product = new Product();
            $product->name = $validatedData['name'];
            $product->unique_code = $validatedData['unique_code'];
            $product->quantity = $request->input('quantity', 0);
            $product->type = $validatedData['type'];
            $product->production_date = $request->input('production_date');
            $product->expiration_duration = $request->input('expiration_duration');
            $product->user_id = $request->user()->id; // Assuming you're using authentication

            $product->save();

            return response()->json(['message' => 'Product added successfully'], 201);
        } catch (QueryException $e) {
            return response()->json(['message' => 'Error saving product to the database'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'quantity' => 'nullable|integer|min:0',
            'type' => 'sometimes|in:1,2,3',
            'production_date' => 'sometimes|date',
            'expiration_duration' => 'sometimes',
        ]);

        try {
            $product = Product::findOrFail($id);

            // Update only the allowed columns
            $product->quantity = $validatedData['quantity'] ?? $product->quantity;
            $product->type = $validatedData['type'] ?? $product->type;
            $product->production_date = $validatedData['production_date'] ?? $product->production_date;
            $product->expiration_duration = $validatedData['expiration_duration'] ?? $product->expiration_duration;

            $product->save();

            return response()->json(['message' => 'Product updated successfully'], 200);
        } catch (QueryException $e) {
            return response()->json(['message' => 'Error updating product in the database'], 500);
        }
    }

}

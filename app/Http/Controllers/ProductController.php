<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * add new product
     *
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * update existing product
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
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

    /**
     * check expiration date
     *
     * @param $unique_code
     * @return JsonResponse
     */
    public function checkExpiration($unique_code)
    {
        try {
            $product = Product::where('unique_code', $unique_code)->firstOrFail();

            $expiration_in_days = $this->convertExpirationToDays($product->expiration_duration);

            $production_date = Carbon::parse($product->production_date);

            $expiration_date = $production_date->addDays($expiration_in_days);
            $is_expired = Carbon::now()->greaterThan($expiration_date);

            return response()->json([
                'expiration_date' => $expiration_date->format('Y-m-d'),
                'is_expired' => $is_expired,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Product not found'], 404);
        }
    }

    /**
     * convert expiration duration to days
     *
     * @param $expiration_duration
     * @return float|int
     */
    private function convertExpirationToDays($expiration_duration)
    {
        $parts = preg_split('/\s*(\d+)\s*/', $expiration_duration, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (!empty($parts) && count($parts) >= 3) {
            $coefficient = intval($parts[1]);
            $stringPart = trim($parts[2]);
        } else {
            return 0;
        }

        $days = match ($stringPart) {
            'წელი' => 365,
            'თვე' => 30,
            'დღე' => 1,
            default => 0,
        };

        return $coefficient*$days;
    }

    /**
     * check product type and qty
     *
     * @param $unique_code
     * @return JsonResponse
     */
    public function checkProduct($unique_code)
    {
        try {
            $product = Product::where('unique_code', $unique_code)->firstOrFail();
            $type = match ($product->type) {
                1 => 'სურსათი',
                2 => 'სარეცხი საშუალებები',
                3 => 'ხორც-პროდუქტები',
            };

            return response()->json([
                'in_stock' => $product->quantity > 0,
                'quantity' => $product->quantity,
                'type' => $type,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Product not found'], 404);
        }
    }
}


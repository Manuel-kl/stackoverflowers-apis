<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TaxStatusEnum;
use App\Enums\TaxTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\PaymentTaxes;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TaxController extends Controller
{
    public function index()
    {
        $taxes = PaymentTaxes::all();

        return view('admin.taxes.index', compact('taxes'));
    }

    public function create()
    {
        return view('admin.taxes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:'.implode(',', array_column(TaxStatusEnum::cases(), 'value')),
            'type' => 'required|in:'.implode(',', array_column(TaxTypeEnum::cases(), 'value')),
            'value' => 'required|numeric|min:0',
        ]);

        $slug = Str::slug($request->name);

        if (PaymentTaxes::where('slug', $slug)->exists()) {
            return redirect()->back()->withErrors(['name' => 'A tax with this name already exists.'])->withInput();
        }

        PaymentTaxes::create([
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'status' => $request->status,
            'type' => $request->type,
            'value' => $request->value,
        ]);

        return redirect()->route('admin.taxes.index')->with('success', 'Tax created successfully.');
    }

    public function edit(PaymentTaxes $tax)
    {
        return view('admin.taxes.edit', compact('tax'));
    }

    public function update(Request $request, PaymentTaxes $tax)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:'.implode(',', array_column(TaxStatusEnum::cases(), 'value')),
            'type' => 'required|in:'.implode(',', array_column(TaxTypeEnum::cases(), 'value')),
            'value' => 'required|numeric|min:0',
        ]);

        $updateData = [
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status,
            'type' => $request->type,
            'value' => $request->value,
        ];

        if ($tax->name !== $request->name) {
            $newSlug = Str::slug($request->name);

            if (PaymentTaxes::where('slug', $newSlug)->where('id', '!=', $tax->id)->exists()) {
                return redirect()->back()->withErrors(['name' => 'A tax with this name already exists.'])->withInput();
            }

            $updateData['slug'] = $newSlug;
        }

        $tax->update($updateData);

        return redirect()->route('admin.taxes.index')->with('success', 'Tax updated successfully.');
    }

    public function destroy(PaymentTaxes $tax)
    {
        $tax->delete();

        return redirect()->route('admin.taxes.index')->with('success', 'Tax deleted successfully.');
    }
}

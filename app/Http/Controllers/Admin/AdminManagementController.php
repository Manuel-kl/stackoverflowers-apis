<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminStatusEnum;
use App\Enums\AdminTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;

class AdminManagementController extends Controller
{
    public function index()
    {
        $admins = Admin::all();

        return view('admin.admins.index', compact('admins'));
    }

    public function create()
    {
        return view('admin.admins.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|string|min:6',
            'admin_type' => 'required|in:'.implode(',', array_column(AdminTypeEnum::cases(), 'value')),
        ]);

        Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'admin_type' => $request->admin_type,
            'status' => AdminStatusEnum::ACTIVE,
        ]);

        return redirect()->route('admin.admins.index')->with('success', 'Admin created successfully.');
    }

    public function edit(Admin $admin)
    {
        return view('admin.admins.edit', compact('admin'));
    }

    public function update(Request $request, Admin $admin)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email,'.$admin->id,
            'admin_type' => 'required|in:'.implode(',', array_column(AdminTypeEnum::cases(), 'value')),
            'status' => 'required|in:'.implode(',', array_column(AdminStatusEnum::cases(), 'value')),
        ]);

        $admin->update([
            'name' => $request->name,
            'email' => $request->email,
            'admin_type' => $request->admin_type,
            'status' => $request->status,
        ]);

        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:6']);
            $admin->update(['password' => bcrypt($request->password)]);
        }

        return redirect()->route('admin.admins.index')->with('success', 'Admin updated successfully.');
    }

    public function destroy(Admin $admin)
    {
        if ($admin->id === auth()->guard('admin')->id()) {
            return redirect()->route('admin.admins.index')->with('error', 'You cannot delete your own account.');
        }

        $admin->delete();

        return redirect()->route('admin.admins.index')->with('success', 'Admin deleted successfully.');
    }
}

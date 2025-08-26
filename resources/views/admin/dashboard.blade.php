<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 mb-8 flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-800">Admin Dashboard</h1>
            <form method="POST" action="{{ route('admin.logout') }}" class="inline">
                @csrf
                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                    Logout
                </button>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow-md p-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-2">Welcome, {{ Auth::guard('admin')->user()->name }}!</h2>
            <p class="text-gray-600 mb-8">You are logged in as an administrator.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @if(Auth::guard('admin')->user()->admin_type->value === 'super_admin')
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Manage Admins</h3>
                    <p class="text-gray-600 mb-4">Create, edit, and manage admin accounts</p>
                    <a href="{{ route('admin.admins.index') }}" class="inline-block bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        Manage Admins
                    </a>
                </div>
                @endif

                <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Manage Taxes</h3>
                    <p class="text-gray-600 mb-4">Create, edit, and manage tax configurations</p>
                    <a href="{{ route('admin.taxes.index') }}" class="inline-block bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        Manage Taxes
                    </a>
                </div>

                <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Manage Orders</h3>
                    <p class="text-gray-600 mb-4">View and manage customer orders</p>
                    <a href="{{ route('admin.orders.index') }}" class="inline-block bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        Manage Orders
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users | Anita MUA CRM</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        rose: {
                            primary: '#d4739a',
                            light: '#f5c6d6',
                            dark: '#b85a7f',
                            50: '#fdf2f8',
                            100: '#fce7f3',
                            200: '#fbcfe8'
                        },
                        cream: '#faf6f2'
                    },
                    fontFamily: {
                        heading: ['Playfair Display', 'serif'],
                        body: ['Plus Jakarta Sans', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #faf6f2; }
        h1, h2, h3, h4, h5, h6 { font-family: 'Playfair Display', serif; }
    </style>
</head>
<body class="bg-cream min-h-screen">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 font-heading">Users</h1>
            <p class="text-gray-500 mt-1">Manage team members and roles</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-rose-100">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 font-heading">Add User</h3>
                </div>
                <form action="{{ route('admin.users.store') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Name</label>
                        <input type="text" name="name" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-rose-primary focus:border-transparent bg-gray-50" placeholder="Full name">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Email</label>
                        <input type="email" name="email" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-rose-primary focus:border-transparent bg-gray-50" placeholder="email@example.com">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Phone</label>
                        <input type="text" name="phone" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-rose-primary focus:border-transparent bg-gray-50" placeholder="08xxxxxxxxxx">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Role</label>
                        <select name="role" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-rose-primary focus:border-transparent bg-gray-50">
                            <option value="admin">Admin</option>
                            <option value="mua">MUA</option>
                            <option value="coordinator">Coordinator</option>
                            <option value="viewer">Viewer</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Password</label>
                        <input type="password" name="password" required minlength="8" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-rose-primary focus:border-transparent bg-gray-50" placeholder="Min 8 characters">
                    </div>
                    <button type="submit" class="w-full bg-rose-primary hover:bg-rose-dark text-white py-2.5 rounded-lg text-sm font-semibold transition-all">
                        Add User
                    </button>
                </form>
            </div>

            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-rose-100">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 font-heading">Team Members</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-rose-50">
                                <th class="text-left px-5 py-3 font-semibold text-gray-600">User</th>
                                <th class="text-left px-5 py-3 font-semibold text-gray-600">Phone</th>
                                <th class="text-left px-5 py-3 font-semibold text-gray-600">Role</th>
                                <th class="text-center px-5 py-3 font-semibold text-gray-600">Bookings</th>
                                <th class="text-left px-5 py-3 font-semibold text-gray-600">Status</th>
                                <th class="text-center px-5 py-3 font-semibold text-gray-600">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                            <tr class="border-t border-gray-50 hover:bg-rose-50/30 transition-colors">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-rose-primary to-rose-dark flex items-center justify-center text-white text-sm font-bold">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-800">{{ $user->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-gray-600">{{ $user->phone ?? '-' }}</td>
                                <td class="px-5 py-4">
                                    @php
                                        $roleColors = [
                                            'admin' => 'bg-purple-100 text-purple-700',
                                            'mua' => 'bg-rose-100 text-rose-700',
                                            'coordinator' => 'bg-blue-100 text-blue-700',
                                            'viewer' => 'bg-gray-100 text-gray-600'
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $roleColors[$user->role] ?? 'bg-gray-100 text-gray-600' }}">
                                        {{ ucfirst($user->role) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-rose-50 text-rose-primary text-sm font-bold">
                                        {{ $user->bookings_count ?? 0 }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <form action="{{ route('admin.users.toggle', $user->id) }}" method="POST" class="inline-block">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all {{ $user->is_active ? 'bg-red-50 text-red-600 hover:bg-red-100' : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100' }}">
                                            {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-5 py-12 text-center">
                                    <div class="text-gray-400">
                                        <svg class="w-12 h-12 mx-auto mb-3 text-rose-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                        <p class="font-medium">No users found</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

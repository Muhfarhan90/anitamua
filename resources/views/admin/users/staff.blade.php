@extends('layouts.app')

@section('title', 'Manajemen Staff')

@section('content')
<x-page-header title="Manajemen Staff" subtitle="Kelola akun Owner, Admin, dan Tim Lapangan">
    <x-slot:actions>
        <x-button href="{{ route('admin.users.clients') }}" color="ghost"><i class="fas fa-users"></i> Manajemen Klien</x-button>
        <x-button color="primary" onclick="document.getElementById('addModal').classList.remove('hidden')"><i class="fas fa-plus"></i> Tambah Staff</x-button>
    </x-slot:actions>
</x-page-header>

<x-card padding="p-0">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b border-gray-100 bg-cream/60">
                    <th class="px-5 py-2.5 font-medium">Staff</th>
                    <th class="px-5 py-2.5 font-medium">Role</th>
                    <th class="px-5 py-2.5 font-medium">No HP</th>
                    <th class="px-5 py-2.5 font-medium">Status</th>
                    <th class="px-5 py-2.5 font-medium text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr class="border-b border-gray-50 hover:bg-brand-50/30 transition-colors">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-brand to-brand-dark text-white flex items-center justify-center font-bold text-xs flex-shrink-0">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800">{{ $user->name }}</p>
                                <p class="text-xs text-gray-400">{{ $user->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3">
                        <x-badge :color="match($user->role) {
                            'owner' => 'danger',
                            'admin' => 'info',
                            'team' => 'warning',
                            default => 'gray',
                        }">{{ ucfirst($user->role) }}</x-badge>
                    </td>
                    <td class="px-5 py-3 text-gray-600">{{ $user->phone ?? '-' }}</td>
                    <td class="px-5 py-3">
                        <x-badge :color="$user->is_active ? 'success' : 'gray'">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-center gap-2">
                            <x-button size="sm" color="outline" href="{{ route('admin.users.edit', $user->id) }}"><i class="fas fa-pen"></i></x-button>
                            <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}" onsubmit="return confirm('Hapus user {{ $user->name }}?')">
                                @csrf @method('DELETE')
                                <x-button size="sm" color="danger" type="submit"><i class="fas fa-trash"></i></x-button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5"><x-empty-state icon="fa-user-tie" title="Belum ada staff" /></td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>

@if($users->hasPages())
<div class="mt-4">{{ $users->links() }}</div>
@endif

{{-- MODAL TAMBAH STAFF --}}
<div id="addModal" class="hidden fixed inset-0 z-[1100] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="document.getElementById('addModal').classList.add('hidden')"></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-display font-bold text-gray-800"><i class="fas fa-user-plus text-brand mr-2"></i>Tambah Staff</h3>
            <button type="button" class="text-gray-400 hover:text-gray-600" onclick="document.getElementById('addModal').classList.add('hidden')">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <form action="{{ route('admin.users.staff.store') }}" method="POST" class="space-y-3">
            @csrf
            <x-input name="name" label="Nama Lengkap" required placeholder="cth: Rina Anggraini" />
            <x-input name="email" label="Email" type="email" required placeholder="email@example.com" />
            <x-input name="phone" label="No HP" placeholder="08xxxxxxxxxx" />
            <x-input name="password" label="Password" type="password" required minlength="6" />
            <div>
                <label for="role" class="block text-sm font-medium text-gray-600 mb-1">Role <span class="text-red-500">*</span></label>
                <select name="role" id="role" required
                        class="w-full rounded-lg border bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-brand-200 border-gray-200">
                    <option value="admin">Admin</option>
                    <option value="team">Tim Lapangan</option>
                    <option value="owner">Owner</option>
                </select>
            </div>
            <x-button color="primary" type="submit" class="w-full"><i class="fas fa-save"></i> Simpan</x-button>
        </form>
    </div>
</div>
@endsection

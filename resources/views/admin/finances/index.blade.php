<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finances | Anita MUA CRM</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 font-heading">Finances</h1>
                <p class="text-gray-500 mt-1">Financial overview and reporting</p>
            </div>
            <div class="flex items-center gap-3">
                <select onchange="window.location.search='year='+this.value" class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-rose-primary">
                    @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                    <option value="{{ $y }}" {{ request('year', date('Y')) == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
                <button onclick="openModal()" class="bg-rose-primary hover:bg-rose-dark text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-sm">
                    + Add Transaction
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="text-sm text-gray-500">Pendapatan</p>
                </div>
                <p class="text-2xl font-bold text-emerald-600">Rp {{ number_format($pendapatan ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <p class="text-sm text-gray-500">Pengeluaran</p>
                </div>
                <p class="text-2xl font-bold text-red-600">Rp {{ number_format($pengeluaran ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-rose-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-rose-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    </div>
                    <p class="text-sm text-gray-500">Profit</p>
                </div>
                <p class="text-2xl font-bold text-rose-primary">Rp {{ number_format($profit ?? 0, 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-5">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <p class="text-sm text-gray-500">Margin</p>
                </div>
                <p class="text-2xl font-bold text-purple-600">{{ $margin ?? 0 }}%</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-rose-100">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 font-heading">Profit per Project</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-rose-50">
                                <th class="text-left px-5 py-3 font-semibold text-gray-600">Project</th>
                                <th class="text-left px-5 py-3 font-semibold text-gray-600">Revenue</th>
                                <th class="text-left px-5 py-3 font-semibold text-gray-600">Cost</th>
                                <th class="text-left px-5 py-3 font-semibold text-gray-600">Profit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($perProject as $row)
                            <tr class="border-t border-gray-50 hover:bg-rose-50/30">
                                <td class="px-5 py-3">
                                    <div class="font-medium text-gray-800">{{ $row['project']->code }}</div>
                                    <div class="text-xs text-gray-500">{{ $row['project']->name }}</div>
                                </td>
                                <td class="px-5 py-3 text-emerald-600 font-medium">Rp {{ number_format($row['income'], 0, ',', '.') }}</td>
                                <td class="px-5 py-3 text-red-600 font-medium">Rp {{ number_format($row['expense'], 0, ',', '.') }}</td>
                                <td class="px-5 py-3 font-bold {{ $row['profit'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">Rp {{ number_format($row['profit'], 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-5 py-8 text-center text-gray-400">No project data for this year</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-rose-100">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 font-heading">Cashflow</h3>
                </div>
                <div class="p-4">
                    <canvas id="cashflowChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-rose-100">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-800 font-heading">Transactions</h3>
                <div class="flex gap-2">
                    <a href="?type=" class="px-3 py-1 rounded-full text-xs font-medium {{ !request('type') ? 'bg-rose-primary text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">All</a>
                    <a href="?type=income" class="px-3 py-1 rounded-full text-xs font-medium {{ request('type') == 'income' ? 'bg-emerald-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">Income</a>
                    <a href="?type=expense" class="px-3 py-1 rounded-full text-xs font-medium {{ request('type') == 'expense' ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">Expense</a>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-rose-50">
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Date</th>
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Description</th>
                            <th class="text-left px-5 py-3 font-semibold text-gray-600">Category</th>
                            <th class="text-right px-5 py-3 font-semibold text-gray-600">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                        <tr class="border-t border-gray-50 hover:bg-rose-50/30">
                            <td class="px-5 py-3 text-gray-600">{{ \Carbon\Carbon::parse($transaction->date)->format('d M Y') }}</td>
                            <td class="px-5 py-3 font-medium text-gray-800">{{ $transaction->description }}</td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs {{ $transaction->type === 'income' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $transaction->category }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right font-semibold {{ $transaction->type === 'income' ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ $transaction->type === 'income' ? '+' : '-' }} Rp {{ number_format($transaction->amount, 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-5 py-8 text-center text-gray-400">No transactions found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="addModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-800 font-heading">Add Transaction</h3>
                <button onclick="closeModal()" class="p-1 hover:bg-gray-100 rounded-lg">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form action="{{ route('admin.finances.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Type</label>
                        <select name="type" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-rose-primary bg-gray-50">
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Date</label>
                        <input type="date" name="date" value="{{ now()->format('Y-m-d') }}" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-rose-primary bg-gray-50">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Description</label>
                    <input type="text" name="description" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-rose-primary bg-gray-50" placeholder="Transaction description">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Category</label>
                        <input type="text" name="category" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-rose-primary bg-gray-50" placeholder="e.g. Makeup, Transport">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Amount (Rp)</label>
                        <input type="text" name="amount" inputmode="numeric" data-money-input pattern="[0-9.]*" required min="0" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-rose-primary bg-gray-50" placeholder="0">
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">Cancel</button>
                    <button type="submit" class="bg-rose-primary hover:bg-rose-dark text-white px-5 py-2 rounded-lg text-sm font-medium transition-all">Save Transaction</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() { document.getElementById('addModal').classList.remove('hidden'); }
        function closeModal() { document.getElementById('addModal').classList.add('hidden'); }

        const ctx = document.getElementById('cashflowChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($cashflowLabels ?? []),
                    datasets: [
                        {
                            label: 'Income',
                            data: @json($cashflowIncome ?? []),
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16,185,129,0.1)',
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Expense',
                            data: @json($cashflowExpense ?? []),
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239,68,68,0.1)',
                            fill: true,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 15 } } },
                    scales: { y: { beginAtZero: true, grid: { color: '#f3f4f6' } }, x: { grid: { display: false } } }
                }
            });
        }
    </script>
</body>
</html>

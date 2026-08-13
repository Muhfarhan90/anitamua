<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timeline | Anita MUA CRM</title>
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
    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 font-heading">Timeline</h1>
            <p class="text-gray-500 mt-1">Activity history across all projects</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-rose-100 mb-6">
            <div class="px-5 py-4 border-b border-gray-100">
                <form method="GET" class="flex items-end gap-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-600 mb-1">Filter by Project</label>
                        <select name="booking_id" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-rose-primary focus:border-transparent bg-gray-50">
                            <option value="">All Projects</option>
                            @foreach($bookings as $booking)
                            <option value="{{ $booking->id }}" {{ request('booking_id') == $booking->id ? 'selected' : '' }}>
                                {{ $booking->code }} - {{ $booking->client_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="bg-rose-primary hover:bg-rose-dark text-white px-5 py-2 rounded-lg text-sm font-medium transition-all">
                        Filter
                    </button>
                </form>
            </div>
        </div>

        <div class="relative">
            <div class="absolute left-6 top-0 bottom-0 w-0.5 bg-rose-200"></div>

            <div class="space-y-6">
                @php $timelineEntries = $logs; @endphp
                @forelse($timelineEntries as $entry)
                <div class="relative pl-14">
                    <div class="absolute left-4 top-4 w-5 h-5 rounded-full bg-rose-primary border-4 border-cream z-10"></div>
                    <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-5 hover:shadow-md transition-shadow">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <h4 class="font-bold text-gray-800">{{ $entry->title }}</h4>
                                @if($entry->booking)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-dark">
                                    {{ $entry->booking->code }}
                                </span>
                                @endif
                            </div>
                            <span class="text-xs text-gray-400 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($entry->created_at)->format('d M Y') }}
                            </span>
                        </div>
                        <p class="text-gray-600 text-sm mb-3">{{ $entry->description }}</p>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 text-xs text-gray-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ \Carbon\Carbon::parse($entry->created_at)->format('H:i') }}
                            </div>
                            @if($entry->user)
                            <div class="flex items-center gap-1.5">
                                <div class="w-5 h-5 rounded-full bg-gradient-to-br from-rose-primary to-rose-dark flex items-center justify-center text-white text-[10px] font-bold">
                                    {{ strtoupper(substr($entry->user->name, 0, 1)) }}
                                </div>
                                <span class="text-xs text-gray-500">{{ $entry->user->name }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @empty
                <div class="relative pl-14">
                    <div class="absolute left-4 top-4 w-5 h-5 rounded-full bg-gray-300 border-4 border-cream z-10"></div>
                    <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-8 text-center">
                        <svg class="w-12 h-12 mx-auto mb-3 text-rose-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="font-medium text-gray-500">No timeline entries found</p>
                        <p class="text-sm text-gray-400 mt-1">Activities will appear here as they happen</p>
                    </div>
                </div>
                @endforelse
            </div>
        </div>

        @if(method_exists($timelineEntries, 'links'))
        <div class="mt-6">
            {{ $timelineEntries->links() }}
        </div>
        @endif
    </div>
</body>
</html>

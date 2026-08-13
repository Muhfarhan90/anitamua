<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reminders | Anita MUA CRM</title>
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
            <h1 class="text-3xl font-bold text-gray-800 font-heading">Reminders</h1>
            <p class="text-gray-500 mt-1">Manage automated reminders and notifications</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-2xl shadow-sm border border-rose-100">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 font-heading">Add Reminder</h3>
                </div>
                <form action="{{ route('admin.reminders.store') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Project</label>
                        <select name="booking_id" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-rose-primary focus:border-transparent bg-gray-50">
                            <option value="">Select project</option>
                            @foreach($bookings as $booking)
                            <option value="{{ $booking->id }}">{{ $booking->code }} - {{ $booking->client_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Title</label>
                        <input type="text" name="title" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-rose-primary focus:border-transparent bg-gray-50" placeholder="Reminder title">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Date & Time</label>
                        <input type="datetime-local" name="scheduled_at" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-rose-primary focus:border-transparent bg-gray-50">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Audience</label>
                        <select name="audience" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-rose-primary focus:border-transparent bg-gray-50">
                            <option value="client">Client</option>
                            <option value="team">Team</option>
                            <option value="all">All</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Channel</label>
                        <select name="channel" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-rose-primary focus:border-transparent bg-gray-50">
                            <option value="whatsapp">WhatsApp</option>
                            <option value="email">Email</option>
                            <option value="sms">SMS</option>
                            <option value="notification">In-App</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Message</label>
                        <textarea name="message" rows="3" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-rose-primary focus:border-transparent bg-gray-50 resize-none" placeholder="Reminder message..."></textarea>
                    </div>
                    <button type="submit" class="w-full bg-rose-primary hover:bg-rose-dark text-white py-2.5 rounded-lg text-sm font-semibold transition-all">
                        Create Reminder
                    </button>
                </form>
            </div>

            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-rose-100">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 font-heading">All Reminders</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-rose-50">
                                <th class="text-left px-5 py-3 font-semibold text-gray-600">Project</th>
                                <th class="text-left px-5 py-3 font-semibold text-gray-600">Title</th>
                                <th class="text-left px-5 py-3 font-semibold text-gray-600">Jadwal</th>
                                <th class="text-left px-5 py-3 font-semibold text-gray-600">Audience</th>
                                <th class="text-left px-5 py-3 font-semibold text-gray-600">Channel</th>
                                <th class="text-left px-5 py-3 font-semibold text-gray-600">Status</th>
                                <th class="text-center px-5 py-3 font-semibold text-gray-600">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reminders as $reminder)
                            <tr class="border-t border-gray-50 hover:bg-rose-50/30 transition-colors">
                                <td class="px-5 py-4">
                                    @if($reminder->booking)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-dark">
                                        {{ $reminder->booking->code }}
                                    </span>
                                    @else
                                    <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 font-medium text-gray-800">{{ $reminder->title }}</td>
                                <td class="px-5 py-4 text-gray-600">
                                    {{ \Carbon\Carbon::parse($reminder->scheduled_at)->format('d M Y H:i') }}
                                </td>
                                <td class="px-5 py-4">
                                    @php
                                        $audienceColors = [
                                            'client' => 'bg-blue-100 text-blue-700',
                                            'team' => 'bg-purple-100 text-purple-700',
                                            'all' => 'bg-gray-100 text-gray-600'
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $audienceColors[$reminder->audience] ?? 'bg-gray-100 text-gray-600' }}">
                                        {{ ucfirst($reminder->audience) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    @php
                                        $channelIcons = [
                                            'whatsapp' => 'bg-green-100 text-green-700',
                                            'email' => 'bg-blue-100 text-blue-700',
                                            'sms' => 'bg-orange-100 text-orange-700',
                                            'notification' => 'bg-rose-100 text-rose-700'
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $channelIcons[$reminder->channel] ?? 'bg-gray-100 text-gray-600' }}">
                                        {{ ucfirst($reminder->channel) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-yellow-100 text-yellow-700',
                                            'sent' => 'bg-emerald-100 text-emerald-700',
                                            'failed' => 'bg-red-100 text-red-700',
                                            'cancelled' => 'bg-gray-100 text-gray-500'
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$reminder->status] ?? 'bg-gray-100 text-gray-600' }}">
                                        {{ ucfirst($reminder->status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    @if($reminder->status === 'pending')
                                    <form action="{{ route('admin.reminders.destroy', $reminder->id) }}" method="POST" class="inline-block">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-red-50 text-red-600 hover:bg-red-100 transition-all">
                                            Cancel
                                        </button>
                                    </form>
                                    @else
                                    <span class="text-gray-400 text-xs">-</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center">
                                    <div class="text-gray-400">
                                        <svg class="w-12 h-12 mx-auto mb-3 text-rose-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                        <p class="font-medium">No reminders found</p>
                                        <p class="text-sm mt-1">Create your first reminder using the form</p>
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

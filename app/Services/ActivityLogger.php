<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public static function log(
        string $action,
        string $title,
        ?string $description = null,
        ?int $bookingId = null,
        array|object|null $oldData = null,
        array|object|null $newData = null,
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => Auth::id(),
            'booking_id' => $bookingId,
            'action' => $action,
            'title' => $title,
            'description' => $description,
            'old_data' => $oldData ? (array) $oldData : null,
            'new_data' => $newData ? (array) $newData : null,
        ]);
    }
}

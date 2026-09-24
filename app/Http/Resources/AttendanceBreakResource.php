<?php

namespace App\Http\Resources;

use App\Models\BreakTime;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BreakTime
 */
class AttendanceBreakResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'break_in' => $this->break_start_time?->format('H:i:s'),
            'break_out' => $this->break_end_time?->format('H:i:s'),
        ];
    }
}

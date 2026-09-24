<?php

namespace App\Http\Resources;

use App\Models\AttendanceCorrectRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AttendanceCorrectRequest
 */
class ApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'new_date' => $this->new_date?->format('Y-m-d'),
            'new_clock_in' => $this->new_clock_in,
            'new_clock_out' => $this->new_clock_out,
            'comment' => $this->comment,
            'approval_status' => $this->approval_status,
        ];
    }
}

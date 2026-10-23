<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class ApplicationResource extends JsonResource
{
    /**
     * 修正申請情報をAPIレスポンス用に整形する。
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'attendance_record_id' => $this->attendance_record_id,
            'new_clock_in' => $this->new_clock_in
                ? Carbon::parse($this->new_clock_in)->format('H:i:s')
                : null,
            'new_clock_out' => $this->new_clock_out
                ? Carbon::parse($this->new_clock_out)->format('H:i:s')
                : null,
            'comment' => $this->comment,
            'approval_status' => $this->approval_status,
            'application_date' => $this->application_date?->toISOString(),

            'application_breaks' => ApplicationBreakResource::collection(
                $this->whenLoaded('applicationBreaks')
            ),
        ];
    }
}

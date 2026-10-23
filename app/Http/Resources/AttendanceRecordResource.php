<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
{
    /**
     * 勤怠情報をAPIレスポンス用に整形する。
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'date' => $this->date?->format('Y-m-d'),
            'clock_in' => $this->clock_in?->format('H:i:s'),
            'clock_out' => $this->clock_out?->format('H:i:s'),
            'total_time' => $this->formatMinutes(
                $this->getTotalWorkMinutes()
            ),
            'total_break_time' => $this->formatMinutes(
                $this->getTotalBreakMinutes()
            ),
            'comment' => $this->comment,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            'user_name' => $this->when(
                $request->routeIs('attendance-records.index')
                    && $this->relationLoaded('user'),
                fn () => $this->user->name
            ),

            'user' => $this->when(
                $request->routeIs('attendance-records.show')
                    && $this->relationLoaded('user'),
                fn () => [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ]
            ),

            'breaks' => AttendanceBreakResource::collection(
                $this->whenLoaded('breaks')
            ),

            'applications' => ApplicationResource::collection(
                $this->whenLoaded('applications')
            ),
        ];
    }

    /**
     * 分単位の時間を HH:MM 形式に整形する。
     */
    private function formatMinutes(int $minutes): string
    {
        return sprintf(
            '%02d:%02d',
            intdiv($minutes, 60),
            $minutes % 60
        );
    }
}

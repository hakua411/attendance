<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class ApplicationBreakResource extends JsonResource
{
    /**
     * 休憩修正申請をAPIレスポンス用に整形する。
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'break_in' => $this->break_in
                ? Carbon::parse($this->break_in)->format('H:i:s')
                : null,
            'break_out' => $this->break_out
                ? Carbon::parse($this->break_out)->format('H:i:s')
                : null,
        ];
    }
}

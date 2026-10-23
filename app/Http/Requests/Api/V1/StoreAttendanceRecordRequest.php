<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceRecordRequest extends FormRequest
{
    /**
     * リクエストを許可する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルールを定義する。
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'clock_in' => ['required', 'date_format:H:i:s'],
            'clock_out' => [
                'nullable',
                'date_format:H:i:s',
                'after:clock_in',
            ],
            'comment' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * 日本語のバリデーションメッセージ。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.required' => '勤怠日は必須です。',
            'date.date_format' => '勤怠日は YYYY-MM-DD 形式で指定してください。',

            'clock_in.required' => '出勤時刻は必須です。',
            'clock_in.date_format' => '出勤時刻は HH:MM:SS 形式で指定してください。',

            'clock_out.date_format' => '退勤時刻は HH:MM:SS 形式で指定してください。',
            'clock_out.after' => '退勤時刻は出勤時刻より後の時刻を指定してください。',

            'comment.string' => '備考は文字列で入力してください。',
            'comment.max' => '備考は 255 文字以内で入力してください。',
        ];
    }
}

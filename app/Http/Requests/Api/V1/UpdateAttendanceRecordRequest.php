<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Attendance;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAttendanceRecordRequest extends FormRequest
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
        $attendance = Attendance::find(
            $this->route('attendanceRecord')
        );

        return [
            'date' => [
                'sometimes',
                'required',
                'date_format:Y-m-d',
                Rule::unique('attendance_records', 'date')
                    ->where('user_id', $attendance?->user_id)
                    ->ignore($attendance?->id),
            ],
            'clock_in' => [
                'sometimes',
                'required',
                'date_format:H:i:s',
            ],
            'clock_out' => [
                'sometimes',
                'nullable',
                'date_format:H:i:s',
            ],
            'comment' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * バリデーション後に、更新後の出勤・退勤時刻を比較する。
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('clock_in')
                || $validator->errors()->has('clock_out')) {
                return;
            }

            $attendance = Attendance::find(
                $this->route('attendanceRecord')
            );

            if ($attendance === null) {
                return;
            }

            $clockIn = $this->input(
                'clock_in',
                $attendance->clock_in?->format('H:i:s')
            );

            $clockOut = $this->exists('clock_out')
                ? $this->input('clock_out')
                : $attendance->clock_out?->format('H:i:s');

            if ($clockIn !== null
                && $clockOut !== null
                && $clockOut <= $clockIn) {
                $validator->errors()->add(
                    'clock_out',
                    '退勤時刻は出勤時刻より後の時刻を指定してください。'
                );
            }
        });
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
            'date.unique' => 'この日付の勤怠は既に登録されています。',

            'clock_in.required' => '出勤時刻は必須です。',
            'clock_in.date_format' => '出勤時刻は HH:MM:SS 形式で指定してください。',

            'clock_out.date_format' => '退勤時刻は HH:MM:SS 形式で指定してください。',
            'clock_out.after' => '退勤時刻は出勤時刻より後の時刻を指定してください。',

            'comment.string' => '備考は文字列で入力してください。',
            'comment.max' => '備考は 255 文字以内で入力してください。',
        ];
    }
}

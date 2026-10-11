<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CorrectionRequest extends FormRequest
{
    /**
     * このリクエストを実行する権限があるか判定する。
     *
     * @return bool 実行を許可する場合はtrue
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 入力値のバリデーションルールを定義する。
     *
     * @return array<string, mixed> バリデーションルール
     */
    public function rules(): array
    {
        return [
            'new_clock_in' => ['required', 'date_format:H:i'],
            'new_clock_out' => ['required', 'date_format:H:i'],
            'new_break_in.*' => ['nullable', 'date_format:H:i'],
            'new_break_out.*' => ['nullable', 'date_format:H:i'],
            'comment' => ['required', 'string'],
        ];
    }

    /**
     * バリデーションエラーメッセージを定義する。
     *
     * @return array<string, string> エラーメッセージ
     */
    public function messages(): array
    {
        return [
            'new_clock_in.required' => '出勤時間を入力してください',
            'new_clock_in.date_format' => '出勤時間を正しい形式で入力してください',

            'new_clock_out.required' => '退勤時間を入力してください',
            'new_clock_out.date_format' => '退勤時間を正しい形式で入力してください',

            'new_break_in.*.date_format' => '休憩開始時間を正しい形式で入力してください',
            'new_break_out.*.date_format' => '休憩終了時間を正しい形式で入力してください',

            'comment.required' => '備考を記入してください',
        ];
    }

    /**
     * 出退勤時間と休憩時間の前後関係を追加検証する。
     *
     * @param  Validator  $validator  バリデーション処理
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $clockIn = $this->input('new_clock_in');
            $clockOut = $this->input('new_clock_out');

            // 出勤・退勤の前後関係
            if ($clockIn && $clockOut) {
                if ($clockIn > $clockOut) {
                    $validator->errors()->add(
                        'new_clock_in',
                        '出勤時間もしくは退勤時間が不適切な値です'
                    );
                }
            }

            $breakIns = $this->input('new_break_in', []);
            $breakOuts = $this->input('new_break_out', []);

            foreach ($breakIns as $index => $breakIn) {
                $breakOut = $breakOuts[$index] ?? null;

                // 休憩開始時間のチェック
                if ($breakIn && $clockIn && $breakIn < $clockIn) {
                    $validator->errors()->add(
                        "new_break_in.$index",
                        '休憩時間が不適切な値です'
                    );
                }

                if ($breakIn && $clockOut && $breakIn > $clockOut) {
                    $validator->errors()->add(
                        "new_break_in.$index",
                        '休憩時間が不適切な値です'
                    );
                }

                // 休憩終了時間のチェック
                if ($breakOut && $clockOut && $breakOut > $clockOut) {
                    $validator->errors()->add(
                        "new_break_out.$index",
                        '休憩時間もしくは退勤時間が不適切な値です'
                    );
                }
            }
        });
    }
}

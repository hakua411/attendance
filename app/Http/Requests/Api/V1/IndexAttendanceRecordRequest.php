<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class IndexAttendanceRecordRequest extends FormRequest
{
    /**
     * このリクエストを許可する。
     */
    public function authorize(): bool
    {
        // GETの一覧取得は認証不要。
        return true;
    }

    /**
     * 検索条件のバリデーションルール。
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', 'integer', 'min:1'],
            'date' => ['sometimes', 'date_format:Y-m-d'],
            'month' => ['sometimes', 'date_format:Y-m'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * バリデーションエラーメッセージ。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.integer' => 'ユーザーIDは整数で指定してください。',
            'user_id.min' => 'ユーザーIDは1以上で指定してください。',
            'date.date_format' => '日付は YYYY-MM-DD 形式で指定してください。',
            'month.date_format' => '月は YYYY-MM 形式で指定してください。',
            'per_page.integer' => '1ページあたりの件数は整数で指定してください。',
            'per_page.min' => '1ページあたりの件数は1以上で指定してください。',
            'per_page.max' => '1ページあたりの件数は100以下で指定してください。',
            'page.integer' => 'ページ番号は整数で指定してください。',
            'page.min' => 'ページ番号は1以上で指定してください。',
        ];
    }
}

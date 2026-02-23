<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * ユーザーがこのリクエストを行う権限があるか
     */
    public function authorize(): bool
    {
        return true; // 今回は制限しないのでtrueに変更
    }

    /**
     * バリデーションルール
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDomainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:255'], // Changed from target_url
            'is_active' => ['sometimes', 'boolean'],
            'check_interval_minutes' => ['sometimes', 'integer', 'min:1'],
            'timeout_seconds' => ['sometimes', 'integer', 'min:1', 'max:120'],
            'check_method' => ['sometimes', 'string', Rule::in(['GET', 'HEAD'])],
            'follow_redirects' => ['sometimes', 'boolean'],
            'max_redirects' => ['sometimes', 'integer', 'min:0', 'max:20'],
            'expected_final_url' => ['nullable', 'url', 'max:2048'],
            'expected_content_marker' => ['nullable', 'string'],
            'content_check_enabled' => ['sometimes', 'boolean'],
            'ssl_check_enabled' => ['sometimes', 'boolean'],
        ];
    }
}

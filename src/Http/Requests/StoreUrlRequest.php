<?php

namespace CleaniqueCoders\Shrinkr\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUrlRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by middleware at the route level
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'original_url' => ['required', 'url', 'max:2048'],
            'custom_slug' => ['nullable', 'string', 'alpha_dash', 'min:3', 'max:255', 'unique:urls,custom_slug'],
            'expiry_duration' => ['nullable', 'integer', 'min:1'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'original_url.required' => 'The original URL is required.',
            'original_url.url' => 'The original URL must be a valid URL.',
            'original_url.max' => 'The original URL may not be greater than 2048 characters.',
            'custom_slug.alpha_dash' => 'The custom slug may only contain letters, numbers, dashes and underscores.',
            'custom_slug.unique' => 'This custom slug is already in use.',
            'expiry_duration.integer' => 'The expiry duration must be a number.',
            'expiry_duration.min' => 'The expiry duration must be at least 1 minute.',
        ];
    }
}

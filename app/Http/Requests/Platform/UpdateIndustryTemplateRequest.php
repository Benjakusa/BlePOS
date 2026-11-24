<?php

namespace App\Http\Requests\Platform;

use App\Services\PlatformPermissionAuthorizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIndustryTemplateRequest extends FormRequest
{
    public function authorize(PlatformPermissionAuthorizer $authorizer): bool
    {
        return $authorizer->allows($this->user('platform'), 'platform.modules.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $template = $this->route('template');
        $templateId = is_object($template) ? $template->id : $template;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'lowercase',
                'max:64',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('industry_templates', 'slug')->ignore($templateId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'default_modules' => ['required', 'array', 'min:1'],
            'default_modules.*' => ['required', 'string', 'exists:modules,slug'],
            'auto_add_dependencies' => ['nullable', 'boolean'],
            'tax_rates' => ['nullable', 'array'],
            'default_roles' => ['nullable', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.regex' => 'The slug must only contain lowercase alphanumeric characters and hyphens (e.g. retail-store).',
            'default_modules.required' => 'At least one module must be selected for the template.',
        ];
    }
}

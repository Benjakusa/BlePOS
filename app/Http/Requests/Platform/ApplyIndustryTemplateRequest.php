<?php

namespace App\Http\Requests\Platform;

use App\Services\PlatformPermissionAuthorizer;
use Illuminate\Foundation\Http\FormRequest;

class ApplyIndustryTemplateRequest extends FormRequest
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
        return [
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'template_id' => ['nullable', 'integer', 'exists:industry_templates,id'],
        ];
    }
}

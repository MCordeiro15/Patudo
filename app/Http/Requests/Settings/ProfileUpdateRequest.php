<?php

/**
 * Form request para validação da atualização do perfil. Valida nome (obrigatório, máximo 255) e email (obrigatório, único).
 * Form request for profile update validation. Validates name (required, max 255) and email (required, unique).
 */
namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Define as regras de validação para os campos do formulário.
     * Defines validation rules for form fields.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->profileRules($this->user()->id);
    }
}

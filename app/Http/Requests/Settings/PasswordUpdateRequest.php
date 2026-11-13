<?php

/**
 * Form request para validação da alteração de password. Valida password atual, nova password e confirmação.
 * Form request for password change validation. Validates current password, new password, and confirmation.
 */
namespace App\Http\Requests\Settings;

use App\Concerns\PasswordValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PasswordUpdateRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * Define as regras de validação para os campos do formulário.
     * Defines validation rules for form fields.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => $this->currentPasswordRules(),
            'password' => $this->passwordRules(),
        ];
    }
}

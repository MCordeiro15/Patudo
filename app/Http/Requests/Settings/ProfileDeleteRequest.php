<?php


/**
 * Form request para validação da eliminação da conta. Exige confirmação da password atual.
 * Form request for account deletion validation. Requires current password confirmation.
 */
namespace App\Http\Requests\Settings;

use App\Concerns\PasswordValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileDeleteRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * Define as regras de validação para os campos do formulário.
     * Defines validation rules for form fields.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */|/**
     * Define as regras de validação para os campos do formulário.
     * Defines validation rules for form fields.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => $this->currentPasswordRules(),
        ];
    }
}

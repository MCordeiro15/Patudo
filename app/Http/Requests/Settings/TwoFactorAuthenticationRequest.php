<?php

/**
 * Form request para validação da ativação/desativação de 2FA. Valida o código de verificação.
 * Form request for 2FA enable/disable validation. Validates the verification code.
 */
namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Laravel\Fortify\InteractsWithTwoFactorState;

class TwoFactorAuthenticationRequest extends FormRequest
{
    use InteractsWithTwoFactorState;

    /**
     * Define as regras de validação para os campos do formulário.
     * Defines validation rules for form fields.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}

// Definição de tipo TypeScript para autenticação. Inclui tipo User com dados de sessão, permissões e perfil.
// TypeScript type definition for authentication. Includes User type with session data, permissions, and profile.
export type User = {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    location?: string | null;
    about?: string | null;
    avatar?: string;
    avatar_url?: string | null;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    is_petsitter?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User | null;
};

export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};

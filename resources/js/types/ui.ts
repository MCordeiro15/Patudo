// Definição de tipo TypeScript para UI. Inclui Appearance (light/dark/system) e ResolvedAppearance.
// TypeScript type definition for UI. Includes Appearance (light/dark/system) and ResolvedAppearance.
export type Appearance = 'light' | 'dark' | 'system';
export type ResolvedAppearance = 'light' | 'dark';

export type AppVariant = 'header' | 'sidebar';

export type FlashToast = {
    type: 'success' | 'info' | 'warning' | 'error';
    message: string;
};

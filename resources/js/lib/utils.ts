// Funções utilitárias gerais: clsx para classes condicionais, resolução de links do Inertia e outros helpers reutilizáveis.
// General utility functions: clsx for conditional classes, Inertia link resolution, and other reusable helpers.
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}

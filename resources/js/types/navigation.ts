// Definição de tipo TypeScript para itens de navegação. Inclui label, ícone, rota e permissões.
// TypeScript type definition for navigation items. Includes label, icon, route, and permissions.
import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
};

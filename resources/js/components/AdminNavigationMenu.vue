<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const isAdmin = computed(() => page.props.auth.isAdmin);

const links = computed(() => [
    { label: 'Utilizadores', href: '/admin/users' },
    { label: 'Verificações', href: '/admin/petsitters/pending', count: page.props.adminCounts?.pending_petsitters },
    { label: 'Reservas', href: '/admin/bookings' },
    { label: 'Avaliações', href: '/admin/reviews' },
    { label: 'Denúncias', href: '/admin/reports', count: page.props.adminCounts?.open_reports },
    { label: 'Suporte', href: '/admin/support', count: page.props.adminCounts?.open_support },
    { label: 'Definições', href: '/admin/settings' },
]);
</script>

<template>
    <div v-if="isAdmin" class="dropdown">
        <button
            type="button"
            class="nav-link dropdown-toggle text-dark rounded-pill px-4"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            aria-label="Páginas de administração"
        >
            Admin
        </button>
        <ul class="dropdown-menu">
            <li v-for="item in links" :key="item.href">
                <Link
                    :href="item.href"
                    class="dropdown-item d-flex align-items-center justify-content-between gap-3"
                >
                    {{ item.label }}
                    <span v-if="item.count" class="badge rounded-pill bg-danger">
                        {{ item.count }}
                    </span>
                </Link>
            </li>
        </ul>
    </div>
</template>
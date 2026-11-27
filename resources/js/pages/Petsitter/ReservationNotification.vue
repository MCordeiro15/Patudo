<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { LogOut, UserRound } from '@lucide/vue';
import { computed } from 'vue';
import logoPatudo from '@/../../assets/logoPatudo.svg';
import AdminNavigationMenu from '@/components/AdminNavigationMenu.vue';
import HeaderNotificationsChat from '@/components/HeaderNotificationsChat.vue';
import BookingList from '@/components/BookingList.vue';

// A página é partilhada: petsitters veem pedidos recebidos e donos veem os seus pedidos pendentes.
const page = usePage<{ auth: { isAdmin: boolean; user: { is_petsitter?: boolean } | null } }>();
const viewerIsPetsitter = computed(() => page.props.auth.user?.is_petsitter === true);
const showOwnerNavigation = computed(() => !viewerIsPetsitter.value || page.props.auth.isAdmin);
</script>

<template>
    <Head title="Pedidos de reserva" />

    <main class="min-vh-100 py-4 patudo-page patudo-page-shell">
        <div class="container">
            <!-- Navbar -->
            <nav class="navbar navbar-expand-lg bg-white border border-primary rounded-4 px-3 px-md-4">
                <a class="navbar-brand fw-bold fs-3 d-flex align-items-center gap-2" href="/">
                    <img :src="logoPatudo" alt="Patudo" width="70" height="70" />
                    <span>Patudo</span>
                </a>

                <div class="nav nav-pills border rounded-pill p-1 mx-auto">
                    <a v-if="showOwnerNavigation" class="nav-link text-dark rounded-pill px-4" href="/">Início</a>
                    <a class="nav-link active rounded-pill px-4" href="/orders" aria-current="page">Pedidos</a>
                    <a v-if="showOwnerNavigation" class="nav-link text-dark rounded-pill px-4" href="/favorites">Favoritos</a>
                    <a class="nav-link text-dark rounded-pill px-4" href="/reservations">Reservas</a>
                    <AdminNavigationMenu />
                </div>

                <div class="d-flex align-items-center gap-3">
                    <HeaderNotificationsChat />
                    <a href="/profile" class="text-dark" aria-label="Perfil">
                        <UserRound :size="25" />
                    </a>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="btn btn-outline-danger rounded-pill d-flex align-items-center gap-2 px-3"
                    >
                        <LogOut :size="16" />
                        <span>Sair</span>
                    </Link>
                </div>
            </nav>

            <h1 class="text-center fw-bold display-5 mt-5 mb-5">Pedidos de reserva</h1>

            <!-- Lista de pedidos -->
            <BookingList
                :role="viewerIsPetsitter ? 'petsitter' : 'owner'"
                :pending-only="!viewerIsPetsitter"
            />
        </div>

    </main>
</template>

<style scoped>
.patudo-page {
    background-color: var(--patudo-branco, #fffaf3);
}
</style>

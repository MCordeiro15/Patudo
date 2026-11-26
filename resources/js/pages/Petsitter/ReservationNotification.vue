<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Bell, LogOut, MessageCircle, UserRound } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import logoPatudo from '@/../../assets/logoPatudo.svg';
import BookingList from '@/components/BookingList.vue';
import { useNotifications } from '@/composables/useNotifications';
import ModalNotifications from '@/pages/Modals/ModalNotifications.vue';

// A página é partilhada: petsitters veem pedidos recebidos e donos veem os seus pedidos pendentes.
const page = usePage<{ auth: { user: { is_petsitter?: boolean } | null } }>();
const viewerIsPetsitter = computed(() => page.props.auth.user?.is_petsitter === true);

const showNotifications = ref(false);
const { unreadCount, fetchNotifications } = useNotifications();

onMounted(() => {
    void fetchNotifications();
});
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
                    <a class="nav-link text-dark rounded-pill px-4" href="/">Início</a>
                    <a class="nav-link active rounded-pill px-4" href="/orders" aria-current="page">Pedidos</a>
                    <a class="nav-link text-dark rounded-pill px-4" href="/favorites">Favoritos</a>
                    <a class="nav-link text-dark rounded-pill px-4" href="/reservations">Reservas</a>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <a href="/messages" class="text-dark" aria-label="Mensagens">
                        <MessageCircle :size="25" />
                    </a>
                    <button
                        type="button"
                        class="text-dark bg-transparent border-0 p-0 position-relative"
                        aria-label="Notificações"
                        @click="showNotifications = true"
                    >
                        <Bell :size="25" />
                        <span
                            v-if="unreadCount > 0"
                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                            style="font-size: 0.6rem;"
                        >
                            {{ unreadCount > 9 ? '9+' : unreadCount }}
                        </span>
                    </button>
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

        <ModalNotifications
            :show="showNotifications"
            @close="showNotifications = false"
        />
    </main>
</template>

<style scoped>
.patudo-page {
    background-color: var(--patudo-branco, #fffaf3);
}
</style>

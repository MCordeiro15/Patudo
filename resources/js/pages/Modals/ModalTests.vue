<!-- Página Vue: ModalTests.vue -->
<!-- Vue page: ModalTests.vue -->
<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';

import AcceptPaymentRequest from '@/pages/Modals/AcceptPaymentRequest.vue';
import ModalAddPet from '@/pages/Modals/ModalAddPet.vue';
import ModalBookingPageAddPet from '@/pages/Modals/ModalBookingPageAddPet.vue';
import ModalDeleteAnimal from '@/pages/Modals/ModalDeleteAnimal.vue';
import ModalDeletePost from '@/pages/Modals/ModalDeletePost.vue';
import ModalEditOwnerProfile from '@/pages/Modals/ModalEditOwnerProfile.vue';
import ModalEditPetsitterProfile from '@/pages/Modals/ModalEditPetsitterProfile.vue';
import ModalEditPost from '@/pages/Modals/ModalEditPost.vue';
import ModalFormPetsitter from '@/pages/Modals/ModalFormPetsitter.vue';
import ModalNewPost from '@/pages/Modals/ModalNewPost.vue';
import ModalNotifications from '@/pages/Modals/ModalNotifications.vue';
import ModalPayment from '@/pages/Modals/ModalPayment.vue';
import ModalPetDetails from '@/pages/Modals/ModalPetDetails.vue';
import ModalReportPetsitter from '@/pages/Modals/ModalReportPetsitter.vue';
import ModalReviewPetsitter from '@/pages/Modals/ModalReviewPetsitter.vue';

const showAddPet = ref(false);
const showEditOwner = ref(false);
const showEditPetsitter = ref(false);
const showFormPetsitter = ref(false);
const showNewPost = ref(false);
const showNotifications = ref(false);
const showPetDetails = ref(false);
const showReport = ref(false);
const showReview = ref(false);
const showAcceptBooking = ref(false);
const showAddPetToBooking = ref(false);
const showPayment = ref(false);
const showDeleteAnimal = ref(false);
const showDeletePost = ref(false);
const showEditPost = ref(false);

const mockPet = {
    id: 1,
    name: 'Bobby',
    type: 'Cão',
    breed: 'Golden Retriever',
    age: '3 anos',
    size: 'Médio',
    sex: 'Macho',
    weight: '25 kg',
    care: 'Passeios diários longos',
    medication: 'Nenhuma',
    aggressiveness: 'Não',
    walks: '2x por dia',
    about: 'Muito dócil e energético.',
};
</script>

<template>
    <Head title="Bancada de Modais" />

    <div
        style="
            position: fixed;
            inset: 0;
            background-color: #fffbf6;
            z-index: 10;
            padding: 40px;
            overflow-y: auto;
        "
    >
        <h1
            style="
                font-size: 28px;
                font-weight: bold;
                margin-bottom: 8px;
                color: #111827;
            "
        >
            Bancada de Testes de Modais
        </h1>
        <p style="color: #6b7280; margin-bottom: 24px">
            Testa a abertura, fecho e formulários dos 15 modais do Patudo:
        </p>

        <div style="display: flex; gap: 12px; flex-wrap: wrap">
            <button type="button" class="btn-pill" @click="showAddPet = true">
                1. Adicionar Animal
            </button>
            <button
                type="button"
                class="btn-pill"
                @click="showPetDetails = true"
            >
                2. Ficha do Animal
            </button>
            <button
                type="button"
                class="btn-pill"
                @click="showEditOwner = true"
            >
                3. Editar Perfil Tutor
            </button>
            <button
                type="button"
                class="btn-pill"
                @click="showEditPetsitter = true"
            >
                4. Editar Perfil Petsitter
            </button>
            <button
                type="button"
                class="btn-pill"
                @click="showFormPetsitter = true"
            >
                5. Formulário Petsitter
            </button>
            <button type="button" class="btn-pill" @click="showNewPost = true">
                6. Nova Publicação
            </button>
            <button type="button" class="btn-pill" @click="showReview = true">
                7. Avaliar Petsitter
            </button>
            <button type="button" class="btn-pill" @click="showReport = true">
                8. Denunciar Petsitter
            </button>
            <button
                type="button"
                class="btn-pill"
                @click="showNotifications = true"
            >
                9. Notificações
            </button>
            <button
                type="button"
                class="btn-pill"
                @click="showAcceptBooking = true"
            >
                10. Aceitar Pedido de Pagamento
            </button>
            <button
                type="button"
                class="btn-pill"
                @click="showAddPetToBooking = true"
            >
                11. Adicionar Animal à Reserva
            </button>
            <button type="button" class="btn-pill" @click="showPayment = true">
                12. Pagamento da Reserva
            </button>
            <button
                type="button"
                class="btn-pill"
                @click="showDeleteAnimal = true"
            >
                13. Eliminar Animal
            </button>
            <button
                type="button"
                class="btn-pill"
                @click="showDeletePost = true"
            >
                14. Eliminar Post
            </button>
            <button type="button" class="btn-pill" @click="showEditPost = true">
                15. Editar Post
            </button>
        </div>

        <ModalAddPet :show="showAddPet" @close="showAddPet = false" />

        <ModalPetDetails
            :show="showPetDetails"
            :pet="mockPet"
            @close="showPetDetails = false"
        />

        <ModalEditOwnerProfile
            :show="showEditOwner"
            @close="showEditOwner = false"
        />

        <ModalEditPetsitterProfile
            :show="showEditPetsitter"
            :petsitter="null"
            @close="showEditPetsitter = false"
        />

        <ModalFormPetsitter
            :show="showFormPetsitter"
            @close="showFormPetsitter = false"
        />

        <ModalNewPost :show="showNewPost" @close="showNewPost = false" />

        <ModalReviewPetsitter
            :show="showReview"
            :petsitter="{
                id: 1,
                name: 'João Silva',
                avatar_url: null,
                service_summary: 'Cuidado de cães · 12/11 – 15/11/2026',
            }"
            @close="showReview = false"
        />

        <ModalReportPetsitter :show="showReport" @close="showReport = false" />

        <ModalNotifications
            :show="showNotifications"
            @close="showNotifications = false"
        />

        <AcceptPaymentRequest
            :show="showAcceptBooking"
            :bookingId="1"
            @close="showAcceptBooking = false"
        />

        <ModalBookingPageAddPet
            :show="showAddPetToBooking"
            :bookingId="1"
            @close="showAddPetToBooking = false"
        />

        <ModalPayment
            :show="showPayment"
            service-label="Serviço de Pet Sitting - Petsitter João"
            :amount="50"
            @close="showPayment = false"
            @paid="router.visit('/payment-confirmed')"
        />

        <ModalDeleteAnimal
            :show="showDeleteAnimal"
            :pet="mockPet"
            @close="showDeleteAnimal = false"
        />

        <ModalDeletePost
            :show="showDeletePost"
            post-title="Título de teste da publicação"
            @close="showDeletePost = false"
        />

        <ModalEditPost
            :show="showEditPost"
            :post="null"
            @close="showEditPost = false"
        />
    </div>
</template>

<style scoped>
.btn-pill {
    background-color: var(--patudo-terracota, #c2410c);
    color: #ffffff;
    border: none;
    padding: 10px 20px;
    border-radius: 9999px;
    font-weight: 600;
    cursor: pointer;
    font-size: 14px;
    transition: opacity 0.15s ease;
}

.btn-pill:hover {
    opacity: 0.9;
}
</style>

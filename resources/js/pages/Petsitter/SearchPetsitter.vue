<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

interface Petsitter {
    user_id: number;
    service_location: string;
    price_per_day: string | number;
    accepted_animal_types: string[] | null;
    user?: { name: string };
}

const petsitters = ref<Petsitter[]>([]);
const msg = ref('');
const isError = ref(false);
const isLoading = ref(false);

const animalLabels: Record<string, string> = {
    dog: 'Cão',
    cat: 'Gato',
    rabbit: 'Coelho',
    bird: 'Ave',
};

const label = (type: string) => animalLabels[type] ?? type;

const loadPetsitters = async () => {
    isLoading.value = true;
    msg.value = '';
    isError.value = false;

    try {
        const res = await fetch('/api/petsitters', {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (res.status === 401) {
            window.location.href = '/login';
            return;
        }

        const data = await res.json();
        if (res.ok) {
            petsitters.value = data.data;
        } else {
            isError.value = true;
            msg.value = 'Erro: ' + (data.message || JSON.stringify(data.errors));
        }
    } catch (e: any) {
        isError.value = true;
        msg.value = 'Erro: ' + e.message;
    } finally {
        isLoading.value = false;
    }
};

onMounted(loadPetsitters);
</script>

<template>
    <Head title="Procurar Petsitter" />

    <div class="container py-5">
        <h1 class="fw-bold mb-4" style="color: var(--patudo-castanhoDark)">Procurar Petsitter</h1>

        <p v-if="isLoading">A carregar...</p>
        <div v-else-if="msg" :class="['alert py-2 small', isError ? 'alert-danger' : 'alert-success']">
            {{ msg }}
        </div>
        <p v-else-if="petsitters.length === 0">Ainda não há petsitters disponíveis.</p>
        <!-- Placeholder Card para testes da lógica / placeholder card for back-end testing-->
        <div v-else class="row g-4">
            <div v-for="p in petsitters" :key="p.user_id" class="col-12 col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <h5 class="card-title">{{ p.user?.name }}</h5>
                            <!-- placeholder: as avaliações virão da tabela reviews -->
                            <span>⭐ 0,0 (0)</span>
                        </div>
                        <p class="mb-2">{{ p.service_location }}</p>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span v-for="type in p.accepted_animal_types ?? []" :key="type" class="badge bg-success">
                                {{ label(type) }}
                            </span>
                        </div>
                        <p class="mb-0">A partir de <strong>{{ p.price_per_day }} €</strong> / dia</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

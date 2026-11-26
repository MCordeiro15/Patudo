<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Bell, CalendarDays, LogOut, MessageCircle, UserRound, ChevronLeft, Dog, Plus } from '@lucide/vue';
import { computed, onMounted, ref, watch } from 'vue';
import logoPatudo from '@/../../assets/logoPatudo.svg';
import type { Animal } from '@/types';
import { useNotifications } from '@/composables/useNotifications';
import ModalNotifications from '@/pages/Modals/ModalNotifications.vue';
import ModalBookingPageAddPet from '@/pages/Modals/ModalBookingPageAddPet.vue';
import ModalPayment from '@/pages/Modals/ModalPayment.vue';

// O id do petsitter vem do URL: /booking?petsitter=ID
const petsitterId = new URLSearchParams(window.location.search).get('petsitter');

function getXsrf(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

function apiFetch(url: string, options: RequestInit = {}) {
    return fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': getXsrf(),
            ...options.headers,
        },
    });
}

// Estado do formulário
const startDate = ref('');
const endDate = ref('');

// Se a data de saída ficar antes da de entrada (ex.: mudaram a entrada depois), a saída é removida.
// As datas vêm em "AAAA-MM-DD", por isso comparar as strings chega.
watch([startDate, endDate], ([start, end]) => {
    if (start && end && end < start) endDate.value = '';
});

const selectedAnimals = ref<number[]>([]);
const notes = ref('');
const selectedServiceId = ref<number | null>(null);
const entranceHour = ref('');
const exitHour = ref('');
const zone = ref('');
const feedback = ref('');

// No mesmo dia, a hora de saída tem de ser depois da de entrada (a API também o exige).
// Se ficar igual ou antes, é removida. As horas vêm em "HH:MM", por isso comparar as strings chega.
const sameDay = computed(() => !!startDate.value && startDate.value === endDate.value);
watch([sameDay, entranceHour, exitHour], ([same, entrance, exit]) => {
    if (same && entrance && exit && exit <= entrance) exitHour.value = '';
});
const submitting = ref(false);

// Pagamento fictício: o modal de pagamento
const showPayment = ref(false);

// Dados carregados
const animals = ref<Animal[]>([]);
const loadError = ref('');
const servicesError = ref('');

type PetsitterServiceData = {
    id: number;
    type: 'pet-sitting' | 'passeio';
    price: number;
};

const services = ref<PetsitterServiceData[]>([]);

type PetsitterData = {
    id: number;
    user?: { name: string };
    price_per_day: number | null;
    service_location: string | null;
    city: string | null;
    short_bio: string | null;
    avatar_url: string | null;
    average_rating: number | null;
    reviews_count: number;
    availability_days: string[] | null;
};

const petsitter = ref<PetsitterData | null>(null);

// Computed
const displayName = computed(() => (petsitter.value ? (petsitter.value.user?.name ?? 'Petsitter') : 'Petsitter'));

const displayLocation = computed(() => (petsitter.value ? (petsitter.value.service_location ?? petsitter.value.city ?? '--') : '--'));

const selectedService = computed(() =>
    services.value.find((service) => service.id === selectedServiceId.value) ?? null,
);

const selectedServicePrice = computed(() => selectedService.value?.price ?? 0);

const selectedServiceLabel = computed(() => {
    if (selectedService.value?.type === 'passeio') return 'Passeios';
    if (selectedService.value?.type === 'pet-sitting') return 'Pet sitting';
    return '—';
});

const selectedAnimalNames = computed(() => {
    const names = animals.value
        .filter((animal) => selectedAnimals.value.includes(animal.id))
        .map((animal) => animal.name);

    return names.length > 0 ? names.join(', ') : '—';
});

const availabilityText = computed(() => {
    const dayLabels: Record<string, string> = {
        monday: 'segunda-feira',
        tuesday: 'terça-feira',
        wednesday: 'quarta-feira',
        thursday: 'quinta-feira',
        friday: 'sexta-feira',
        saturday: 'sábado',
        sunday: 'domingo',
    };

    const days = (petsitter.value?.availability_days ?? [])
        .map((day) => dayLabels[day])
        .filter(Boolean);

    return days.length > 0
        ? `Disponibilidade do cuidador: ${days.join(', ')}.`
        : 'O cuidador não indicou dias de disponibilidade.';
});

const numberOfDays = computed(() => {
    if (!startDate.value || !endDate.value) return 0;

    const start = new Date(startDate.value);
    const end = new Date(endDate.value);

    const diff = Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24)) + 1;

    return diff > 0 ? diff : 0;
});

const totalPrice = computed(() => numberOfDays.value * selectedServicePrice.value);

const isFormValid = computed(() =>
    Boolean(selectedServiceId.value && startDate.value && endDate.value && selectedAnimals.value.length > 0),
);

// Data mínima para seleção (hoje)
const today = new Date().toISOString().split('T')[0];

// Carregar dados
const loadPetsitter = async () => {
    if (!petsitterId) return;

    const res = await apiFetch(`/api/petsitters/${petsitterId}`);

    if (res.ok) petsitter.value = (await res.json()).data;
    else feedback.value = 'Não foi possível carregar o petsitter.';
};

const loadAnimals = async () => {
    loadError.value = '';

    try {
        await fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' });

        const response = await fetch('/api/animals', {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (response.ok) {
            const data = await response.json();
            animals.value = data.data ?? [];
        } else {
            loadError.value = 'Erro ao carregar os animais.';
        }
    } catch {
        loadError.value = 'Erro de conexão. Tente novamente.';
    }
};

const loadServices = async () => {
    servicesError.value = '';

    if (!petsitterId) return;

    try {
        const response = await fetch(`/api/petsitters/${petsitterId}/services`, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (response.ok) {
            const data = await response.json();
            services.value = data.data ?? [];
        } else {
            servicesError.value = 'Não foi possível carregar os serviços.';
        }
    } catch {
        servicesError.value = 'Erro de conexão ao carregar os serviços.';
    }
};

// Toggle seleção de animal
const toggleAnimal = (id: number) => {
    const index = selectedAnimals.value.indexOf(id);

    if (index > -1) {
        selectedAnimals.value.splice(index, 1);
    } else {
        selectedAnimals.value.push(id);
    }
};

// Quando um novo animal é criado no modal, adiciona à lista e seleciona-o
const handleAnimalCreated = (animal: Animal) => {
    if (!animal?.id) return;

    const alreadyInList = animals.value.some((item) => item.id === animal.id);

    if (!alreadyInList) {
        animals.value.push(animal);
    }

    if (!selectedAnimals.value.includes(animal.id)) {
        selectedAnimals.value.push(animal.id);
    }

    loadError.value = '';
};

// Submeter reserva
const submitBooking = async () => {
    if (!isFormValid.value || !petsitterId) return;

    submitting.value = true;
    feedback.value = '';

    try {
        await fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' });

        const res = await apiFetch('/api/bookings', {
            method: 'POST',
            body: JSON.stringify({
                petsitter_id: Number(petsitterId),
                start_date: startDate.value,
                end_date: endDate.value,
                service_id: selectedServiceId.value,
                animal_ids: selectedAnimals.value,
                message: notes.value.trim() || null,
                zone: zone.value.trim() || null,
                entrance_hour: entranceHour.value || null,
                exit_hour: exitHour.value || null,
            }),
        });

        if (res.ok) {
            // reserva paga e criada: vai para a página "Pagamento Confirmado" com o id da nova reserva
            const created = (await res.json()).data;
            router.visit(`/payment-confirmed?booking=${created.id}`);
            feedback.value = 'Pagamento feito e reserva criada com sucesso! Aguarda a confirmação do petsitter.';
            startDate.value = '';
            endDate.value = '';
            selectedAnimals.value = [];
            notes.value = '';
            selectedServiceId.value = null;
            entranceHour.value = '';
            exitHour.value = '';
            zone.value = '';
        } else {
            const err = await res.json().catch(() => ({}));

            if (err.errors) {
                feedback.value = Object.values(err.errors).flat().join(' ');
            } else {
                feedback.value = err.message ?? 'Não foi possível criar a reserva.';
            }
        }
    } catch {
        feedback.value = 'Erro de conexão. Tente novamente.';
    } finally {
        submitting.value = false;
    }
};

const showNotifications = ref(false);
const { unreadCount } = useNotifications();
const showAddPetModal = ref(false);

// Dias da semana das datas escolhidas em que o cuidador não trabalha (o servidor verifica o mesmo).
// Bastam os primeiros 7 dias: a partir daí os dias da semana repetem-se.
const weekdayKeys = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
const weekdayLabels: Record<string, string> = {
    monday: 'segunda-feira',
    tuesday: 'terça-feira',
    wednesday: 'quarta-feira',
    thursday: 'quinta-feira',
    friday: 'sexta-feira',
    saturday: 'sábado',
    sunday: 'domingo',
};

const unavailableDays = computed(() => {
    if (!petsitter.value || numberOfDays.value === 0) return [];
    const available = petsitter.value.availability_days ?? [];
    const missing: string[] = [];
    const day = new Date(`${startDate.value}T00:00:00`);

    for (let i = 0; i < Math.min(numberOfDays.value, 7); i++) {
        const key = weekdayKeys[day.getDay()];
        if (!available.includes(key) && !missing.includes(key)) missing.push(key);
        day.setDate(day.getDate() + 1);
    }

    return missing.map((key) => weekdayLabels[key]);
});

// Só deixa pagar com o formulário completo e dias disponíveis
const canPay = computed(() => isFormValid.value && unavailableDays.value.length === 0);

const serviceLabelForPayment = computed(() => `Serviço de ${selectedServiceLabel.value} - Petsitter ${displayName.value}`);

// "Pagar" no modal: cria a reserva e fecha o modal
const payAndBook = async () => {
    await submitBooking();
    showPayment.value = false;
};

onMounted(() => {
    void loadPetsitter();
    void loadAnimals();
    void loadServices();
});
</script>

<template>
    <Head title="Nova Reserva" />

    <main class="booking-page min-vh-100 py-4">
        <div class="container booking-container">
            <nav class="navbar navbar-expand-lg bg-white border border-primary rounded-4 px-3 px-md-4 booking-nav gap-3 gap-lg-4">
                <a class="navbar-brand fw-bold fs-3 d-flex align-items-center gap-2 booking-brand" href="/">
                    <img :src="logoPatudo" alt="Patudo" width="58" height="58" />
                    <span>Patudo</span>
                </a>

                <div class="booking-links nav nav-pills border rounded-pill p-1 mx-auto">
                    <a class="nav-link rounded-pill px-3 px-lg-4" href="/">Início</a>
                    <a class="nav-link rounded-pill px-3 px-lg-4" href="/orders">Pedidos</a>
                    <a class="nav-link rounded-pill px-3 px-lg-4" href="/favorites">Favoritos</a>
                    <a class="nav-link active rounded-pill px-3 px-lg-4" href="/reservations">Reservas</a>
                </div>

                <div class="booking-actions d-flex align-items-center gap-3">
                    <a href="/messages" class="text-dark">
                        <MessageCircle :size="25" />
                    </a>

                    <button
                        type="button"
                        class="text-dark bg-transparent border-0 p-0 position-relative"
                        @click="showNotifications = true"
                    >
                        <Bell :size="25" />
                        <span
                            v-if="unreadCount > 0"
                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge"
                        >
                            {{ unreadCount > 9 ? '9+' : unreadCount }}
                        </span>
                    </button>

                    <button type="button" class="text-dark p-0 border-0 bg-transparent">
                        <UserRound :size="25" />
                    </button>

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

            <a
                :href="petsitterId ? `/petsitters/${petsitterId}` : '/petsitters'"
                class="back-link d-inline-flex align-items-center gap-1 mt-5"
            >
                <ChevronLeft :size="24" />
                Voltar ao perfil
            </a>

            <h1 class="booking-title text-center mt-2">Pedir reserva</h1>

            <div
                v-if="feedback"
                class="alert py-2 small booking-feedback"
                :class="feedback.includes('sucesso') ? 'alert-success' : 'alert-danger'"
                role="alert"
            >
                {{ feedback }}
            </div>

            <div class="row justify-content-center g-3 g-lg-4 booking-layout">
                <div class="col-12 col-lg-7">
                    <section class="booking-form-card">
                        <div class="booking-section">
                            <h2>1. Escolhe o serviço</h2>

                            <div v-if="servicesError" class="alert alert-danger py-2 small mb-0" role="alert">
                                {{ servicesError }}
                            </div>

                            <div v-else-if="services.length === 0" class="booking-empty">
                                Este cuidador ainda não tem serviços disponíveis.
                            </div>

                            <div v-else class="service-grid">
                                <button
                                    v-for="service in services"
                                    :key="service.id"
                                    type="button"
                                    class="service-option text-start"
                                    :class="{ selected: selectedServiceId === service.id }"
                                    @click="selectedServiceId = service.id"
                                >
                                    <strong>
                                        {{ service.type === 'passeio' ? 'Passeios' : 'Pet sitting' }}
                                    </strong>

                                    <small>
                                        € {{ service.price }} / {{ service.type === 'passeio' ? 'passeio' : 'dia' }}
                                    </small>

                                    <span
                                        class="service-radio"
                                        :class="{ selected: selectedServiceId === service.id }"
                                    ></span>
                                </button>
                            </div>
                        </div>

                        <div class="booking-section">
                            <h2>2. Escolhe o animal</h2>

                            <div v-if="loadError" class="alert alert-danger py-2 small mb-0" role="alert">
                                {{ loadError }}
                            </div>

                            <div v-else-if="animals.length === 0" class="booking-empty text-center">
                                <Dog :size="38" class="mb-1" />
                                <p class="mb-2">Não tens animais registados.</p>
                                <button
                                    type="button"
                                    class="add-pet-button"
                                    @click="showAddPetModal = true"
                                >
                                    Adicionar o meu primeiro animal
                                </button>
                            </div>

                            <div v-else class="animal-grid">
                                <button
                                    v-for="animal in animals"
                                    :key="animal.id"
                                    type="button"
                                    class="animal-option"
                                    :class="{ 'animal-selected': selectedAnimals.includes(animal.id) }"
                                    @click="toggleAnimal(animal.id)"
                                >
                                    <span class="animal-avatar d-flex align-items-center justify-content-center flex-shrink-0 overflow-hidden">
                                        <img
                                            v-if="animal.photo"
                                            :src="animal.photo"
                                            :alt="animal.name"
                                            class="w-100 h-100 object-fit-cover"
                                        />
                                        <Dog v-else :size="24" />
                                    </span>

                                    <span class="text-start flex-grow-1">
                                        <strong class="d-block">{{ animal.name }}</strong>
                                        <small>
                                            {{ animal.species }}{{ animal.breed ? ' · ' + animal.breed : '' }}{{
                                                animal.age != null ? ` · ${animal.age} ${animal.age === 1 ? 'ano' : 'anos'}` : ''
                                            }}
                                        </small>
                                    </span>

                                    <span
                                        class="animal-check"
                                        :class="{ checked: selectedAnimals.includes(animal.id) }"
                                    >
                                        ✓
                                    </span>
                                </button>
                            </div>

                            <button
                                v-if="!loadError && animals.length > 0"
                                type="button"
                                class="add-pet-button d-inline-flex align-items-center gap-1 mt-2"
                                @click="showAddPetModal = true"
                            >
                                <Plus :size="15" />
                                Adicionar animal
                            </button>
                        </div>

                        <div class="booking-section">
                            <h2>3. Escolhe as datas e a hora</h2>

                            <div class="row g-2">
                                <div class="col-12 col-sm-6 col-lg-3">
                                    <label for="start-date" class="form-label">Data de entrada</label>
                                    <input
                                        id="start-date"
                                        v-model="startDate"
                                        type="date"
                                        class="form-control booking-input"
                                        :min="today"
                                    />
                                </div>

                                <div class="col-12 col-sm-6 col-lg-3">
                                    <label for="end-date" class="form-label">Data de saída</label>
                                    <input
                                        id="end-date"
                                        v-model="endDate"
                                        type="date"
                                        class="form-control booking-input"
                                        :min="startDate || today"
                                    />
                                </div>

                                <div class="col-12 col-sm-6 col-lg-3">
                                    <label for="entrance-hour" class="form-label">Hora de entrada</label>
                                    <input
                                        id="entrance-hour"
                                        v-model="entranceHour"
                                        type="time"
                                        class="form-control booking-input"
                                    />
                                </div>

                                <div class="col-12 col-sm-6 col-lg-3">
                                    <label for="exit-hour" class="form-label">Hora de saída</label>
                                    <input
                                        id="exit-hour"
                                        v-model="exitHour"
                                        type="time"
                                        class="form-control booking-input"
                                        :min="sameDay ? entranceHour || undefined : undefined"
                                    />
                                </div>
                            </div>

                            <p class="booking-help mb-0">
                                <CalendarDays :size="14" />
                                {{ availabilityText }}
                            </p>

                            <!-- datas com dias em que o cuidador não trabalha -->
                            <p v-if="unavailableDays.length > 0" class="booking-help text-danger mb-0">
                                O cuidador não está disponível à {{ unavailableDays.join(', ') }}. Escolhe outras datas.
                            </p>

                            <p v-if="numberOfDays > 0" class="booking-help mb-0">
                                {{ numberOfDays }}
                                {{ numberOfDays === 1 ? 'dia de cuidado' : 'dias de cuidado' }}
                            </p>
                        </div>

                        <div class="booking-section">
                            <h2>4. Onde é o serviço?</h2>
                            <label for="booking-zone" class="form-label">Zona</label>
                            <input
                                id="booking-zone"
                                v-model="zone"
                                type="text"
                                maxlength="255"
                                class="form-control booking-input"
                                placeholder="Indica a zona"
                            />
                        </div>

                        <div class="booking-section mb-0">
                            <h2>5. Mensagem para o cuidador <span>(opcional)</span></h2>
                            <textarea
                                v-model="notes"
                                class="form-control booking-input booking-notes"
                                rows="4"
                                maxlength="500"
                                placeholder="Fala-lhe do teu animal, rotinas, horários de entrega..."
                            />
                            <div class="notes-count">{{ notes.length }}/500</div>
                        </div>
                    </section>
                </div>

                <div class="col-12 col-lg-4">
                    <aside class="booking-summary sticky-lg-top">
                        <h2>Resumo</h2>

                        <div class="summary-profile d-flex align-items-center gap-2">
                            <div class="petsitter-avatar d-flex align-items-center justify-content-center flex-shrink-0 overflow-hidden">
                                <img
                                    v-if="petsitter?.avatar_url"
                                    :src="petsitter.avatar_url"
                                    alt="Foto de perfil"
                                    class="w-100 h-100 object-fit-cover"
                                />
                                <UserRound v-else :size="29" />
                            </div>

                            <div class="summary-details">
                                <strong>{{ displayName }}</strong>
                                <small class="summary-rating">
                                    <span class="rating-star">★</span>
                                    {{ petsitter?.average_rating ?? '—' }} ·
                                    {{ petsitter?.reviews_count ?? 0 }} avaliações
                                </small>
                            </div>
                        </div>

                        <div class="summary-lines">
                            <div>
                                <span>Serviço</span>
                                <strong>{{ selectedServiceLabel }}</strong>
                            </div>

                            <div>
                                <span>Animais</span>
                                <strong>{{ selectedAnimalNames }}</strong>
                            </div>

                            <div>
                                <span>Entrada</span>
                                <strong>{{ startDate || '—' }}</strong>
                            </div>

                            <div>
                                <span>Saída</span>
                                <strong>{{ endDate || '—' }}</strong>
                            </div>

                            <div>
                                <span>Cálculo</span>
                                <strong>
                                    {{
                                        numberOfDays
                                            ? `${numberOfDays} dia${numberOfDays === 1 ? '' : 's'} × € ${selectedServicePrice}`
                                            : '—'
                                    }}
                                </strong>
                            </div>
                        </div>

                        <div class="summary-total d-flex align-items-center justify-content-between">
                            <strong>Total</strong>
                            <span>€ {{ totalPrice.toFixed(2) }}</span>
                        </div>

                        <button
                            type="button"
                            class="btn btn-primary w-100"
                            @click="showPayment = true"
                        >
                            <span
                                v-if="submitting"
                                class="spinner-border spinner-border-sm me-2"
                                role="status"
                            ></span>
                            {{ submitting ? 'A pagar...' : 'Fazer Pagamento' }}
                        </button>

                        <p class="summary-note">
                            O pedido só fica confirmado quando o cuidador aceitar.
                        </p>
                    </aside>
                </div>
            </div>
        </div>

        <ModalNotifications
            :show="showNotifications"
            @close="showNotifications = false"
        />

        <!-- Pagamento (fictício): o "Pagar" cria a reserva -->
        <ModalPayment
            :show="showPayment"
            :service-label="serviceLabelForPayment"
            :amount="totalPrice"
            :processing="submitting"
            @close="showPayment = false"
            @paid="payAndBook"
        />

        <ModalBookingPageAddPet
            :show="showAddPetModal"
            @close="showAddPetModal = false"
            @created="handleAnimalCreated"
        />
    </main>
</template>

<style scoped>
.booking-page {
    background: #fffaf3;
    color: #2e1d14;
}

.booking-container {
    max-width: 1240px;
}

.booking-nav {
    min-height: 76px;
    padding: .45rem 1.35rem;
    background: #fff;
    border: 1px solid #e4571c;
    border-radius: 1.25rem;
}

.booking-brand {
    color: #2e1d14;
    font-size: 2rem;
    font-weight: 800;
    text-decoration: none;
}

.booking-brand img {
    object-fit: contain;
}

.booking-links .nav-link {
    color: #2e1d14;
    font-weight: 500;
}

.booking-links .nav-link.active {
    color: #fff !important;
    background: var(--patudo-terracota, #c2410c) !important;
}


.notification-badge {
    font-size: .6rem;
}

.back-link {
    color: #2e1d14;
    font-size: .95rem;
    font-weight: 700;
    text-decoration: none;
}

.booking-title {
    margin: 3rem 0 3.25rem;
    font-size: clamp(2.2rem, 4vw, 3.1rem);
    font-weight: 800;
}

.booking-feedback {
    max-width: 940px;
    margin: -2rem auto 1.5rem;
}

.booking-layout {
    max-width: 990px;
    margin: 0 auto;
}

.booking-form-card {
    padding: 1.15rem;
    background: #fed7aa;
    border: 1.5px solid #d64b16;
    border-radius: 1.25rem;
}

.booking-section {
    margin-bottom: 1.35rem;
}

.booking-section h2 {
    margin: 0 0 .65rem;
    font-size: 1rem;
    font-weight: 800;
}

.booking-section h2 span {
    font-weight: 400;
}

.service-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(145px, 1fr));
    gap: .55rem;
}

.service-option {
    position: relative;
    min-height: 68px;
    padding: .65rem 2rem .65rem .75rem;
    color: #2e1d14;
    background: #fff;
    border: 1.5px solid #d64b16;
    border-radius: .75rem;
    transition: box-shadow .15s, transform .15s;
}

.service-option:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 10px rgba(96, 45, 17, .12);
}

.service-option.selected {
    box-shadow: inset 0 0 0 1px #c2410c;
}

.service-option strong,
.service-option small {
    display: block;
}

.service-option strong {
    font-size: .84rem;
}

.service-option small {
    margin-top: .2rem;
    font-size: .72rem;
    color: #725b4c;
}

.service-radio {
    position: absolute;
    top: .7rem;
    right: .7rem;
    width: 16px;
    height: 16px;
    border: 1.5px solid #c2410c;
    border-radius: 50%;
}

.service-radio.selected::after {
    display: block;
    width: 8px;
    height: 8px;
    margin: 2.5px;
    content: '';
    background: #c2410c;
    border-radius: 50%;
}

.add-pet-button {
    padding: 0;
    color: #24150e;
    background: transparent;
    border: 0;
    border-bottom: 1px solid currentColor;
    font-size: .82rem;
    font-weight: 700;
}

.animal-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .55rem;
}

.animal-option {
    display: flex;
    align-items: center;
    gap: .55rem;
    width: 100%;
    padding: .55rem .65rem;
    color: #2e1d14;
    background: #fff;
    border: 1.5px solid #d64b16;
    border-radius: .8rem;
    cursor: pointer;
    transition: transform .15s, box-shadow .15s;
}

.animal-option:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 10px rgba(96, 45, 17, .12);
}

.animal-option.animal-selected {
    border-color: #c2410c;
    box-shadow: inset 0 0 0 1px #c2410c;
}

.animal-avatar {
    width: 38px;
    height: 38px;
    color: #c2410c;
    background: #fff4e4;
    border-radius: 50%;
}

.animal-option strong {
    font-size: .86rem;
}

.animal-option small {
    font-size: .72rem;
}

.animal-check {
    display: grid;
    place-items: center;
    width: 19px;
    height: 19px;
    margin-left: auto;
    color: transparent;
    border: 1.5px solid #c2410c;
    border-radius: .3rem;
    font-size: .75rem;
}

.animal-check.checked {
    color: #fff;
    background: #c2410c;
}

.booking-empty {
    padding: 1.25rem;
    color: #725b4c;
    background: rgba(255, 255, 255, .55);
    border-radius: .8rem;
}

.form-label {
    margin-bottom: .25rem;
    font-size: .78rem;
    font-weight: 700;
}

.form-label span {
    font-weight: 400;
}

.booking-input {
    min-height: 42px;
    border: 1.5px solid #d64b16;
    border-radius: .55rem;
    background: #fffdf9;
    font-size: .88rem;
}

.booking-input:focus {
    border-color: #c2410c;
    box-shadow: 0 0 0 .16rem rgba(194, 65, 12, .16);
}

.booking-help {
    display: flex;
    align-items: center;
    gap: .35rem;
    margin-top: .55rem;
    font-size: .76rem;
}

.booking-notes {
    resize: vertical;
}

.notes-count {
    margin-top: .2rem;
    font-size: .7rem;
    text-align: right;
}

.booking-summary {
    top: 1rem;
    padding: 1.25rem;
    background: #fff;
    border: 1.5px solid #d64b16;
    border-radius: 1.1rem;
}

.booking-summary h2 {
    margin: 0 0 .9rem;
    font-size: 1.2rem;
    font-weight: 800;
}

.petsitter-avatar {
    width: 48px;
    height: 48px;
    color: #c2410c;
    border: 1px solid #2e1d14;
    border-radius: 50%;
}

.summary-profile {
    padding-bottom: .85rem;
    border-bottom: 2px solid #d64b16;
    align-items: center !important;
}

.summary-profile .petsitter-avatar {
    margin-top: 0;
}

.summary-details {
    padding-top: 0;
}

.summary-profile strong {
    display: block;
    font-size: .9rem;
    line-height: 1.2;
}

.summary-profile small {
    margin-top: .18rem;
    font-size: .74rem;
    line-height: 1.25;
    color: #725b4c;
}

.summary-profile .summary-rating {
    margin-top: .2rem;
    color: #2e1b12;
}

.summary-profile .rating-star {
    color: #f4b400;
}

.summary-lines {
    padding: .65rem 0;
    border-bottom: 2px solid #d64b16;
}

.summary-lines > div {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    padding: .18rem 0;
    font-size: .78rem;
}

.summary-lines strong {
    text-align: right;
}

.summary-total {
    padding: .75rem 0;
}

.summary-total span {
    font-size: 1.65rem;
    font-weight: 800;
}

.booking-submit {
    padding: .75rem 1rem;
    color: #fff;
    background: #c9450b;
    border: 0;
    border-radius: 999px;
    font-size: .88rem;
    font-weight: 800;
}

.booking-submit:hover:not(:disabled) {
    background: #e75a13;
}

.booking-submit:disabled {
    opacity: .5;
    cursor: not-allowed;
}

.summary-note {
    max-width: 220px;
    margin: .8rem auto 0;
    color: #725b4c;
    font-size: .72rem;
    text-align: center;
}

@media (max-width: 991.98px) {
    .booking-nav {
        flex-wrap: wrap;
    }

    .booking-links {
        order: 3;
        width: 100%;
        overflow-x: auto;
    }

    .booking-actions {
        margin-left: auto;
    }

    .booking-title {
        margin: 2.5rem 0;
    }
}

@media (max-width: 575.98px) {
    .booking-nav {
        padding: .55rem .75rem;
    }

    .booking-brand {
        font-size: 1.55rem;
    }

    .booking-brand img {
        width: 44px;
        height: 44px;
    }

    .booking-actions {
        gap: .7rem !important;
    }

    .booking-links .nav-link {
        padding: .35rem .7rem !important;
        font-size: .83rem;
    }

    .animal-grid {
        grid-template-columns: 1fr;
    }

    .booking-form-card {
        padding: .9rem;
    }
}
</style>


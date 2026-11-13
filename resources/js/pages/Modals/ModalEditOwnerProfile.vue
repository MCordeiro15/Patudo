<script setup lang="ts">
import { ref, watch } from 'vue';

export interface OwnerProfileData {
    name?: string;
    location?: string;
    phone?: string;
    about?: string;
    avatar_url?: string | null;
}

const props = defineProps<{
    show: boolean;
    user?: OwnerProfileData;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'save', payload: FormData): void;
    (e: 'delete-account'): void;
}>();

const name = ref('');
const location = ref('');
const phone = ref('');
const about = ref('');
const avatarFile = ref<File | null>(null);
const avatarPreview = ref<string | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);
const isLoading = ref(false);

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            name.value = props.user?.name ?? '';
            location.value = props.user?.location ?? '';
            phone.value = props.user?.phone ?? '';
            about.value = props.user?.about ?? '';
            avatarPreview.value = props.user?.avatar_url ?? null;
            avatarFile.value = null;
        }
    },
    { immediate: true }
);

const triggerFileInput = () => {
    fileInputRef.value?.click();
};

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;

    avatarFile.value = file;
    avatarPreview.value = URL.createObjectURL(file);
};

const handleClose = () => {
    emit('close');
};

const handleSubmit = () => {
    isLoading.value = true;

    const formData = new FormData();
    formData.append('name', name.value.trim());
    formData.append('location', location.value.trim());
    formData.append('phone', phone.value.trim());
    formData.append('about', about.value.trim());
    if (avatarFile.value) {
        formData.append('avatar', avatarFile.value);
    }

    emit('save', formData);
    isLoading.value = false;
    emit('close');
};

const handleDeleteAccount = () => {
    emit('delete-account');
};
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="patudo-modal-backdrop" @click.self="handleClose" @keydown.escape="handleClose">
            <div class="patudo-modal-card patudo-edit-profile-card" role="dialog" aria-modal="true">
                <button type="button" class="patudo-close-btn" aria-label="Fechar" @click="handleClose">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round">
                        <path d="M18 6L6 18M6 6l12 12" />
                    </svg>
                </button>

                <div class="mb-2">
                    <h2 class="patudo-modal-title">Editar Perfil</h2>
                </div>

                <div class="d-flex flex-column align-items-center mb-4">
                    <div class="patudo-avatar-circle mb-2">
                        <img v-if="avatarPreview" :src="avatarPreview" alt="Foto de perfil" class="patudo-avatar-img"/>
                        <svg v-else viewBox="0 0 100 100" class="patudo-avatar-placeholder" aria-hidden="true">
                            <circle cx="50" cy="50" r="48" fill="#ffffff" />
                            <path d="M35 72 C35 66, 65 66, 65 72 L68 85 L32 85 Z" fill="#f3c69f" />
                            <path d="M27 52 C27 25, 73 25, 73 52 L76 64 L24 64 Z" fill="#f25c66" />
                            <path d="M34 42 C34 30, 66 30, 66 42 C66 57, 58 66, 50 66 C42 66, 34 57, 34 42 Z" fill="#fbd3ae" />
                            <path d="M32 42 C32 24, 68 24, 68 42 C60 39, 42 44, 32 42 Z" fill="#f25c66" />
                        </svg>
                    </div>

                    <input ref="fileInputRef" type="file" accept="image/*" class="d-none" @change="handleFileChange"/>

                    <button type="button" class="btn-patudo-avatar-pill" @click="triggerFileInput">
                        Alterar foto
                    </button>
                </div>

                <form @submit.prevent="handleSubmit">
                    <div class="row g-4 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="owner-name" class="patudo-field-label d-block mb-1">
                                Nome Completo
                            </label>
                            <input id="owner-name" v-model="name" type="text" class="patudo-pill-input" required/>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="owner-location" class="patudo-field-label d-block mb-1">
                                Localização / Cidade
                            </label>
                            <input id="owner-location" v-model="location" type="text" class="patudo-pill-input"/>
                        </div>
                    </div>

                    <div class="row g-4 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="owner-phone" class="patudo-field-label d-block mb-1">
                                Telemóvel / Whatsapp
                            </label>
                            <input id="owner-phone" v-model="phone" type="tel" class="patudo-pill-input"/>
                        </div>
                    </div>

                     <div class="mb-4">
                        <label for="owner-about" class="patudo-field-label d-block mb-1">
                            Sobre mim
                        </label>
                        <textarea id="owner-about" v-model="about" rows="4" class="patudo-bordered-textarea"
                        ></textarea>
                    </div>

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-2">
                        <button type="button" class="btn-patudo-danger" @click="handleDeleteAccount">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6" />
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                <line x1="10" y1="11" x2="10" y2="17" />
                                <line x1="14" y1="11" x2="14" y2="17" />
                            </svg>
                            <span>Eliminar Conta</span>
                        </button>

                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn-patudo-outline-sm" @click="handleClose">
                                Cancelar
                            </button>
                            <button type="submit" class="btn-patudo-solid-sm" :disabled="isLoading">
                                {{ isLoading ? 'A guardar...' : 'Guardar Alterações' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.patudo-modal-backdrop {
    position: fixed;
    inset: 0;
    background-color: rgba(0, 0, 0, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    z-index: 10000000;
}

.patudo-edit-profile-card {
    position: relative;
    width: 100%;
    max-width: 570px;
    background-color: #fff8f1;
    border: 1.5px solid #c4450c;
    border-radius: 18px;
    padding: 1.75rem 2rem;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
    font-family: 'Plus Jakarta Sans', sans-serif;
}

.patudo-close-btn {
    position: absolute;
    top: 1.35rem;
    right: 1.35rem;
    background: transparent;
    border: none;
    color: #231810;
    cursor: pointer;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: opacity 0.15s ease;
}

.patudo-close-btn:hover {
    opacity: 0.65;
}

.patudo-modal-title {
    font-size: 1.45rem;
    font-weight: 700;
    color: #231810;
    margin: 0;
}

.patudo-avatar-circle {
    width: 96px;
    height: 96px;
    border-radius: 50%;
    border: 1px solid #3d3229;
    background-color: #ffffff;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.patudo-avatar-img, .patudo-avatar-placeholder {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.btn-patudo-avatar-pill {
    background-color: #ffd8a8;
    color: #231810;
    border: none;
    border-radius: 9999px;
    padding: 0.35rem 1.25rem;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.btn-patudo-avatar-pill:hover {
    background-color: #ffc987;
}

.patudo-field-label {
    font-size: 0.85rem;
    font-weight: 700;
    color: #231810;
}

.patudo-pill-input {
    width: 100%;
    height: 36px;
    background-color: #ffffff;
    border: 1px solid #d98a6c;
    border-radius: 9999px;
    padding: 0 1rem;
    font-size: 0.86rem;
    color: #231810;
    outline: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.patudo-pill-input:focus {
    border-color: #c4450c;
    box-shadow: 0 0 0 2px rgba(196, 69, 12, 0.18);
}

.patudo-bordered-textarea {
    width: 100%;
    background-color: #ffffff;
    border: 1px solid #d98a6c;
    border-radius: 10px;
    padding: 0.65rem 0.9rem;
    font-size: 0.86rem;
    color: #231810;
    resize: none;
    outline: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.patudo-bordered-textarea:focus {
    border-color: #c4450c;
    box-shadow: 0 0 0 2px rgba(196, 69, 12, 0.18);
}

.btn-patudo-danger {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    background-color: #ea2b1f;
    color: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 0.45rem 1.1rem;
    font-size: 0.84rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.btn-patudo-danger:hover {
    background-color: #c81e14;
}

.btn-patudo-outline-sm {
    background-color: #ffffff;
    color: var(--patudo-terracota);
    border: 1px solid var(--patudo-terracota);
    border-radius: 8px;
    padding: 0.45rem 1rem;
    font-size: 0.84rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.btn-patudo-outline-sm:hover {
    background-color: #fdf2ea;
}

.btn-patudo-solid-sm {
    background-color: #c4450c;
    color: #ffffff;
    border: 1px solid #c4450c;
    border-radius: 8px;
    padding: 0.45rem 1.15rem;
    font-size: 0.84rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.15s ease;
}

.btn-patudo-solid-sm:hover:not(:disabled) {
    background-color: #a83908;
}

.btn-patudo-solid-sm:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>

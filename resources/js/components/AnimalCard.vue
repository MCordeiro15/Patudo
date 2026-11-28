<!-- Componente que exibe um card com informações resumidas de um animal de estimação. -->
<!-- Component that displays a card with summarized pet information. -->
<script setup lang="ts">
import { Pencil, Trash2 } from '@lucide/vue';
import type { Animal } from '@/types';

// Props recebidas do componente pai.
// Props received from the parent component.
const props = defineProps<{
    animal: Animal;
}>();

const emit = defineEmits<{
    edit: [id: number];
    delete: [id: number];
}>();

const sizeLabelMap: Record<string, string> = {
    small: 'Pequeno',
    medium: 'Médio',
    large: 'Grande',
};

const sizeBadgeClassMap: Record<string, string> = {
    small: 'text-bg-success',
    medium: 'text-bg-warning',
    large: 'text-bg-danger',
};

const sizeLabel = (size: string) => sizeLabelMap[size] ?? size;
const sizeBadgeClass = (size: string) =>
    sizeBadgeClassMap[size] ?? 'text-bg-secondary';
</script>

<template>
    <div class="card animal-card border border-primary rounded-4 p-3 h-100 d-flex flex-column">
        <div class="d-flex align-items-start gap-3 flex-grow-1">
            <div
                class="d-flex align-items-center justify-content-center rounded-circle bg-light flex-shrink-0 overflow-hidden"
                style="width: 56px; height: 56px"
            >
                <img
                    v-if="animal.photo"
                    :src="animal.photo"
                    :alt="animal.name"
                    class="w-100 h-100 object-fit-cover"
                />
                <span v-else class="fs-4">🐾</span>
            </div>

            <div class="w-100">
                <div class="d-flex align-items-center justify-content-between">
                    <h3 class="h6 mb-1">{{ animal.name }}</h3>
                    <span
                        class="badge rounded-pill px-2 py-1"
                        :class="sizeBadgeClass(animal.size)"
                    >
                        {{ sizeLabel(animal.size) }}
                    </span>
                </div>

                <p class="mb-1 small text-muted">
                    <strong>Espécie:</strong> {{ animal.species }}
                </p>
                <p class="mb-1 small text-muted">
                    <strong>Raça:</strong> {{ animal.breed }}
                </p>

                <p v-if="animal.special_needs" class="mb-0 small text-muted">
                    <strong>Necessidades especiais:</strong>
                    {{ animal.special_needs }}
                </p>
            </div>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-2">
            <button
                type="button"
                class="patudo-icon-action patudo-icon-action-edit"
                :aria-label="`Editar ${animal.name}`"
                :title="`Editar ${animal.name}`"
                @click="emit('edit', props.animal.id)"
            >
                <Pencil :size="16" />
            </button>
            <button
                type="button"
                class="patudo-icon-action patudo-icon-action-delete"
                :aria-label="`Remover ${animal.name}`"
                :title="`Remover ${animal.name}`"
                @click="emit('delete', props.animal.id)"
            >
                <Trash2 :size="16" />
            </button>
        </div>
    </div>
</template>

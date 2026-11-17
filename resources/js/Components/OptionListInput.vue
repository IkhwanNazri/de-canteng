<script setup>
import { nextTick, ref } from 'vue';
import Icon from './Icon.vue';

// Senarai pilihan gaya Shopee: satu baris untuk setiap pilihan, butang tambah & buang.
const model = defineModel({ type: Array, default: () => [] });
const props = defineProps({
    label: { type: String, required: true },
    placeholder: { type: String, default: '' },
    presets: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) },
    field: { type: String, required: true },
    max: { type: Number, default: 10 },
});

const inputs = ref([]);

const add = async (value = '') => {
    if (model.value.length >= props.max) return;
    if (value && model.value.includes(value)) return;
    model.value = [...model.value, value];
    if (!value) {
        await nextTick();
        inputs.value.at(-1)?.focus();
    }
};

const update = (index, value) => {
    const next = [...model.value];
    next[index] = value;
    model.value = next;
};

const remove = (index) => (model.value = model.value.filter((_, i) => i !== index));

const errorFor = (index) => props.errors[`${props.field}.${index}`];
</script>

<template>
    <div class="rounded-2xl border border-pasir p-4">
        <div class="mb-3 flex items-center justify-between">
            <p class="text-sm font-semibold">{{ label }}</p>
            <span class="text-xs text-kelabu">{{ model.length }}/{{ max }}</span>
        </div>

        <div v-if="presets.length" class="mb-3 flex flex-wrap gap-1.5">
            <button
                v-for="preset in presets"
                :key="preset"
                type="button"
                class="rounded-full border px-3 py-1 text-xs font-semibold transition"
                :class="model.includes(preset) ? 'border-hijau bg-hijau text-white' : 'border-pasir hover:border-hijau'"
                @click="model.includes(preset) ? remove(model.indexOf(preset)) : add(preset)"
            >
                {{ preset }}
            </button>
        </div>

        <div class="space-y-2">
            <div v-for="(option, index) in model" :key="index">
                <div class="flex gap-2">
                    <input
                        :ref="(el) => (inputs[index] = el)"
                        :value="option"
                        :placeholder="placeholder"
                        maxlength="30"
                        class="min-w-0 flex-1 rounded-xl border border-pasir px-3 py-2 text-sm outline-none focus:border-hijau"
                        @input="update(index, $event.target.value)"
                        @keydown.enter.prevent="add()"
                    />
                    <button type="button" class="grid size-9 shrink-0 place-items-center rounded-xl text-kelabu hover:bg-red-50 hover:text-red-600" :aria-label="`Buang ${option}`" @click="remove(index)">
                        <Icon name="minus" :size="16" :stroke="2.2" />
                    </button>
                </div>
                <p v-if="errorFor(index)" class="mt-1 text-xs text-red-600">{{ errorFor(index) }}</p>
            </div>
        </div>

        <button
            v-if="model.length < max"
            type="button"
            class="mt-3 flex w-full items-center justify-center gap-1.5 rounded-xl border border-dashed border-hijau/50 py-2 text-sm font-semibold text-hijau hover:bg-krim"
            @click="add()"
        >
            <Icon name="plus" :size="16" :stroke="2.2" /> Tambah pilihan
        </button>
        <p v-if="errors[field]" class="mt-2 text-xs text-red-600">{{ errors[field] }}</p>
    </div>
</template>

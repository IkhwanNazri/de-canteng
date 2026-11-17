<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import SellerNav from '@/Components/SellerNav.vue';
import BatikPhoto from '@/Components/BatikPhoto.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Icon from '@/Components/Icon.vue';

const props = defineProps({ shop: { type: Object, required: true } });

const form = useForm({
    description: props.shop.description ?? '',
    bank_name: props.shop.bank_name ?? '',
    bank_account: props.shop.bank_account ?? '',
    logo: null,
    banner: null,
});
const logoPreview = ref(props.shop.logo);
const bannerPreview = ref(props.shop.banner);

const pick = (field, preview) => (event) => {
    form[field] = event.target.files[0] ?? null;
    preview.value = form[field] ? URL.createObjectURL(form[field]) : props.shop[field];
};

const submit = () => form.post(route('seller.settings.update'), { forceFormData: true, preserveScroll: true, onSuccess: () => form.reset('logo', 'banner') });
</script>

<template>
    <Head title="Tetapan Kedai" />
    <div class="mx-auto max-w-[1200px] px-4 py-8">
        <p class="text-sm text-kelabu">Pusat Penjual</p>
        <h1 class="mb-4 font-serif text-3xl font-bold text-hijau-gelap">Tetapan Kedai</h1>
        <SellerNav />

        <form class="max-w-3xl space-y-6 rounded-3xl bg-white p-6 shadow-sm md:p-8" @submit.prevent="submit">
            <div>
                <InputLabel value="Banner (min. lebar 600px)" />
                <label class="mt-2 block cursor-pointer">
                    <BatikPhoto :src="bannerPreview" :variant="shop.id" :person="false" class="h-40 rounded-2xl">
                        <span class="absolute inset-x-0 bottom-0 bg-black/50 py-2 text-center text-xs font-semibold text-white">Tukar Banner</span>
                    </BatikPhoto>
                    <input type="file" accept="image/*" class="sr-only" @change="pick('banner', bannerPreview)($event)" />
                </label>
                <InputError :message="form.errors.banner" class="mt-2" />
            </div>

            <div class="flex items-center gap-5">
                <label class="relative block size-24 shrink-0 cursor-pointer overflow-hidden rounded-full bg-hijau-gelap">
                    <img v-if="logoPreview" :src="logoPreview" alt="Logo" class="size-full object-cover" />
                    <Icon v-else name="sparkle" :size="40" class="absolute inset-0 m-auto text-emas" />
                    <span class="absolute inset-x-0 bottom-0 bg-black/50 py-1 text-center text-[10px] font-semibold text-white">Tukar</span>
                    <input type="file" accept="image/*" class="sr-only" @change="pick('logo', logoPreview)($event)" />
                </label>
                <div>
                    <p class="font-serif text-xl font-bold">{{ shop.name }}</p>
                    <Link :href="route('shops.show', shop.slug)" class="text-sm font-semibold text-hijau">Lihat kedai →</Link>
                    <InputError :message="form.errors.logo" class="mt-1" />
                </div>
            </div>

            <div>
                <InputLabel for="description" value="Penerangan" />
                <textarea id="description" v-model="form.description" rows="3" class="mt-1 block w-full rounded-xl border border-pasir px-4 py-2.5 text-sm outline-none focus:border-hijau" />
                <InputError :message="form.errors.description" class="mt-2" />
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <InputLabel for="bank_name" value="Bank" />
                    <TextInput id="bank_name" v-model="form.bank_name" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.bank_name" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="bank_account" value="Nombor Akaun" />
                    <TextInput id="bank_account" v-model="form.bank_account" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.bank_account" class="mt-2" />
                </div>
            </div>

            <PrimaryButton :disabled="form.processing">Simpan</PrimaryButton>
        </form>
    </div>
</template>

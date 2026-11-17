<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import BatikPhoto from '@/Components/BatikPhoto.vue';
import OptionListInput from '@/Components/OptionListInput.vue';

const props = defineProps({
    product: { type: Object, default: null },
    categories: { type: Array, default: () => [] },
});

const isEdit = !!props.product;
const preview = ref(props.product?.image ?? null);

const form = useForm({
    name: props.product?.name ?? '',
    category_id: props.product?.category_id ?? '',
    description: props.product?.description ?? '',
    price: props.product?.price ?? '',
    compare_price: props.product?.compare_price ?? '',
    stock: props.product?.stock ?? 1,
    sizes: [...(props.product?.sizes ?? [])],
    colors: [...(props.product?.colors ?? [])],
    image: null,
});

const pickImage = (event) => {
    form.image = event.target.files[0] ?? null;
    preview.value = form.image ? URL.createObjectURL(form.image) : props.product?.image ?? null;
};

const submit = () => {
    const options = { forceFormData: true };
    if (isEdit) {
        // fail upload perlu POST + _method untuk kemas kini
        form.transform((data) => ({ ...data, _method: 'put' })).post(route('seller.products.update', props.product.id), options);
    } else {
        form.post(route('seller.products.store'), options);
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Edit Produk' : 'Tambah Produk'" />
    <div class="mx-auto max-w-3xl px-4 py-8">
        <Link :href="route('seller.products.index')" class="text-sm font-semibold hover:text-hijau">← Produk Saya</Link>
        <h1 class="mt-2 mb-6 font-serif text-3xl font-bold text-hijau-gelap">{{ isEdit ? 'Edit Produk' : 'Tambah Produk' }}</h1>

        <form class="grid gap-6 rounded-3xl bg-white p-6 shadow-sm md:grid-cols-[200px_1fr] md:p-8" @submit.prevent="submit">
            <div>
                <label class="block cursor-pointer">
                    <BatikPhoto :src="preview" :variant="product?.id ?? 0" :person="false" class="aspect-square rounded-2xl">
                        <span class="absolute inset-x-0 bottom-0 bg-black/50 py-2 text-center text-xs font-semibold text-white">Pilih Gambar</span>
                    </BatikPhoto>
                    <input type="file" accept="image/*" class="sr-only" @change="pickImage" />
                </label>
                <InputError :message="form.errors.image" class="mt-2" />
            </div>

            <div class="space-y-5">
                <div>
                    <InputLabel for="name" value="Nama Produk" />
                    <TextInput id="name" v-model="form.name" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="category" value="Kategori" />
                    <select id="category" v-model="form.category_id" class="mt-1 block w-full rounded-xl border border-pasir bg-white px-4 py-2.5 text-sm">
                        <option value="">Tiada</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                    <InputError :message="form.errors.category_id" class="mt-2" />
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <InputLabel for="price" value="Harga (RM)" />
                        <TextInput id="price" v-model="form.price" type="number" step="0.01" min="1" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.price" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="compare_price" value="Harga Asal" />
                        <TextInput id="compare_price" v-model="form.compare_price" type="number" step="0.01" class="mt-1 block w-full" />
                        <InputError :message="form.errors.compare_price" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="stock" value="Stok" />
                        <TextInput id="stock" v-model="form.stock" type="number" min="0" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.stock" class="mt-2" />
                    </div>
                </div>
                <div>
                    <p class="mb-2 text-sm font-semibold">Variasi <span class="font-normal text-kelabu">(pilihan — kosongkan jika tiada, cth: beg)</span></p>
                    <div class="grid gap-3 md:grid-cols-2">
                        <OptionListInput v-model="form.sizes" label="Saiz" field="sizes" placeholder="cth: M" :presets="['XS', 'S', 'M', 'L', 'XL', 'XXL']" :errors="form.errors" />
                        <OptionListInput v-model="form.colors" label="Warna" field="colors" placeholder="cth: Hijau Zamrud" :errors="form.errors" />
                    </div>
                </div>
                <div>
                    <InputLabel for="description" value="Penerangan" />
                    <textarea id="description" v-model="form.description" rows="4" class="mt-1 block w-full rounded-xl border border-pasir px-4 py-2.5 text-sm outline-none focus:border-hijau" />
                    <InputError :message="form.errors.description" class="mt-2" />
                </div>
                <PrimaryButton :disabled="form.processing">{{ isEdit ? 'Simpan' : 'Tambah Produk' }}</PrimaryButton>
            </div>
        </form>
    </div>
</template>

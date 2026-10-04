<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { Category } from '@/types';
import { Plus, Pencil, Trash2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    categories: Category[];
}>();

const showForm = ref(false);
const editingId = ref<string | null>(null);

const form = useForm({
    name: '',
    type: 'expense' as 'income' | 'expense',
    parent_id: '' as string | null,
    color: '#10b981',
    icon: '',
});

function openAdd() {
    form.reset();
    editingId.value = null;
    showForm.value = true;
}

function openEdit(c: Category) {
    form.name = c.name;
    form.type = c.type;
    form.parent_id = c.parent_id ?? '';
    form.color = c.color ?? '#10b981';
    form.icon = c.icon ?? '';
    editingId.value = c.id;
    showForm.value = true;
}

function submit() {
    const payload = {
        name: form.name,
        type: form.type,
        parent_id: form.parent_id || null,
        color: form.color,
        icon: form.icon || null,
    };
    if (editingId.value) {
        router.put(`/categories/${editingId.value}`, payload, { onSuccess: () => { showForm.value = false; } });
    } else {
        router.post('/categories', payload, { onSuccess: () => { showForm.value = false; form.reset(); } });
    }
}

function destroy(c: Category) {
    if (!confirm(`Hapus kategori "${c.name}"?`)) return;
    router.delete(`/categories/${c.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Kategori" />

    <div class="flex flex-col gap-4 p-4 lg:p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-semibold">Kategori</h1>
            <Button size="sm" @click="openAdd"><Plus class="h-4 w-4 mr-1" />Tambah Kategori</Button>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <!-- Expense Categories -->
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <h2 class="font-medium text-rose-500 mb-3">Pengeluaran</h2>
                <div class="divide-y">
                    <div v-for="cat in categories.filter(c => c.type === 'expense')" :key="cat.id" class="py-2.5">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="h-3 w-3 rounded-full" :style="`background: ${cat.color ?? '#f43f5e'}`" />
                                <span class="font-medium text-sm">{{ cat.name }}</span>
                            </div>
                            <div class="flex gap-1">
                                <button @click="openEdit(cat)" class="p-1 rounded hover:bg-muted"><Pencil class="h-3.5 w-3.5" /></button>
                                <button @click="destroy(cat)" class="p-1 rounded hover:bg-muted text-rose-500"><Trash2 class="h-3.5 w-3.5" /></button>
                            </div>
                        </div>
                        <!-- Subcategories -->
                        <div v-if="cat.children?.length" class="ml-5 mt-1.5 space-y-1">
                            <div v-for="sub in cat.children" :key="sub.id" class="flex items-center justify-between text-xs text-muted-foreground">
                                <span>↳ {{ sub.name }}</span>
                                <div class="flex gap-1">
                                    <button @click="openEdit(sub)" class="p-0.5 rounded hover:bg-muted"><Pencil class="h-3 w-3" /></button>
                                    <button @click="destroy(sub)" class="p-0.5 rounded hover:bg-muted text-rose-500"><Trash2 class="h-3 w-3" /></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p v-if="!categories.filter(c => c.type === 'expense').length" class="py-4 text-center text-sm text-muted-foreground">Belum ada kategori pengeluaran.</p>
                </div>
            </div>

            <!-- Income Categories -->
            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <h2 class="font-medium text-emerald-600 mb-3">Pemasukan</h2>
                <div class="divide-y">
                    <div v-for="cat in categories.filter(c => c.type === 'income')" :key="cat.id" class="py-2.5">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="h-3 w-3 rounded-full" :style="`background: ${cat.color ?? '#10b981'}`" />
                                <span class="font-medium text-sm">{{ cat.name }}</span>
                            </div>
                            <div class="flex gap-1">
                                <button @click="openEdit(cat)" class="p-1 rounded hover:bg-muted"><Pencil class="h-3.5 w-3.5" /></button>
                                <button @click="destroy(cat)" class="p-1 rounded hover:bg-muted text-rose-500"><Trash2 class="h-3.5 w-3.5" /></button>
                            </div>
                        </div>
                        <div v-if="cat.children?.length" class="ml-5 mt-1.5 space-y-1">
                            <div v-for="sub in cat.children" :key="sub.id" class="flex items-center justify-between text-xs text-muted-foreground">
                                <span>↳ {{ sub.name }}</span>
                                <div class="flex gap-1">
                                    <button @click="openEdit(sub)" class="p-0.5 rounded hover:bg-muted"><Pencil class="h-3 w-3" /></button>
                                    <button @click="destroy(sub)" class="p-0.5 rounded hover:bg-muted text-rose-500"><Trash2 class="h-3 w-3" /></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p v-if="!categories.filter(c => c.type === 'income').length" class="py-4 text-center text-sm text-muted-foreground">Belum ada kategori pemasukan.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="showForm = false">
        <div class="bg-card rounded-xl border shadow-lg w-full max-w-sm p-6 mx-4">
            <h2 class="text-base font-semibold mb-4">{{ editingId ? 'Edit' : 'Tambah' }} Kategori</h2>
            <form @submit.prevent="submit" class="space-y-3">
                <div class="flex rounded-lg border overflow-hidden text-sm">
                    <button type="button" v-for="t in ['expense', 'income']" :key="t"
                        @click="form.type = t as 'income' | 'expense'"
                        class="flex-1 py-1.5 font-medium transition-colors"
                        :class="form.type === t ? (t === 'expense' ? 'bg-rose-500 text-white' : 'bg-emerald-500 text-white') : 'hover:bg-muted'">
                        {{ t === 'expense' ? 'Pengeluaran' : 'Pemasukan' }}
                    </button>
                </div>
                <div>
                    <label class="text-xs text-muted-foreground">Nama Kategori</label>
                    <input v-model="form.name" required class="w-full h-9 rounded-md border px-3 text-sm bg-background mt-0.5" />
                </div>
                <div>
                    <label class="text-xs text-muted-foreground">Induk Kategori (opsional)</label>
                    <select v-model="form.parent_id" class="w-full h-9 rounded-md border px-3 text-sm bg-background mt-0.5">
                        <option value="">Tidak ada (Kategori Utama)</option>
                        <option v-for="c in categories.filter(c => c.type === form.type && c.id !== editingId)" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-muted-foreground">Warna</label>
                    <input v-model="form.color" type="color" class="mt-0.5 h-9 w-16 rounded-md border bg-background cursor-pointer" />
                </div>
                <div class="flex justify-end gap-2 pt-1">
                    <Button type="button" variant="ghost" @click="showForm = false">Batal</Button>
                    <Button type="submit">{{ editingId ? 'Simpan' : 'Tambah' }}</Button>
                </div>
            </form>
        </div>
    </div>
</template>

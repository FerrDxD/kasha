<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useMoney } from '@/composables/useMoney';
import type { RecurringTransaction, Account, Category } from '@/types';
import { Plus, Pencil, Trash2, RefreshCw } from '@lucide/vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    recurring: (RecurringTransaction & { next_dates: string[] })[];
    accounts: Pick<Account, 'id' | 'name'>[];
    categories: Pick<Category, 'id' | 'name' | 'type'>[];
}>();

const { format } = useMoney();

const FREQ_LABELS: Record<string, string> = { daily: 'Harian', weekly: 'Mingguan', monthly: 'Bulanan', yearly: 'Tahunan' };

const showForm = ref(false);
const editingId = ref<string | null>(null);

const form = useForm({
    account_id: '',
    category_id: '',
    type: 'expense' as 'income' | 'expense',
    amount: '' as unknown as number,
    payee: '',
    frequency: 'monthly' as RecurringTransaction['frequency'],
    interval: 1,
    day_of_month: null as number | null,
    starts_on: new Date().toISOString().split('T')[0],
    ends_on: '',
    next_run_on: new Date().toISOString().split('T')[0],
    mode: 'reminder' as 'auto' | 'reminder',
    is_active: true,
});

function openAdd() {
    form.reset();
    form.interval = 1;
    form.mode = 'reminder';
    form.is_active = true;
    editingId.value = null;
    showForm.value = true;
}

function openEdit(r: RecurringTransaction) {
    Object.assign(form, {
        account_id: r.account_id ?? r.account?.id ?? '',
        category_id: r.category_id ?? '',
        type: r.type,
        amount: r.amount,
        payee: r.payee ?? '',
        frequency: r.frequency,
        interval: r.interval,
        next_run_on: r.next_run_on,
        mode: r.mode,
        is_active: r.is_active,
    });
    editingId.value = r.id;
    showForm.value = true;
}

function submit() {
    if (editingId.value) {
        form.put(`/recurring/${editingId.value}`, { onSuccess: () => { showForm.value = false; } });
    } else {
        form.post('/recurring', { onSuccess: () => { showForm.value = false; form.reset(); } });
    }
}

function destroy(r: RecurringTransaction) {
    if (!confirm(`Hapus recurring "${r.payee}"?`)) return;
    router.delete(`/recurring/${r.id}`, { preserveScroll: true });
}

function typeColor(type: string) {
    return type === 'income' ? 'text-emerald-600' : 'text-rose-500';
}
</script>

<template>
    <Head title="Recurring" />

    <div class="flex flex-col gap-4 p-4 lg:p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-semibold">Transaksi Berulang</h1>
            <Button size="sm" @click="openAdd"><Plus class="h-4 w-4 mr-1" />Tambah</Button>
        </div>

        <div class="rounded-xl border bg-card divide-y">
            <div v-for="r in recurring" :key="r.id" class="p-4 flex items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 p-2 rounded-lg" :class="r.type === 'income' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-500'">
                        <RefreshCw class="h-4 w-4" />
                    </div>
                    <div>
                        <p class="font-medium">{{ r.payee || r.account?.name }}</p>
                        <p class="text-xs text-muted-foreground">{{ FREQ_LABELS[r.frequency] }} · {{ r.account?.name }} · {{ r.mode === 'auto' ? 'Otomatis' : 'Reminder' }}</p>
                        <div class="flex gap-1 mt-1 flex-wrap">
                            <span v-for="d in r.next_dates" :key="d" class="text-xs bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded">{{ new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="tabular-nums font-semibold" :class="typeColor(r.type)">{{ format(r.amount) }}</span>
                    <div class="flex gap-1">
                        <button @click="openEdit(r)" class="p-1.5 rounded hover:bg-muted"><Pencil class="h-3.5 w-3.5" /></button>
                        <button @click="destroy(r)" class="p-1.5 rounded hover:bg-muted text-rose-500"><Trash2 class="h-3.5 w-3.5" /></button>
                    </div>
                </div>
            </div>
            <p v-if="!recurring.length" class="p-8 text-center text-sm text-muted-foreground">Belum ada transaksi berulang.</p>
        </div>
    </div>

    <!-- Modal -->
    <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="showForm = false">
        <div class="bg-card rounded-xl border shadow-lg w-full max-w-md p-6 mx-4 overflow-y-auto max-h-[90vh]">
            <h2 class="text-base font-semibold mb-4">{{ editingId ? 'Edit' : 'Tambah' }} Recurring</h2>
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
                    <label class="text-xs text-muted-foreground">Payee / Nama</label>
                    <input v-model="form.payee" class="w-full h-9 rounded-md border px-3 text-sm bg-background mt-0.5" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-muted-foreground">Nominal (Rp)</label>
                        <input v-model="form.amount" type="number" min="1" required class="w-full h-9 rounded-md border px-3 text-sm bg-background mt-0.5" />
                    </div>
                    <div>
                        <label class="text-xs text-muted-foreground">Akun</label>
                        <select v-model="form.account_id" required class="w-full h-9 rounded-md border px-3 text-sm bg-background mt-0.5">
                            <option value="">Pilih…</option>
                            <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }}</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-muted-foreground">Frekuensi</label>
                        <select v-model="form.frequency" class="w-full h-9 rounded-md border px-3 text-sm bg-background mt-0.5">
                            <option v-for="(label, val) in FREQ_LABELS" :key="val" :value="val">{{ label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-muted-foreground">Mulai</label>
                        <input v-model="form.starts_on" type="date" required class="w-full h-9 rounded-md border px-3 text-sm bg-background mt-0.5" />
                    </div>
                </div>
                <div>
                    <label class="text-xs text-muted-foreground">Eksekusi berikutnya</label>
                    <input v-model="form.next_run_on" type="date" required class="w-full h-9 rounded-md border px-3 text-sm bg-background mt-0.5" />
                </div>
                <div>
                    <label class="text-xs text-muted-foreground">Mode</label>
                    <select v-model="form.mode" class="w-full h-9 rounded-md border px-3 text-sm bg-background mt-0.5">
                        <option value="reminder">Reminder</option>
                        <option value="auto">Otomatis</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-1">
                    <Button type="button" variant="ghost" @click="showForm = false">Batal</Button>
                    <Button type="submit" :disabled="form.processing">{{ editingId ? 'Simpan' : 'Tambah' }}</Button>
                </div>
            </form>
        </div>
    </div>
</template>

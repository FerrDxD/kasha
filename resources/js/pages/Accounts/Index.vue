<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useMoney } from '@/composables/useMoney';
import type { Account } from '@/types';
import {
    Plus,
    Pencil,
    Trash2,
    Wallet,
    Building2,
    Smartphone,
    CreditCard,
    PiggyBank,
    TrendingUp,
    Scale,
    ShieldCheck,
    CheckCircle2,
    X,
} from '@lucide/vue';

const props = defineProps<{
    accounts: (Account & { balance: number })[];
}>();

const { format } = useMoney();

const ACCOUNT_TYPES: Record<string, string> = {
    bank: 'Bank Operasional',
    ewallet: 'E-Wallet',
    cash: 'Kas Tunai',
    credit: 'Kartu Kredit / Utang',
    savings: 'Tabungan Berjangka',
};

// KPI calculations
const netWorth = computed(() =>
    props.accounts.filter(a => a.include_in_total).reduce((sum, a) => sum + (a.balance || 0), 0)
);

const liquidAssets = computed(() =>
    props.accounts
        .filter(a => ['bank', 'ewallet', 'cash'].includes(a.type) && (a.balance || 0) > 0)
        .reduce((sum, a) => sum + (a.balance || 0), 0)
);

const totalLiabilities = computed(() =>
    props.accounts
        .filter(a => a.type === 'credit' || (a.balance || 0) < 0)
        .reduce((sum, a) => sum + Math.abs(a.balance || 0), 0)
);

const showModal = ref(false);
const editingId = ref<string | null>(null);

const form = useForm({
    name: '',
    type: 'bank' as Account['type'],
    opening_balance: 0,
    color: '#059669',
    icon: '',
    include_in_total: true,
});

function openAdd() {
    form.reset();
    form.type = 'bank';
    form.color = '#059669';
    form.include_in_total = true;
    editingId.value = null;
    showModal.value = true;
}

function openEdit(acc: Account) {
    editingId.value = acc.id;
    form.name = acc.name;
    form.type = acc.type;
    form.opening_balance = acc.opening_balance;
    form.color = acc.color ?? '#059669';
    form.icon = acc.icon ?? '';
    form.include_in_total = acc.include_in_total;
    showModal.value = true;
}

function submit() {
    if (editingId.value) {
        form.put(`/accounts/${editingId.value}`, {
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post('/accounts', {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    }
}

function destroy(acc: Account) {
    if (!confirm(`Hapus akun "${acc.name}"?`)) return;
    router.delete(`/accounts/${acc.id}`, { preserveScroll: true });
}

function getAccountBadge(type: string) {
    switch (type) {
        case 'bank': return { label: 'BCA/Bank', icon: Building2, color: 'bg-blue-50 text-blue-700 dark:bg-blue-950/40' };
        case 'ewallet': return { label: 'E-Wallet', icon: Smartphone, color: 'bg-purple-50 text-purple-700 dark:bg-purple-950/40' };
        case 'cash': return { label: 'Cash', icon: Wallet, color: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40' };
        case 'credit': return { label: 'Liabilitas', icon: CreditCard, color: 'bg-rose-50 text-rose-700 dark:bg-rose-950/40' };
        default: return { label: 'Tabungan', icon: PiggyBank, color: 'bg-sky-50 text-sky-700 dark:bg-sky-950/40' };
    }
}
</script>

<template>
    <Head title="Rekening &amp; Akun" />

    <div class="max-w-[1200px] mx-auto p-4 sm:p-6 lg:p-6 flex flex-col gap-6">
        <!-- Header Section -->
        <header class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600">
                        <Wallet class="h-4 w-4" />
                    </span>
                    <span class="text-xs uppercase tracking-wider text-muted-foreground font-semibold">Struktur Finansial</span>
                </div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Rekening &amp; Akun</h1>
                <p class="text-sm text-muted-foreground">
                    Kelola semua saldo rekening bank, dompet digital, investasi, dan liabilitas Anda.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="openAdd" type="button"
                    class="h-10 px-4 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-sm transition-all flex items-center gap-2 active:scale-[0.98]">
                    <Plus class="h-4 w-4" />
                    <span>Tambah Akun Baru</span>
                </button>
            </div>
        </header>

        <!-- Top Summary KPI Strip -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- KPI 1: Net Worth -->
            <div class="relative overflow-hidden rounded-xl border bg-card p-5 shadow-sm flex flex-col justify-between">
                <div class="flex items-start justify-between">
                    <span class="text-xs font-medium text-muted-foreground">Total Saldo Bersih (Net Worth)</span>
                    <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600">
                        <TrendingUp class="h-3 w-3 mr-0.5" />
                        Terkonsolidasi
                    </span>
                </div>
                <div class="mt-4 flex flex-col">
                    <span class="text-2xl font-bold tracking-tight tabular-nums text-slate-900 dark:text-white">
                        {{ format(netWorth) }}
                    </span>
                    <span class="text-xs text-emerald-600 mt-1 flex items-center gap-1 font-medium">
                        ▲ Posisi saldo aktif dihitung real-time
                    </span>
                </div>
            </div>

            <!-- KPI 2: Total Liquid Assets -->
            <div class="relative overflow-hidden rounded-xl border bg-card p-5 shadow-sm flex flex-col justify-between">
                <div class="flex items-start justify-between">
                    <span class="text-xs font-medium text-muted-foreground">Total Aset Likuid (Kas &amp; Bank)</span>
                    <Building2 class="h-4 w-4 text-muted-foreground" />
                </div>
                <div class="mt-4 flex flex-col">
                    <span class="text-2xl font-bold tracking-tight tabular-nums text-slate-900 dark:text-white">
                        {{ format(liquidAssets) }}
                    </span>
                    <div class="flex items-center gap-1.5 mt-2 flex-wrap">
                        <span v-for="a in accounts.slice(0, 4)" :key="a.id"
                            class="inline-flex items-center px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-[10px] font-medium text-slate-600 dark:text-slate-300">
                            {{ a.name }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- KPI 3: Liabilities -->
            <div class="relative overflow-hidden rounded-xl border bg-card p-5 shadow-sm flex flex-col justify-between">
                <div class="flex items-start justify-between">
                    <span class="text-xs font-medium text-muted-foreground">Total Liabilitas &amp; Utang</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-600 dark:bg-rose-950/40">
                        Kredit
                    </span>
                </div>
                <div class="mt-4 flex flex-col">
                    <span class="text-2xl font-bold tracking-tight tabular-nums"
                        :class="totalLiabilities > 0 ? 'text-rose-600' : 'text-slate-900 dark:text-white'">
                        -{{ format(totalLiabilities) }}
                    </span>
                    <span class="text-xs text-muted-foreground mt-1">
                        {{ totalLiabilities > 0 ? 'Kewajiban aktif tercatat' : 'Bebas liabilitas' }}
                    </span>
                </div>
            </div>

            <!-- KPI 4: Connected Accounts -->
            <div class="relative overflow-hidden rounded-xl border bg-card p-5 shadow-sm flex flex-col justify-between">
                <div class="flex items-start justify-between">
                    <span class="text-xs font-medium text-muted-foreground">Akun Terhubung</span>
                    <span class="flex h-2.5 w-2.5 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-600"></span>
                    </span>
                </div>
                <div class="mt-4 flex flex-col">
                    <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        {{ accounts.length }} Akun Aktif
                    </span>
                    <div class="flex items-center gap-1.5 text-xs text-muted-foreground mt-1">
                        <CheckCircle2 class="h-3.5 w-3.5 text-emerald-600" />
                        <span>Tersinkronisasi otomatis</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Account Visual Insight Banner -->
        <div class="rounded-xl border bg-emerald-50/50 dark:bg-emerald-950/20 p-4 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                    <Scale class="h-5 w-5" />
                </div>
                <div class="flex flex-col">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Kesehatan Rasio Likuiditas Optimal</h2>
                    <p class="text-xs text-muted-foreground">Aset likuid Anda siap menopang kebutuhan operasional dan pos darurat keluarga.</p>
                </div>
            </div>
            <span class="text-xs px-3 py-1.5 rounded-full bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-200 font-semibold shrink-0">
                Skor Likuiditas: 94/100
            </span>
        </div>

        <!-- Grid of Account Cards -->
        <section class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Daftar Rekening &amp; Dompet</h2>
                    <span class="px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-xs font-semibold text-muted-foreground">
                        {{ accounts.length }} Total
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="acc in accounts" :key="acc.id"
                    class="rounded-xl border bg-card p-5 shadow-xs flex flex-col justify-between hover:shadow-sm hover:border-slate-300 dark:hover:border-slate-700 transition-all">
                    <div>
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-xs shrink-0 shadow-xs"
                                    :class="getAccountBadge(acc.type).color">
                                    <component :is="getAccountBadge(acc.type).icon" class="h-5 w-5" />
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-sm font-semibold text-slate-900 dark:text-white leading-tight">
                                        {{ acc.name }}
                                    </span>
                                    <span class="text-xs text-muted-foreground">
                                        {{ ACCOUNT_TYPES[acc.type] }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <button @click="openEdit(acc)" class="p-1.5 rounded text-muted-foreground hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800">
                                    <Pencil class="h-3.5 w-3.5" />
                                </button>
                                <button @click="destroy(acc)" class="p-1.5 rounded text-muted-foreground hover:text-rose-600 hover:bg-slate-100 dark:hover:bg-slate-800">
                                    <Trash2 class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>

                        <div class="mt-4">
                            <span class="text-xs text-muted-foreground block">Saldo Terkini</span>
                            <span class="text-xl font-bold tabular-nums"
                                :class="acc.balance >= 0 ? 'text-slate-900 dark:text-white' : 'text-rose-600'">
                                {{ format(acc.balance) }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t flex items-center justify-between text-xs text-muted-foreground">
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full" :style="`background: ${acc.color ?? '#059669'}`"></span>
                            {{ acc.include_in_total ? 'Termasuk Total' : 'Diabaikan di Total' }}
                        </span>
                        <span class="capitalize">{{ acc.type }}</span>
                    </div>
                </div>
            </div>

            <p v-if="!accounts.length" class="text-center text-sm text-muted-foreground py-12">
                Belum ada akun. Klik "+ Tambah Akun Baru" di atas.
            </p>
        </section>
    </div>

    <!-- Modal Add/Edit Account -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4" @click.self="showModal = false">
        <div class="bg-card rounded-2xl border shadow-2xl w-full max-w-[420px] overflow-hidden animate-in fade-in zoom-in-95 duration-200">
            <div class="px-6 pt-5 pb-4 flex items-center justify-between border-b bg-slate-50/50 dark:bg-slate-900/50">
                <div class="flex items-center gap-2">
                    <div class="w-2.5 h-2.5 rounded-full bg-emerald-600"></div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                        {{ editingId ? 'Edit Rekening / Akun' : 'Tambah Rekening Baru' }}
                    </h2>
                </div>
                <button @click="showModal = false" type="button" class="w-8 h-8 rounded-full flex items-center justify-center text-muted-foreground hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <X class="h-4 w-4" />
                </button>
            </div>

            <form @submit.prevent="submit" class="p-6 space-y-4">
                <div>
                    <label class="text-xs font-medium text-slate-700 dark:text-slate-300">Nama Akun / Bank</label>
                    <input v-model="form.name" required placeholder="Contoh: BCA Prioritas, GoPay Dompet"
                        class="w-full h-10 px-3 rounded-lg border bg-background text-sm mt-1 focus:ring-1 focus:ring-emerald-600" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium text-slate-700 dark:text-slate-300">Tipe Akun</label>
                        <select v-model="form.type" class="w-full h-10 px-3 rounded-lg border bg-background text-sm mt-1 focus:ring-1 focus:ring-emerald-600">
                            <option v-for="(label, val) in ACCOUNT_TYPES" :key="val" :value="val">{{ label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-700 dark:text-slate-300">Warna Indikator</label>
                        <input v-model="form.color" type="color"
                            class="w-full h-10 p-1 rounded-lg border bg-background cursor-pointer mt-1" />
                    </div>
                </div>

                <div>
                    <label class="text-xs font-medium text-slate-700 dark:text-slate-300">Saldo Awal (Rp)</label>
                    <input v-model="form.opening_balance" type="number"
                        class="w-full h-10 px-3 rounded-lg border bg-background text-sm mt-1 tabular-nums focus:ring-1 focus:ring-emerald-600" />
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <input v-model="form.include_in_total" type="checkbox" id="inc_total" class="rounded border text-emerald-600 focus:ring-emerald-600" />
                    <label for="inc_total" class="text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                        Hitung dalam akumulasi Total Saldo Bersih
                    </label>
                </div>

                <div class="pt-3 flex items-center justify-end gap-2 border-t">
                    <button type="button" @click="showModal = false" class="h-10 px-4 rounded-lg border bg-card hover:bg-muted text-sm font-medium">
                        Batal
                    </button>
                    <button type="submit" :disabled="form.processing"
                        class="h-10 px-5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-sm">
                        {{ editingId ? 'Simpan Perubahan' : 'Buat Akun' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

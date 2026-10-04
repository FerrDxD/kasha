<script setup lang="ts">
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useMoney } from '@/composables/useMoney';
import type { Account, Transaction, Category, PaginatedData } from '@/types';
import {
    Plus,
    Pencil,
    Trash2,
    Search,
    X,
    UploadCloud,
    Download,
    TrendingUp,
    TrendingDown,
    ArrowLeftRight,
    Building2,
    Tag as TagIcon,
    Calendar,
    ChevronDown,
} from '@lucide/vue';

const props = defineProps<{
    transactions: PaginatedData<Transaction>;
    accounts: Pick<Account, 'id' | 'name' | 'type'>[];
    categories: Pick<Category, 'id' | 'name' | 'type' | 'color'>[];
    filters: {
        search?: string;
        account_id?: string;
        category_id?: string;
        type?: string;
        date_from?: string;
        date_to?: string;
    };
}>();

const { format, compact } = useMoney();

// Metrics computed from current page data (or totals)
const totalIncome = computed(() =>
    props.transactions.data.filter(t => t.type === 'income').reduce((sum, t) => sum + t.amount, 0)
);
const countIncome = computed(() =>
    props.transactions.data.filter(t => t.type === 'income').length
);
const totalExpense = computed(() =>
    props.transactions.data.filter(t => t.type === 'expense').reduce((sum, t) => sum + t.amount, 0)
);
const countExpense = computed(() =>
    props.transactions.data.filter(t => t.type === 'expense').length
);
const totalTransfer = computed(() =>
    props.transactions.data.filter(t => t.type === 'transfer').reduce((sum, t) => sum + t.amount, 0)
);
const netFlow = computed(() => totalIncome.value - totalExpense.value);

// Filter state
const filters = ref({ ...props.filters });

function applyFilters() {
    router.get('/transactions', filters.value, { preserveState: true, replace: true });
}

function clearFilters() {
    filters.value = {};
    router.get('/transactions', {}, { preserveState: true, replace: true });
}

// Modal State: Add / Edit Transaction
const showModal = ref(false);
const editingId = ref<string | null>(null);

const form = useForm({
    type: 'expense' as 'expense' | 'income' | 'transfer',
    amount: '' as unknown as number,
    account_id: '',
    to_account_id: '',
    category_id: '',
    occurred_on: new Date().toISOString().split('T')[0],
    payee: '',
    note: '',
});

function openAdd(type: 'expense' | 'income' | 'transfer' = 'expense') {
    form.reset();
    form.type = type;
    form.occurred_on = new Date().toISOString().split('T')[0];
    editingId.value = null;
    showModal.value = true;
}

function openEdit(tx: Transaction) {
    editingId.value = tx.id;
    form.type = tx.type as 'expense' | 'income' | 'transfer';
    form.amount = tx.amount;
    form.account_id = tx.account_id;
    form.to_account_id = '';
    form.category_id = tx.category_id ?? '';
    form.occurred_on = tx.occurred_on;
    form.payee = tx.payee ?? '';
    form.note = tx.note ?? '';
    showModal.value = true;
}

function submitForm() {
    if (form.type === 'transfer' && !editingId.value) {
        form.post('/transactions/transfer', {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
        return;
    }

    if (editingId.value) {
        form.put(`/transactions/${editingId.value}`, {
            onSuccess: () => { showModal.value = false; },
        });
    } else {
        form.post('/transactions', {
            onSuccess: () => { showModal.value = false; form.reset(); },
        });
    }
}

function destroy(tx: Transaction) {
    if (!confirm('Hapus transaksi ini?')) return;
    router.delete(`/transactions/${tx.id}`, { preserveScroll: true });
}

const activeTypeStyle = computed(() => {
    switch (form.type) {
        case 'expense': return { bg: 'bg-rose-600 text-white', prefix: '▼ Rp' };
        case 'income': return { bg: 'bg-emerald-600 text-white', prefix: '▲ Rp' };
        case 'transfer': return { bg: 'bg-slate-700 text-white', prefix: '↔ Rp' };
    }
});
</script>

<template>
    <Head title="Transaksi" />

    <div class="max-w-[1200px] mx-auto p-4 sm:p-6 lg:p-6 flex flex-col gap-6">
        <!-- Header Section -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Transaksi</h1>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 text-xs font-semibold">
                        Live Feed
                    </span>
                </div>
                <p class="text-sm text-muted-foreground">Kelola riwayat pemasukan, pengeluaran, dan transfer akun keuangan Anda</p>
            </div>

            <!-- Page Actions -->
            <div class="flex flex-wrap items-center gap-2">
                <Link href="/transactions/import" class="h-10 px-3.5 rounded-lg border bg-card hover:bg-muted text-slate-800 dark:text-slate-200 text-sm font-medium flex items-center gap-2 shadow-xs transition-colors">
                    <UploadCloud class="h-4 w-4 text-muted-foreground" />
                    <span>Import CSV</span>
                </Link>
                <button class="h-10 px-3.5 rounded-lg border bg-card hover:bg-muted text-slate-800 dark:text-slate-200 text-sm font-medium flex items-center gap-2 shadow-xs transition-colors" type="button">
                    <Download class="h-4 w-4 text-muted-foreground" />
                    <span>Export</span>
                </button>
                <button @click="openAdd('expense')" type="button"
                    class="h-10 px-4 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium flex items-center gap-2 shadow-sm transition-all active:scale-[0.98]">
                    <Plus class="h-4 w-4" />
                    <span>Tambah Transaksi</span>
                </button>
            </div>
        </div>

        <!-- Key Metrics Strip (4 Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Pemasukan -->
            <div class="rounded-xl border bg-card p-4 shadow-sm flex flex-col justify-between gap-2.5">
                <div class="flex items-center justify-between text-muted-foreground text-xs uppercase tracking-wider font-semibold">
                    <span>Total Pemasukan</span>
                    <TrendingUp class="h-4 w-4 text-emerald-600" />
                </div>
                <div class="flex items-baseline justify-between">
                    <span class="text-lg font-bold text-emerald-600 tabular-nums">+{{ format(totalIncome) }}</span>
                    <span class="text-xs text-muted-foreground">{{ countIncome }} Transaksi</span>
                </div>
                <div class="h-1.5 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                    <div class="h-full bg-emerald-600 rounded-full" :style="`width: ${totalIncome > 0 ? 100 : 0}%;`" />
                </div>
            </div>

            <!-- Pengeluaran -->
            <div class="rounded-xl border bg-card p-4 shadow-sm flex flex-col justify-between gap-2.5">
                <div class="flex items-center justify-between text-muted-foreground text-xs uppercase tracking-wider font-semibold">
                    <span>Total Pengeluaran</span>
                    <TrendingDown class="h-4 w-4 text-rose-600" />
                </div>
                <div class="flex items-baseline justify-between">
                    <span class="text-lg font-bold text-rose-600 tabular-nums">-{{ format(totalExpense) }}</span>
                    <span class="text-xs text-muted-foreground">{{ countExpense }} Transaksi</span>
                </div>
                <div class="h-1.5 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                    <div class="h-full bg-rose-600 rounded-full" :style="`width: ${totalIncome > 0 ? Math.min(100, Math.round((totalExpense / totalIncome) * 100)) : 0}%;`" />
                </div>
            </div>

            <!-- Transfer -->
            <div class="rounded-xl border bg-card p-4 shadow-sm flex flex-col justify-between gap-2.5">
                <div class="flex items-center justify-between text-muted-foreground text-xs uppercase tracking-wider font-semibold">
                    <span>Transfer / Mutasi</span>
                    <ArrowLeftRight class="h-4 w-4 text-slate-500" />
                </div>
                <div class="flex items-baseline justify-between">
                    <span class="text-lg font-bold text-slate-800 dark:text-slate-200 tabular-nums">{{ format(totalTransfer) }}</span>
                    <span class="text-xs text-muted-foreground">Internal</span>
                </div>
                <div class="h-1.5 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                    <div class="h-full bg-slate-400 rounded-full" style="width: 20%;" />
                </div>
            </div>

            <!-- Arus Kas Bersih -->
            <div class="rounded-xl border bg-card p-4 shadow-sm flex flex-col justify-between gap-2.5">
                <div class="flex items-center justify-between text-muted-foreground text-xs uppercase tracking-wider font-semibold">
                    <span>Arus Kas Bersih</span>
                    <Building2 class="h-4 w-4 text-emerald-600" />
                </div>
                <div class="flex items-baseline justify-between">
                    <span class="text-lg font-bold tabular-nums" :class="netFlow >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                        {{ netFlow >= 0 ? '+' : '-' }}{{ format(Math.abs(netFlow)) }}
                    </span>
                    <span class="text-xs font-medium" :class="netFlow >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                        {{ netFlow >= 0 ? '▲ Sehat' : '▼ Defisit' }}
                    </span>
                </div>
                <div class="h-1.5 w-full bg-emerald-50 dark:bg-emerald-950/40 rounded-full overflow-hidden">
                    <div class="h-full bg-emerald-600 rounded-full" :style="`width: ${netFlow > 0 ? 100 : 0}%;`" />
                </div>
            </div>
        </div>

        <!-- Filter Toolbar -->
        <div class="rounded-xl border bg-card p-4 shadow-sm flex flex-col gap-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-2.5 items-center">
                <!-- Search -->
                <div class="lg:col-span-4 relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <input v-model="filters.search" @keyup.enter="applyFilters"
                        placeholder="Cari transaksi, merchant, catatan..."
                        class="w-full h-10 pl-9 pr-3 rounded-lg border bg-background text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-emerald-600" />
                </div>

                <!-- Date Range -->
                <div class="lg:col-span-3 relative">
                    <button class="w-full h-10 px-3 rounded-lg border bg-background text-left text-sm flex items-center justify-between hover:bg-muted transition-colors" type="button">
                        <div class="flex items-center gap-2 truncate">
                            <Calendar class="h-4 w-4 text-muted-foreground" />
                            <span class="tabular-nums">{{ filters.date_from || 'Dari' }} - {{ filters.date_to || 'Sampai' }}</span>
                        </div>
                        <ChevronDown class="h-4 w-4 text-muted-foreground" />
                    </button>
                </div>

                <!-- Account -->
                <div class="lg:col-span-2 relative">
                    <select v-model="filters.account_id" @change="applyFilters"
                        class="w-full h-10 pl-3 pr-8 rounded-lg border bg-background text-sm appearance-none focus:outline-none focus:ring-1 focus:ring-emerald-600">
                        <option value="">Semua Akun</option>
                        <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }}</option>
                    </select>
                    <ChevronDown class="absolute right-2.5 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
                </div>

                <!-- Category -->
                <div class="lg:col-span-2 relative">
                    <select v-model="filters.category_id" @change="applyFilters"
                        class="w-full h-10 pl-3 pr-8 rounded-lg border bg-background text-sm appearance-none focus:outline-none focus:ring-1 focus:ring-emerald-600">
                        <option value="">Semua Kategori</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                    <ChevronDown class="absolute right-2.5 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
                </div>

                <!-- Tag -->
                <div class="lg:col-span-1 relative">
                    <select v-model="filters.tag" @change="applyFilters"
                        class="w-full h-10 pl-2.5 pr-6 rounded-lg border bg-background text-sm appearance-none focus:outline-none focus:ring-1 focus:ring-emerald-600">
                        <option value="">Tag</option>
                        <option value="rutin">#rutin</option>
                        <option value="online">#online</option>
                        <option value="kantor">#kantor</option>
                    </select>
                    <ChevronDown class="absolute right-1 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
                </div>
            </div>

            <!-- Active filter chips -->
            <div class="flex flex-wrap items-center justify-between gap-2 pt-1 border-t text-xs">
                <div class="flex items-center gap-2">
                    <span class="text-muted-foreground font-medium">Filter Aktif:</span>
                    <span v-if="filters.date_from || filters.date_to" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200">
                        <Calendar class="h-3 w-3 text-muted-foreground" />
                        <span>{{ filters.date_from || '...' }} - {{ filters.date_to || '...' }}</span>
                        <X class="h-3 w-3 hover:text-rose-600 cursor-pointer" @click="filters.date_from = ''; filters.date_to = ''" />
                    </span>
                    <span v-if="filters.account_id" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200">
                        <span>{{ accounts.find(a => a.id === filters.account_id)?.name }}</span>
                        <X class="h-3 w-3 hover:text-rose-600 cursor-pointer" @click="filters.account_id = ''" />
                    </span>
                    <span v-if="filters.category_id" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200">
                        <span>{{ categories.find(c => c.id === filters.category_id)?.name }}</span>
                        <X class="h-3 w-3 hover:text-rose-600 cursor-pointer" @click="filters.category_id = ''" />
                    </span>
                    <button @click="clearFilters" class="text-emerald-600 hover:underline font-semibold ml-1">
                        Reset Filter
                    </button>
                </div>
                <span class="text-muted-foreground tabular-nums">{{ transactions.total }} transaksi ditemukan</span>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="border-b bg-slate-50/60 dark:bg-slate-900/60 text-xs text-muted-foreground uppercase tracking-wider font-semibold">
                        <tr>
                            <th class="px-4 py-3.5 cursor-pointer hover:text-slate-900 dark:hover:text-slate-100 select-none">Tanggal</th>
                            <th class="px-4 py-3.5 cursor-pointer hover:text-slate-900 dark:hover:text-slate-100 select-none">Merchant / Deskripsi</th>
                            <th class="px-4 py-3.5">Kategori</th>
                            <th class="px-4 py-3.5">Akun</th>
                            <th class="px-4 py-3.5">Tag</th>
                            <th class="px-4 py-3.5 text-right cursor-pointer hover:text-slate-900 dark:hover:text-slate-100 select-none">Jumlah</th>
                            <th class="px-4 py-3.5 text-center w-16">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="tx in transactions.data" :key="tx.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-900/40 transition-colors">
                            <td class="px-4 py-3.5 whitespace-nowrap text-xs text-muted-foreground tabular-nums">
                                {{ new Date(tx.occurred_on).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 font-bold text-xs bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                        {{ (tx.payee || tx.category?.name || 'TX').substring(0, 2).toUpperCase() }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-medium text-slate-900 dark:text-slate-100 truncate">
                                            {{ tx.payee || tx.category?.name || 'Tanpa Nama' }}
                                        </p>
                                        <p v-if="tx.note" class="text-xs text-muted-foreground truncate">{{ tx.note }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-xs font-medium text-slate-700 dark:text-slate-300">
                                    <span class="font-bold text-xs">{{ tx.type === 'income' ? '▲' : tx.type === 'expense' ? '▼' : '↔' }}</span>
                                    {{ tx.category?.name || '—' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-xs text-slate-700 dark:text-slate-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-600"></span>
                                    {{ tx.account?.name }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span v-if="tx.tag" class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-xs font-mono">#{{ tx.tag }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-right whitespace-nowrap font-semibold tabular-nums"
                                :class="tx.type === 'income' ? 'text-emerald-600' : tx.type === 'expense' ? 'text-rose-600' : 'text-slate-600'">
                                {{ tx.type === 'income' ? '+' : tx.type === 'expense' ? '-' : '' }}{{ format(tx.amount) }} <span class="text-xs">{{ tx.type === 'income' ? '▲' : tx.type === 'expense' ? '▼' : '' }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <button class="p-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-muted-foreground hover:text-slate-900 dark:hover:text-slate-100 transition-colors" type="button">
                                    <Pencil class="h-4 w-4" @click="openEdit(tx)" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!transactions.data.length">
                            <td colspan="7" class="px-4 py-12 text-center text-sm text-muted-foreground">
                                Tidak ada transaksi yang sesuai filter.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            <div v-if="transactions.last_page > 1" class="p-4 border-t flex items-center justify-between text-xs text-muted-foreground">
                <span>Halaman {{ transactions.current_page }} dari {{ transactions.last_page }}</span>
                <div class="flex gap-1">
                    <template v-for="(link, i) in transactions.links" :key="i">
                        <Link v-if="link.url" :href="link.url"
                            class="px-3 py-1 rounded-md border text-xs transition-colors"
                            :class="link.active ? 'bg-emerald-600 text-white border-emerald-600' : 'hover:bg-muted'"
                            v-html="link.label" />
                        <span v-else class="px-3 py-1 rounded-md text-slate-400 dark:text-slate-600" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Tambah / Edit Transaksi (Matching kasha_tambah_transaksi_modal) -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4" @click.self="showModal = false">
        <div class="bg-card rounded-2xl border shadow-2xl w-full max-w-[500px] overflow-hidden animate-in fade-in zoom-in-95 duration-200">
            <!-- Modal Header -->
            <div class="px-6 pt-5 pb-4 flex items-center justify-between border-b bg-slate-50/50 dark:bg-slate-900/50">
                <div class="flex items-center gap-2">
                    <div class="w-2.5 h-2.5 rounded-full bg-emerald-600"></div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                        {{ editingId ? 'Edit Transaksi' : 'Tambah Transaksi' }}
                    </h2>
                </div>
                <button @click="showModal = false" type="button" class="w-8 h-8 rounded-full flex items-center justify-center text-muted-foreground hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <X class="h-4 w-4" />
                </button>
            </div>

            <!-- Segmented Control: Type Picker -->
            <div class="px-6 pt-4 pb-2">
                <div class="grid grid-cols-3 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl gap-1">
                    <button type="button" @click="form.type = 'expense'"
                        class="py-2 text-center rounded-lg text-xs font-semibold transition-all flex items-center justify-center gap-1.5"
                        :class="form.type === 'expense' ? 'bg-rose-600 text-white shadow-xs' : 'text-muted-foreground hover:text-slate-900 dark:hover:text-white'">
                        <span>▼</span> Pengeluaran
                    </button>
                    <button type="button" @click="form.type = 'income'"
                        class="py-2 text-center rounded-lg text-xs font-semibold transition-all flex items-center justify-center gap-1.5"
                        :class="form.type === 'income' ? 'bg-emerald-600 text-white shadow-xs' : 'text-muted-foreground hover:text-slate-900 dark:hover:text-white'">
                        <span>▲</span> Pemasukan
                    </button>
                    <button type="button" @click="form.type = 'transfer'"
                        class="py-2 text-center rounded-lg text-xs font-semibold transition-all flex items-center justify-center gap-1.5"
                        :class="form.type === 'transfer' ? 'bg-slate-700 text-white shadow-xs' : 'text-muted-foreground hover:text-slate-900 dark:hover:text-white'">
                        <span>↔</span> Transfer
                    </button>
                </div>
            </div>

            <!-- Scrollable Form Body -->
            <form @submit.prevent="submitForm" class="px-6 py-4 space-y-4 max-h-[calc(85vh-160px)] overflow-y-auto">
                <!-- Big Money Input Field -->
                <div class="rounded-xl border bg-slate-50/60 dark:bg-slate-900/60 p-4 flex flex-col items-center justify-center text-center">
                    <label class="text-xs uppercase tracking-wider text-muted-foreground font-semibold mb-1">Nominal Transaksi</label>
                    <div class="relative flex items-center justify-center w-full">
                        <div class="flex items-baseline justify-center">
                            <span class="text-xl font-bold mr-1.5 tabular-nums"
                                :class="form.type === 'income' ? 'text-emerald-600' : form.type === 'expense' ? 'text-rose-600' : 'text-slate-700 dark:text-slate-300'">
                                {{ activeTypeStyle.prefix }}
                            </span>
                            <input v-model="form.amount" type="number" min="1" required placeholder="0"
                                class="w-48 text-center text-3xl font-bold bg-transparent focus:outline-none tabular-nums tracking-tight text-slate-900 dark:text-white" />
                        </div>
                    </div>
                </div>

                <!-- Account Selection / Transfer Split -->
                <div v-if="form.type === 'transfer'" class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium text-slate-700 dark:text-slate-300">Dari Rekening</label>
                        <select v-model="form.account_id" required class="w-full h-10 px-3 rounded-lg border bg-background text-sm mt-1 focus:ring-1 focus:ring-emerald-600">
                            <option value="">Pilih Asal...</option>
                            <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-700 dark:text-slate-300">Ke Rekening</label>
                        <select v-model="form.to_account_id" required class="w-full h-10 px-3 rounded-lg border bg-background text-sm mt-1 focus:ring-1 focus:ring-emerald-600">
                            <option value="">Pilih Tujuan...</option>
                            <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }}</option>
                        </select>
                    </div>
                </div>

                <div v-else class="space-y-3">
                    <div>
                        <label class="text-xs font-medium text-slate-700 dark:text-slate-300">Sumber Rekening / Akun</label>
                        <select v-model="form.account_id" required class="w-full h-10 px-3 rounded-lg border bg-background text-sm mt-1 focus:ring-1 focus:ring-emerald-600">
                            <option value="">Pilih Akun...</option>
                            <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-medium text-slate-700 dark:text-slate-300">Kategori</label>
                        <select v-model="form.category_id" class="w-full h-10 px-3 rounded-lg border bg-background text-sm mt-1 focus:ring-1 focus:ring-emerald-600">
                            <option value="">Tanpa Kategori</option>
                            <option v-for="c in categories.filter(c => c.type === form.type)" :key="c.id" :value="c.id">
                                {{ c.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Payee & Date -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium text-slate-700 dark:text-slate-300">Merchant / Payee</label>
                        <input v-model="form.payee" placeholder="Contoh: Gojek, Netflix"
                            class="w-full h-10 px-3 rounded-lg border bg-background text-sm mt-1 focus:ring-1 focus:ring-emerald-600" />
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-700 dark:text-slate-300">Tanggal</label>
                        <input v-model="form.occurred_on" type="date" required
                            class="w-full h-10 px-3 rounded-lg border bg-background text-sm mt-1 focus:ring-1 focus:ring-emerald-600" />
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <label class="text-xs font-medium text-slate-700 dark:text-slate-300">Catatan Tambahan (Opsional)</label>
                    <textarea v-model="form.note" rows="2" placeholder="Tulis rincian atau keperluan transaksi..."
                        class="w-full p-2.5 rounded-lg border bg-background text-sm mt-1 focus:ring-1 focus:ring-emerald-600" />
                </div>

                <!-- Action Buttons -->
                <div class="pt-2 flex items-center justify-end gap-2 border-t">
                    <button type="button" @click="showModal = false"
                        class="h-10 px-4 rounded-lg border bg-card hover:bg-muted text-sm font-medium transition-colors">
                        Batal
                    </button>
                    <button type="submit" :disabled="form.processing"
                        class="h-10 px-5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold transition-all shadow-sm">
                        {{ editingId ? 'Simpan Perubahan' : 'Simpan Transaksi' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

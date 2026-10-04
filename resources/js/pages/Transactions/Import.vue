<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useMoney } from '@/composables/useMoney';
import { Upload, CheckCircle2, Map, Eye, PartyPopper, ChevronRight, FileText, X, Plus, AlertCircle } from '@lucide/vue';
import { Button } from '@/components/ui/button';

interface AccountItem { id: string; name: string; type: string; }
interface CategoryItem { id: string; name: string; type: string; }

const props = defineProps<{
    accounts?: AccountItem[];
    categories?: CategoryItem[];
}>();

const { format } = useMoney();

// ─── Wizard step state ───────────────────────────────────────────────────────
const step = ref<1 | 2 | 3 | 4>(1);

// ─── Step 1: File upload ─────────────────────────────────────────────────────
const fileRef = ref<HTMLInputElement | null>(null);
const uploadedFile = ref<File | null>(null);
const rawRows = ref<string[][]>([]);
const headers = ref<string[]>([]);

function onFileDrop(e: DragEvent) {
    const file = e.dataTransfer?.files[0];
    if (file) processFile(file);
}

function onFileChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) processFile(file);
}

function processFile(file: File) {
    uploadedFile.value = file;
    const reader = new FileReader();
    reader.onload = (ev) => {
        const text = ev.target?.result as string;
        const lines = text.trim().split('\n').map(l => l.split(/[,;|]/).map(c => c.trim().replace(/^"|"$/g, '')));
        headers.value = lines[0] ?? [];
        rawRows.value = lines.slice(1).filter(r => r.some(c => c));
        step.value = 2;
    };
    reader.readAsText(file);
}

// ─── Step 2: Column mapping ───────────────────────────────────────────────────
const KASHA_FIELDS = ['tanggal', 'deskripsi', 'debit', 'kredit', 'saldo', 'skip'];

const mapping = ref<Record<string, string>>({});

// Auto-detect common column names
function autoDetect() {
    const guesses: Record<string, string> = {};
    headers.value.forEach(h => {
        const lh = h.toLowerCase();
        if (!guesses[h]) {
            if (lh.includes('tanggal') || lh.includes('date')) guesses[h] = 'tanggal';
            else if (lh.includes('debit') || lh.includes('keluar')) guesses[h] = 'debit';
            else if (lh.includes('kredit') || lh.includes('masuk')) guesses[h] = 'kredit';
            else if (lh.includes('keterangan') || lh.includes('deskripsi') || lh.includes('description') || lh.includes('remark') || lh.includes('memo')) guesses[h] = 'deskripsi';
            else if (lh.includes('saldo') || lh.includes('balance')) guesses[h] = 'saldo';
            else guesses[h] = 'skip';
        }
    });
    mapping.value = guesses;
}

if (headers.value.length) autoDetect();

// ─── Step 3: Preview & Verify ─────────────────────────────────────────────────
const targetAccountId = ref(props.accounts?.[0]?.id ?? '');
const defaultCategoryId = ref(props.categories?.filter(c => c.type === 'expense')[0]?.id ?? '');
const dedupeEnabled = ref(true);

interface ParsedRow {
    date: string;
    description: string;
    amount: number;
    type: 'income' | 'expense';
    isDuplicate: boolean;
    selected: boolean;
}

const parsedRows = computed<ParsedRow[]>(() => {
    const dateCol   = Object.entries(mapping.value).find(([, v]) => v === 'tanggal')?.[0];
    const descCol   = Object.entries(mapping.value).find(([, v]) => v === 'deskripsi')?.[0];
    const debitCol  = Object.entries(mapping.value).find(([, v]) => v === 'debit')?.[0];
    const creditCol = Object.entries(mapping.value).find(([, v]) => v === 'kredit')?.[0];

    return rawRows.value.map(row => {
        const idxDate  = dateCol  ? headers.value.indexOf(dateCol)  : -1;
        const idxDesc  = descCol  ? headers.value.indexOf(descCol)  : -1;
        const idxDebit = debitCol ? headers.value.indexOf(debitCol) : -1;
        const idxCred  = creditCol? headers.value.indexOf(creditCol): -1;

        const date = idxDate >= 0 ? row[idxDate] : '';
        const desc = idxDesc >= 0 ? row[idxDesc] : row[1] ?? '';
        const debit  = idxDebit >= 0 ? parseAmount(row[idxDebit])  : 0;
        const credit = idxCred  >= 0 ? parseAmount(row[idxCred])   : 0;

        const amount = debit > 0 ? debit : credit;
        const type: 'income'|'expense' = credit > 0 ? 'income' : 'expense';

        return { date, description: desc, amount, type, isDuplicate: false, selected: true };
    });
});

function parseAmount(s: string): number {
    if (!s) return 0;
    return parseFloat(s.replace(/[^0-9.]/g, '')) || 0;
}

const selectedRows = computed(() => parsedRows.value.filter(r => r.selected));
const totalIncome  = computed(() => selectedRows.value.filter(r => r.type === 'income').reduce((a, r) => a + r.amount, 0));
const totalExpense = computed(() => selectedRows.value.filter(r => r.type === 'expense').reduce((a, r) => a + r.amount, 0));

// ─── Step 4: Perform import ───────────────────────────────────────────────────
const importing = ref(false);
const importedCount = ref(0);

async function runImport() {
    importing.value = true;
    const rows = selectedRows.value;
    let count = 0;

    // Submit each row as a transaction via fetch
    for (const row of rows) {
        if (row.amount <= 0) continue;
        try {
            await fetch('/transactions', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') as HTMLMetaElement)?.content ?? '' },
                body: JSON.stringify({
                    account_id: targetAccountId.value,
                    category_id: defaultCategoryId.value || null,
                    type: row.type,
                    amount: Math.round(row.amount),
                    payee: row.description,
                    transacted_at: row.date || new Date().toISOString().split('T')[0],
                    note: 'Imported via CSV',
                }),
            });
            count++;
        } catch { /* skip */ }
    }

    importedCount.value = count;
    importing.value = false;
    step.value = 4;
}

const STEPS = [
    { n: 1, label: 'Unggah Berkas', icon: Upload },
    { n: 2, label: 'Pemetaan Kolom', icon: Map },
    { n: 3, label: 'Pratinjau & Verifikasi', icon: Eye },
    { n: 4, label: 'Impor Selesai', icon: PartyPopper },
];
</script>

<template>
    <Head title="Import CSV - KASHA" />

    <div class="w-full max-w-[1240px] mx-auto px-4 sm:px-6 py-6 flex flex-col gap-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <div class="flex items-center gap-1.5 text-xs text-muted-foreground mb-1">
                    <span>Import CSV</span>
                    <ChevronRight class="h-3 w-3" />
                    <span class="text-slate-800 dark:text-slate-200 font-medium">Wizard Pemetaan &amp; Verifikasi</span>
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 flex items-center gap-2.5">
                    Import Mutasi Rekening (CSV)
                    <span class="bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 text-xs font-semibold px-2.5 py-0.5 rounded-full">Automasi Cerdas</span>
                </h1>
            </div>
        </div>

        <!-- Step Progress -->
        <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div v-for="s in STEPS" :key="s.n" class="flex items-center gap-2.5 relative" :class="{ 'bg-emerald-50 dark:bg-emerald-950/40 px-3 py-2 rounded-lg': step === s.n }">
                    <div
                        :class="[
                            'w-8 h-8 rounded-full flex items-center justify-center shrink-0 text-sm font-bold transition-all',
                            step > s.n  ? 'bg-emerald-600 text-white'       :
                            step === s.n ? 'bg-emerald-600 text-white shadow-sm' :
                                           'bg-slate-100 dark:bg-slate-800 text-muted-foreground'
                        ]"
                    >
                        <CheckCircle2 v-if="step > s.n" class="h-4 w-4" />
                        <component v-else :is="s.icon" class="h-4 w-4" />
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-[11px] font-medium" :class="step === s.n ? 'text-emerald-600 dark:text-emerald-400' : step > s.n ? 'text-emerald-600 dark:text-emerald-400' : 'text-muted-foreground'">
                            Langkah {{ s.n }} {{ step > s.n ? '• Selesai' : step === s.n ? '• Aktif' : '' }}
                        </span>
                        <span class="text-xs font-semibold truncate" :class="step === s.n ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-700 dark:text-slate-300'">
                            {{ s.label }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── Step 1: Upload ─────────────────────────────────────────────── -->
        <div v-if="step === 1" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-8 flex flex-col items-center gap-6">
            <div
                class="w-full max-w-md border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-xl p-10 flex flex-col items-center gap-4 cursor-pointer hover:border-emerald-500 hover:bg-emerald-50/30 dark:hover:bg-emerald-950/20 transition-all"
                @dragover.prevent
                @drop.prevent="onFileDrop"
                @click="fileRef?.click()"
            >
                <div class="w-16 h-16 rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 flex items-center justify-center">
                    <Upload class="h-7 w-7" />
                </div>
                <div class="text-center">
                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Seret &amp; lepas file CSV di sini</p>
                    <p class="text-xs text-muted-foreground mt-1">atau klik untuk memilih file (CSV, XLS, XLSX, TXT)</p>
                </div>
                <input ref="fileRef" type="file" accept=".csv,.txt,.xls,.xlsx" class="hidden" @change="onFileChange" />
            </div>

            <div class="text-center max-w-md">
                <p class="text-xs text-muted-foreground">
                    Format CSV yang didukung: BCA, Mandiri, BNI, BRI, CIMB Niaga, GoPay, OVO, dan format ekspor mutasi umum lainnya.
                </p>
            </div>
        </div>

        <!-- ─── Step 2: Column Mapping ─────────────────────────────────────── -->
        <div v-if="step === 2" class="flex flex-col gap-4">
            <!-- File info banner -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 flex items-center gap-3 shadow-sm">
                <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-emerald-600">
                    <FileText class="h-5 w-5" />
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ uploadedFile?.name }}</p>
                    <p class="text-xs text-muted-foreground">{{ rawRows.length }} baris data terbaca · {{ headers.length }} kolom terdeteksi</p>
                </div>
                <button @click="step = 1; uploadedFile = null; rawRows = []; headers = []" class="ml-auto p-2 text-muted-foreground hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition-colors">
                    <X class="h-4 w-4" />
                </button>
            </div>

            <!-- Mapping table -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Pemetaan Kolom CSV → KASHA</h2>
                        <p class="text-xs text-muted-foreground">Pasangkan kolom dari file CSV Anda dengan field transaksi KASHA.</p>
                    </div>
                    <button @click="autoDetect" class="text-xs text-emerald-600 hover:text-emerald-700 font-medium underline">
                        Auto-Deteksi Ulang
                    </button>
                </div>

                <div class="space-y-3">
                    <div v-for="h in headers" :key="h" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 items-center bg-slate-50 dark:bg-slate-800/40 p-3 rounded-lg">
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-mono">{{ h }}</span>
                            <span class="text-xs text-muted-foreground">→</span>
                        </div>
                        <select
                            v-model="mapping[h]"
                            class="w-full h-9 px-2.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        >
                            <option value="tanggal">Tanggal</option>
                            <option value="deskripsi">Deskripsi / Keterangan</option>
                            <option value="debit">Debit / Keluar</option>
                            <option value="kredit">Kredit / Masuk</option>
                            <option value="saldo">Saldo</option>
                            <option value="skip">— Skip (Abaikan) —</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 flex justify-end gap-2">
                    <Button variant="outline" size="sm" @click="step = 1">Kembali</Button>
                    <Button @click="step = 3" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs">
                        Lanjut ke Pratinjau →
                    </Button>
                </div>
            </div>
        </div>

        <!-- ─── Step 3: Preview & Verify ──────────────────────────────────── -->
        <div v-if="step === 3" class="flex flex-col gap-4">
            <!-- Target account + stats -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 flex flex-col xl:flex-row xl:items-center justify-between gap-4 shadow-sm">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-muted-foreground font-medium shrink-0">Rekening Tujuan:</span>
                        <select v-model="targetAccountId" class="h-9 px-3 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-muted-foreground font-medium shrink-0">Kategori Default:</span>
                        <select v-model="defaultCategoryId" class="h-9 px-3 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            <option value="">Tanpa Kategori</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-medium">
                        {{ parsedRows.length }} Baris Terbaca
                    </span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 text-xs font-semibold">
                        <CheckCircle2 class="h-3 w-3" /> {{ selectedRows.length }} Valid
                    </span>
                </div>
            </div>

            <!-- Summary -->
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm text-center">
                    <p class="text-xs text-muted-foreground font-medium uppercase tracking-wider">Total Pemasukan</p>
                    <p class="text-xl font-bold text-emerald-600 tabular-nums mt-1">+{{ format(totalIncome) }}</p>
                </div>
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm text-center">
                    <p class="text-xs text-muted-foreground font-medium uppercase tracking-wider">Total Pengeluaran</p>
                    <p class="text-xl font-bold text-rose-600 tabular-nums mt-1">-{{ format(totalExpense) }}</p>
                </div>
            </div>

            <!-- Preview table -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100 dark:border-slate-800">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Pratinjau Transaksi ({{ parsedRows.length }} baris)</h2>
                </div>

                <div v-if="!parsedRows.length" class="py-10 text-center text-xs text-muted-foreground">
                    Tidak ada data yang dapat diparsing. Periksa pemetaan kolom.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/60 text-muted-foreground uppercase tracking-wider font-semibold">
                                <th class="py-2.5 px-4 w-10"></th>
                                <th class="py-2.5 px-4">Tanggal</th>
                                <th class="py-2.5 px-4">Deskripsi</th>
                                <th class="py-2.5 px-4 text-right">Nominal</th>
                                <th class="py-2.5 px-4 text-center">Tipe</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="(row, i) in parsedRows.slice(0, 30)" :key="i" class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="py-2.5 px-4">
                                    <input type="checkbox" v-model="row.selected" class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                                </td>
                                <td class="py-2.5 px-4 text-muted-foreground tabular-nums">{{ row.date || '—' }}</td>
                                <td class="py-2.5 px-4 text-slate-800 dark:text-slate-200 max-w-[300px] truncate" :title="row.description">{{ row.description || '—' }}</td>
                                <td class="py-2.5 px-4 text-right font-semibold tabular-nums" :class="row.type === 'income' ? 'text-emerald-600' : 'text-rose-600'">
                                    {{ row.type === 'income' ? '+' : '-' }}{{ format(row.amount) }}
                                </td>
                                <td class="py-2.5 px-4 text-center">
                                    <span :class="['text-[11px] px-2 py-0.5 rounded-full font-semibold', row.type === 'income' ? 'bg-emerald-50 dark:bg-emerald-950 text-emerald-700' : 'bg-rose-50 dark:bg-rose-950 text-rose-600']">
                                        {{ row.type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="parsedRows.length > 30" class="text-center py-3 text-xs text-muted-foreground border-t border-slate-100 dark:border-slate-800">
                        … dan {{ parsedRows.length - 30 }} baris lainnya
                    </div>
                </div>

                <div class="p-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <Button variant="outline" size="sm" @click="step = 2">Kembali</Button>
                    <Button
                        @click="runImport"
                        :disabled="importing || selectedRows.length === 0"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs"
                    >
                        {{ importing ? 'Mengimpor...' : `Impor ${selectedRows.length} Transaksi →` }}
                    </Button>
                </div>
            </div>
        </div>

        <!-- ─── Step 4: Done ───────────────────────────────────────────────── -->
        <div v-if="step === 4" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-12 flex flex-col items-center gap-6 text-center">
            <div class="w-20 h-20 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-600 flex items-center justify-center">
                <PartyPopper class="h-10 w-10" />
            </div>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Impor Berhasil!</h2>
                <p class="text-sm text-muted-foreground mt-2">
                    <strong class="text-emerald-600 tabular-nums">{{ importedCount }} transaksi</strong> berhasil diimpor dari file CSV.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <Button variant="outline" @click="step = 1; uploadedFile = null; rawRows = []; headers = []">
                    Import File Lainnya
                </Button>
                <Button @click="router.visit('/transactions')" class="bg-emerald-600 hover:bg-emerald-700 text-white">
                    Lihat Transaksi →
                </Button>
            </div>
        </div>
    </div>
</template>

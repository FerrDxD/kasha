<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useMoney } from '@/composables/useMoney';
import { TrendingUp, TrendingDown, Wallet, PiggyBank, ChevronLeft, ChevronRight, BarChart2, Info } from '@lucide/vue';

interface CategoryBreakdown {
    category_id: string | null;
    category_name: string;
    color: string;
    total: number;
    percent: number;
}

interface TopCategory {
    category_id: string | null;
    category_name: string;
    color: string;
    icon: string;
    total: number;
    limit: number;
    percent: number | null;
    vs_prev: number;
    status: 'ok' | 'warning' | 'over';
}

interface Kpi {
    income: number;
    expense: number;
    net_flow: number;
    saving_rate: number;
    prev_income: number;
    prev_expense: number;
    prev_net: number;
}

const props = defineProps<{
    year: number;
    month: number;
    kpi: Kpi;
    categoryBreakdown: CategoryBreakdown[];
    topCategories: TopCategory[];
}>();

const { format } = useMoney();

const monthNames = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

const currentLabel = computed(() => `${monthNames[props.month - 1]} ${props.year}`);
const prevLabel = computed(() => {
    const d = new Date(props.year, props.month - 2, 1);
    return `${monthNames[d.getMonth()]} ${d.getFullYear()}`;
});

function navigate(delta: number) {
    const d = new Date(props.year, props.month - 1 + delta, 1);
    router.get('/reports', { year: d.getFullYear(), month: d.getMonth() + 1 }, { preserveScroll: true });
}

// Donut SVG
const DONUT_RADIUS = 38;
const DONUT_CIRC = 2 * Math.PI * DONUT_RADIUS; // ~238.76

const donutSegments = computed(() => {
    let offset = 0;
    return props.categoryBreakdown.map(cat => {
        const dash = (cat.percent / 100) * DONUT_CIRC;
        const seg = { color: cat.color, dash, gap: DONUT_CIRC - dash, offset: -offset };
        offset += dash;
        return seg;
    });
});

// Pct formatting helpers
function pctDiff(cur: number, prev: number) {
    if (prev === 0) return null;
    return Math.round(((cur - prev) / prev) * 100 * 10) / 10;
}

const incomeChg  = computed(() => pctDiff(props.kpi.income, props.kpi.prev_income));
const expenseChg = computed(() => pctDiff(props.kpi.expense, props.kpi.prev_expense));
const netChg     = computed(() => pctDiff(props.kpi.net_flow, props.kpi.prev_net));

// Comparison bars relative widths
function relBar(a: number, b: number) {
    const max = Math.max(a, b, 1);
    return { cur: Math.round((a / max) * 100), prev: Math.round((b / max) * 100) };
}
const incomeBar  = computed(() => relBar(props.kpi.income, props.kpi.prev_income));
const expenseBar = computed(() => relBar(props.kpi.expense, props.kpi.prev_expense));
const netBar     = computed(() => relBar(Math.abs(props.kpi.net_flow), Math.abs(props.kpi.prev_net)));

function statusBadge(cat: TopCategory) {
    if (cat.percent === null) return { cls: 'bg-slate-100 dark:bg-slate-800 text-slate-600', label: 'Tanpa Anggaran' };
    if (cat.status === 'over')    return { cls: 'bg-rose-50 dark:bg-rose-950/50 text-rose-600', label: `${cat.percent}% (Melebihi Plafon)` };
    if (cat.status === 'warning') return { cls: 'bg-amber-50 dark:bg-amber-950/50 text-amber-700', label: `${cat.percent}% (Mendekati Batas)` };
    return { cls: 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700', label: `${cat.percent}% (Sesuai Rencana)` };
}
</script>

<template>
    <Head :title="`Laporan ${currentLabel} - KASHA`" />

    <div class="w-full max-w-[1240px] mx-auto px-4 sm:px-6 py-6 flex flex-col gap-6">

        <!-- Header with month navigator -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <div class="flex flex-col gap-1">
                <span class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-2.5 py-0.5 rounded-full w-fit">
                    Laporan Berkala
                </span>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
                    Laporan Keuangan Bulanan
                </h1>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <!-- Month Navigator -->
                <div class="flex items-center bg-slate-100 dark:bg-slate-800 rounded-lg p-1">
                    <button @click="navigate(-1)" class="w-8 h-8 flex items-center justify-center rounded text-muted-foreground hover:text-slate-900 dark:hover:text-slate-100 hover:bg-white dark:hover:bg-slate-700 transition-colors">
                        <ChevronLeft class="h-4 w-4" />
                    </button>
                    <div class="flex items-center gap-1.5 px-3 py-1">
                        <span class="text-sm font-semibold text-slate-900 dark:text-slate-100 select-none">{{ currentLabel }}</span>
                    </div>
                    <button @click="navigate(1)" class="w-8 h-8 flex items-center justify-center rounded text-muted-foreground hover:text-slate-900 dark:hover:text-slate-100 hover:bg-white dark:hover:bg-slate-700 transition-colors">
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>

        <!-- 4 KPI Tiles -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Pemasukan -->
            <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Total Pemasukan</span>
                    <div class="w-9 h-9 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600">
                        <TrendingUp class="h-4 w-4" />
                    </div>
                </div>
                <div class="mt-4">
                    <div class="text-2xl font-bold text-emerald-600 tracking-tight tabular-nums">+{{ format(kpi.income) }}</div>
                    <div class="flex items-center gap-1 mt-2">
                        <span v-if="incomeChg !== null" :class="['text-xs px-2 py-0.5 rounded-full font-medium flex items-center gap-0.5', incomeChg >= 0 ? 'bg-emerald-50 dark:bg-emerald-950 text-emerald-700' : 'bg-rose-50 dark:bg-rose-950 text-rose-600']">
                            <TrendingUp v-if="incomeChg >= 0" class="h-3 w-3" />
                            <TrendingDown v-else class="h-3 w-3" />
                            {{ incomeChg >= 0 ? '+' : '' }}{{ incomeChg }}%
                        </span>
                        <span class="text-xs text-muted-foreground">vs {{ prevLabel }}</span>
                    </div>
                </div>
            </div>

            <!-- Total Pengeluaran -->
            <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Total Pengeluaran</span>
                    <div class="w-9 h-9 rounded-lg bg-rose-50 dark:bg-rose-950/50 flex items-center justify-center text-rose-600">
                        <TrendingDown class="h-4 w-4" />
                    </div>
                </div>
                <div class="mt-4">
                    <div class="text-2xl font-bold text-rose-600 tracking-tight tabular-nums">-{{ format(kpi.expense) }}</div>
                    <div class="flex items-center gap-1 mt-2">
                        <span v-if="expenseChg !== null" :class="['text-xs px-2 py-0.5 rounded-full font-medium flex items-center gap-0.5', expenseChg <= 0 ? 'bg-emerald-50 dark:bg-emerald-950 text-emerald-700' : 'bg-rose-50 dark:bg-rose-950 text-rose-600']">
                            <TrendingDown v-if="expenseChg <= 0" class="h-3 w-3" />
                            <TrendingUp v-else class="h-3 w-3" />
                            {{ expenseChg >= 0 ? '+' : '' }}{{ expenseChg }}%
                        </span>
                        <span class="text-xs text-muted-foreground">vs {{ prevLabel }}</span>
                    </div>
                </div>
            </div>

            <!-- Arus Kas Bersih -->
            <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Arus Kas Bersih</span>
                    <div class="w-9 h-9 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600">
                        <Wallet class="h-4 w-4" />
                    </div>
                </div>
                <div class="mt-4">
                    <div class="text-2xl font-bold tracking-tight tabular-nums" :class="kpi.net_flow >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                        {{ kpi.net_flow >= 0 ? '+' : '' }}{{ format(kpi.net_flow) }}
                    </div>
                    <div class="flex items-center gap-1 mt-2">
                        <span v-if="netChg !== null" :class="['text-xs px-2 py-0.5 rounded-full font-medium flex items-center gap-0.5', netChg >= 0 ? 'bg-emerald-50 dark:bg-emerald-950 text-emerald-700' : 'bg-rose-50 dark:bg-rose-950 text-rose-600']">
                            <TrendingUp v-if="netChg >= 0" class="h-3 w-3" />
                            <TrendingDown v-else class="h-3 w-3" />
                            {{ netChg >= 0 ? '+' : '' }}{{ netChg }}%
                        </span>
                        <span class="text-xs text-muted-foreground">surplus periode ini</span>
                    </div>
                </div>
            </div>

            <!-- Rasio Tabungan -->
            <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Rasio Tabungan</span>
                    <div class="w-9 h-9 rounded-lg bg-sky-50 dark:bg-sky-950/50 flex items-center justify-center text-sky-600">
                        <PiggyBank class="h-4 w-4" />
                    </div>
                </div>
                <div class="mt-4">
                    <div class="text-2xl font-bold text-slate-900 dark:text-slate-100 tracking-tight tabular-nums">{{ kpi.saving_rate }}%</div>
                    <div class="mt-2">
                        <span :class="['text-xs px-2 py-0.5 rounded-full font-medium flex items-center gap-1 w-fit', kpi.saving_rate >= 20 ? 'bg-emerald-50 dark:bg-emerald-950 text-emerald-700' : 'bg-amber-50 dark:bg-amber-950 text-amber-700']">
                            {{ kpi.saving_rate >= 20 ? '✓ Sehat (>20% acuan)' : '⚠ Di bawah acuan 20%' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
            <!-- Donut Chart: Komposisi Pengeluaran -->
            <div class="lg:col-span-6 bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Komposisi Pengeluaran</h2>
                        <p class="text-xs text-muted-foreground">Sebaran pos pembiayaan {{ currentLabel }}</p>
                    </div>
                    <BarChart2 class="h-5 w-5 text-muted-foreground" />
                </div>

                <div v-if="!categoryBreakdown.length" class="flex-1 flex items-center justify-center text-xs text-muted-foreground py-8">
                    Belum ada transaksi pengeluaran bulan ini.
                </div>

                <div v-else class="flex flex-col sm:flex-row items-center justify-center gap-6 py-2">
                    <!-- Donut SVG -->
                    <div class="relative w-44 h-44 shrink-0 flex items-center justify-center">
                        <svg class="w-full h-full -rotate-90" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" fill="transparent" r="38" stroke="#f1f5f9" stroke-width="12" />
                            <circle
                                v-for="(seg, i) in donutSegments"
                                :key="i"
                                cx="50" cy="50"
                                fill="transparent"
                                r="38"
                                :stroke="seg.color"
                                :stroke-dasharray="`${seg.dash} ${DONUT_CIRC}`"
                                :stroke-dashoffset="seg.offset"
                                stroke-width="12"
                            />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center pointer-events-none">
                            <span class="text-[10px] uppercase tracking-wider text-muted-foreground">Total Belanja</span>
                            <span class="text-sm font-bold text-slate-900 dark:text-slate-100 mt-0.5 tabular-nums">{{ format(kpi.expense) }}</span>
                            <span class="text-[11px] text-muted-foreground">{{ categoryBreakdown.length }} Pos Utama</span>
                        </div>
                    </div>

                    <!-- Legend -->
                    <div class="flex flex-col gap-2 w-full min-w-0">
                        <div v-for="cat in categoryBreakdown" :key="cat.category_id ?? cat.category_name" class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="`background:${cat.color}`" />
                                <span class="text-slate-800 dark:text-slate-200 truncate">{{ cat.category_name }}</span>
                            </div>
                            <div class="text-right shrink-0 ml-2">
                                <span class="font-semibold text-slate-900 dark:text-slate-100 tabular-nums">{{ format(cat.total) }}</span>
                                <span class="text-muted-foreground ml-1.5">({{ cat.percent }}%)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Insight footer -->
                <div v-if="categoryBreakdown.length" class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-muted-foreground bg-slate-50 dark:bg-slate-800/50 px-3 py-2 rounded-lg">
                    <span class="flex items-center gap-1.5">
                        <Info class="h-3.5 w-3.5 text-emerald-600" />
                        Kategori terbesar: <strong class="text-slate-800 dark:text-slate-200">{{ categoryBreakdown[0]?.category_name }}</strong>
                        ({{ categoryBreakdown[0]?.percent }}% total belanja)
                    </span>
                </div>
            </div>

            <!-- Comparison Chart -->
            <div class="lg:col-span-6 bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Komparasi Bulan Lalu</h2>
                        <p class="text-xs text-muted-foreground">Performa finansial {{ currentLabel }} vs {{ prevLabel }}</p>
                    </div>
                </div>

                <div class="flex flex-col gap-4">
                    <!-- Total Pemasukan comparison -->
                    <div class="flex flex-col gap-1.5 bg-slate-50 dark:bg-slate-800/40 p-3 rounded-lg">
                        <div class="flex items-center justify-between text-xs font-semibold">
                            <span class="text-slate-900 dark:text-slate-100">Total Pemasukan</span>
                            <span v-if="incomeChg !== null" :class="incomeChg >= 0 ? 'text-emerald-600' : 'text-rose-600'" class="flex items-center gap-0.5">
                                <TrendingUp v-if="incomeChg >= 0" class="h-3 w-3" />
                                <TrendingDown v-else class="h-3 w-3" />
                                {{ incomeChg >= 0 ? '+' : '' }}{{ format(kpi.income - kpi.prev_income) }} ({{ incomeChg }}%)
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-muted-foreground">
                            <span>{{ prevLabel }}: {{ format(kpi.prev_income) }}</span>
                            <span class="font-semibold text-slate-900 dark:text-slate-100">{{ currentLabel }}: {{ format(kpi.income) }}</span>
                        </div>
                        <div class="w-full bg-slate-200 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                            <div class="bg-emerald-600 h-full rounded-full transition-all duration-500" :style="`width: ${incomeBar.cur}%`" />
                        </div>
                    </div>

                    <!-- Total Pengeluaran comparison -->
                    <div class="flex flex-col gap-1.5 bg-slate-50 dark:bg-slate-800/40 p-3 rounded-lg">
                        <div class="flex items-center justify-between text-xs font-semibold">
                            <span class="text-slate-900 dark:text-slate-100">Total Pengeluaran</span>
                            <span v-if="expenseChg !== null" :class="expenseChg <= 0 ? 'text-emerald-600' : 'text-rose-600'" class="flex items-center gap-0.5">
                                <TrendingDown v-if="expenseChg <= 0" class="h-3 w-3" />
                                <TrendingUp v-else class="h-3 w-3" />
                                {{ expenseChg >= 0 ? '+' : '' }}{{ format(kpi.expense - kpi.prev_expense) }} ({{ expenseChg }}%)
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-muted-foreground">
                            <span>{{ prevLabel }}: {{ format(kpi.prev_expense) }}</span>
                            <span class="font-semibold text-slate-900 dark:text-slate-100">{{ currentLabel }}: {{ format(kpi.expense) }}</span>
                        </div>
                        <div class="w-full bg-slate-200 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                            <div class="bg-rose-500 h-full rounded-full transition-all duration-500" :style="`width: ${expenseBar.cur}%`" />
                        </div>
                    </div>

                    <!-- Net Flow comparison -->
                    <div class="flex flex-col gap-1.5 bg-slate-50 dark:bg-slate-800/40 p-3 rounded-lg">
                        <div class="flex items-center justify-between text-xs font-semibold">
                            <span class="text-slate-900 dark:text-slate-100">Arus Kas Bersih</span>
                            <span v-if="netChg !== null" :class="netChg >= 0 ? 'text-emerald-600' : 'text-rose-600'" class="flex items-center gap-0.5">
                                <TrendingUp v-if="netChg >= 0" class="h-3 w-3" />
                                <TrendingDown v-else class="h-3 w-3" />
                                {{ netChg >= 0 ? '+' : '' }}{{ netChg }}%
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-muted-foreground">
                            <span>{{ prevLabel }}: {{ format(kpi.prev_net) }}</span>
                            <span class="font-semibold text-slate-900 dark:text-slate-100">{{ currentLabel }}: {{ format(kpi.net_flow) }}</span>
                        </div>
                        <div class="w-full bg-slate-200 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                            <div class="bg-emerald-600 h-full rounded-full transition-all duration-500" :style="`width: ${netBar.cur}%`" />
                        </div>
                    </div>

                    <!-- Saving rate -->
                    <div class="flex flex-col gap-1.5 bg-emerald-50 dark:bg-emerald-950/30 p-3 rounded-lg border border-emerald-100 dark:border-emerald-950/50">
                        <div class="flex items-center justify-between text-xs font-semibold">
                            <span class="text-slate-900 dark:text-slate-100">Total Ditabung / Surplus</span>
                            <span class="text-emerald-600 flex items-center gap-0.5">
                                <TrendingUp class="h-3 w-3" />
                                {{ format(kpi.net_flow) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-muted-foreground">
                            <span>{{ prevLabel }}: {{ format(kpi.prev_net) }}</span>
                            <span class="font-semibold text-emerald-700 dark:text-emerald-400">{{ currentLabel }}: {{ format(kpi.net_flow) }}</span>
                        </div>
                        <div class="w-full bg-emerald-100 dark:bg-emerald-900/40 h-2 rounded-full overflow-hidden">
                            <div class="bg-emerald-600 h-full rounded-full transition-all duration-500" style="width: 100%" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top 5 Kategori Pengeluaran Table -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Top 5 Kategori Pengeluaran Terbesar</h2>
                    <p class="text-xs text-muted-foreground">Pemantauan realisasi terhadap plafon anggaran yang ditetapkan</p>
                </div>
            </div>

            <div v-if="!topCategories.length" class="text-center py-10 text-xs text-muted-foreground">
                Belum ada transaksi pengeluaran bulan ini.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/60 text-muted-foreground uppercase tracking-wider font-semibold">
                            <th class="py-3 px-5 text-center w-14">Peringkat</th>
                            <th class="py-3 px-4">Kategori</th>
                            <th class="py-3 px-4 text-right">Anggaran Plafon</th>
                            <th class="py-3 px-4 text-right">Realisasi Belanja</th>
                            <th class="py-3 px-4 text-center">% Plafon</th>
                            <th class="py-3 px-5 text-right">vs {{ prevLabel }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr
                            v-for="(cat, i) in topCategories"
                            :key="cat.category_id ?? cat.category_name"
                            class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors"
                        >
                            <td class="py-3.5 px-5 text-center">
                                <span :class="['w-6 h-6 rounded-full font-bold inline-flex items-center justify-center text-[11px]', i === 0 ? 'bg-rose-100 dark:bg-rose-950 text-rose-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-600']">
                                    {{ i + 1 }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" :style="`background:${cat.color}18`">
                                        <span class="text-sm font-bold" :style="`color:${cat.color}`">{{ cat.category_name.charAt(0) }}</span>
                                    </div>
                                    <div>
                                        <span class="font-semibold text-slate-900 dark:text-slate-100 block">{{ cat.category_name }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-right font-medium text-muted-foreground tabular-nums">
                                {{ cat.limit > 0 ? format(cat.limit) : '—' }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-semibold text-rose-600 tabular-nums">
                                -{{ format(cat.total) }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex flex-col items-center gap-1">
                                    <span :class="['text-[11px] px-2 py-0.5 rounded-full font-medium inline-flex items-center gap-1', statusBadge(cat).cls]">
                                        {{ statusBadge(cat).label }}
                                    </span>
                                    <div v-if="cat.percent !== null" class="w-20 bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden">
                                        <div
                                            class="h-full rounded-full transition-all duration-500"
                                            :class="cat.status === 'over' ? 'bg-rose-500' : cat.status === 'warning' ? 'bg-amber-500' : 'bg-emerald-500'"
                                            :style="`width: ${Math.min(100, cat.percent)}%`"
                                        />
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-5 text-right">
                                <span :class="['font-semibold flex items-center justify-end gap-0.5', cat.vs_prev > 0 ? 'text-rose-600' : cat.vs_prev < 0 ? 'text-emerald-600' : 'text-muted-foreground']">
                                    <TrendingUp v-if="cat.vs_prev > 0" class="h-3 w-3" />
                                    <TrendingDown v-else-if="cat.vs_prev < 0" class="h-3 w-3" />
                                    {{ cat.vs_prev === 0 ? 'Sama' : (cat.vs_prev > 0 ? '+' : '') + format(cat.vs_prev) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

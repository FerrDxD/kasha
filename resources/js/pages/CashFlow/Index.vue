<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useMoney } from '@/composables/useMoney';
import {
    Wallet,
    TrendingDown,
    TrendingUp,
    Clock,
    AlertTriangle,
    Calendar,
    ArrowUpRight,
    ArrowDownRight,
    CheckCircle2,
} from '@lucide/vue';

interface DailyGroup {
    [date: string]: { occurred_on: string; type: string; total: number }[];
}
interface Projected {
    date: string;
    type: string;
    amount: number;
    payee: string | null;
}

const props = defineProps<{
    historical: DailyGroup;
    projected: Projected[];
    range: number;
}>();

const { format, compact } = useMoney();
const selectedRange = ref(props.range);

function changeRange(r: number) {
    selectedRange.value = r;
    router.get('/cash-flow', { range: `${r}d` }, { preserveState: true, replace: true });
}

// Compute daily delta
const chartData = computed(() => {
    return Object.entries(props.historical).map(([date, rows]) => {
        const income = rows.filter(r => r.type === 'income').reduce((s, r) => s + Number(r.total), 0);
        const expense = rows.filter(r => r.type === 'expense').reduce((s, r) => s + Number(r.total), 0);
        return { date, income, expense, net: income - expense };
    }).sort((a, b) => a.date.localeCompare(b.date));
});

const totalIncome = computed(() => chartData.value.reduce((s, d) => s + d.income, 0));
const totalExpense = computed(() => chartData.value.reduce((s, d) => s + d.expense, 0));
const currentNet = computed(() => totalIncome.value - totalExpense.value);

// Projected obligations & totals
const projectedObligations = computed(() =>
    props.projected.filter(p => p.type === 'expense').reduce((s, p) => s + p.amount, 0)
);
const projectedIncome = computed(() =>
    props.projected.filter(p => p.type === 'income').reduce((s, p) => s + p.amount, 0)
);
const projectedEnding = computed(() =>
    currentNet.value + projectedIncome.value - projectedObligations.value
);

const maxDaily = computed(() =>
    Math.max(...chartData.value.map(d => Math.max(d.income, d.expense)), 1)
);

// Group projected items by date
const projectedByDate = computed(() => {
    const map: Record<string, Projected[]> = {};
    props.projected.forEach(p => {
        if (!map[p.date]) map[p.date] = [];
        map[p.date].push(p);
    });
    return map;
});
const projectedDates = computed(() => Object.keys(projectedByDate.value).sort());
</script>

<template>
    <Head title="Simulasi Arus Kas" />

    <div class="max-w-[1200px] mx-auto p-4 sm:p-6 lg:p-6 flex flex-col gap-6">
        <!-- Top Header & Controls -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                    <span class="text-xs uppercase tracking-wider font-semibold text-emerald-600">Arus Kas &amp; Proyeksi</span>
                    <span>•</span>
                    <span>Simulasi Keuangan</span>
                </div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Simulasi Arus Kas (Cash Flow)</h1>
                <p class="text-sm text-muted-foreground max-w-2xl">
                    Pantau riwayat likuiditas dan mitigasi risiko defisit kas di masa depan melalui kalkulasi prediktif jadwal pengeluaran dan pemasukan berkala.
                </p>
            </div>

            <!-- Range Toggle Segmented Button -->
            <div class="inline-flex p-1 bg-slate-100 dark:bg-slate-800 rounded-lg text-xs font-semibold self-start md:self-auto">
                <button v-for="r in [30, 60, 90]" :key="r" @click="changeRange(r)" type="button"
                    class="px-3.5 py-1.5 rounded-md transition-all"
                    :class="selectedRange === r ? 'bg-card text-emerald-600 shadow-xs' : 'text-muted-foreground hover:text-slate-900'">
                    {{ r }} Hari
                </button>
            </div>
        </div>

        <!-- 4 KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Saldo Terkini -->
            <div class="rounded-xl border bg-card p-5 shadow-sm flex flex-col justify-between gap-3">
                <div class="flex items-center justify-between text-muted-foreground text-xs font-medium">
                    <span>Arus Kas Bersih (Periode Ini)</span>
                    <span class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 flex items-center justify-center">
                        <Wallet class="h-4 w-4" />
                    </span>
                </div>
                <div>
                    <div class="text-xl font-bold tracking-tight tabular-nums"
                        :class="currentNet >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                        {{ currentNet >= 0 ? '+' : '-' }}{{ format(Math.abs(currentNet)) }}
                    </div>
                    <span class="text-xs text-muted-foreground">Historis {{ range }} hari</span>
                </div>
                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 self-start text-[11px] font-semibold">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                    <span>Likuid Terkelola</span>
                </div>
            </div>

            <!-- Total Pemasukan -->
            <div class="rounded-xl border bg-card p-5 shadow-sm flex flex-col justify-between gap-3">
                <div class="flex items-center justify-between text-muted-foreground text-xs font-medium">
                    <span>Total Pemasukan</span>
                    <span class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 flex items-center justify-center">
                        <TrendingUp class="h-4 w-4" />
                    </span>
                </div>
                <div>
                    <div class="text-xl font-bold tracking-tight text-emerald-600 tabular-nums">
                        +{{ format(totalIncome) }}
                    </div>
                    <span class="text-xs text-muted-foreground">Akumulasi {{ range }} hari</span>
                </div>
                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 self-start text-[11px] font-semibold">
                    <span>Inflow Kas</span>
                </div>
            </div>

            <!-- Proyeksi Akhir Periode -->
            <div class="rounded-xl border bg-card p-5 shadow-sm flex flex-col justify-between gap-3">
                <div class="flex items-center justify-between text-muted-foreground text-xs font-medium">
                    <span>Proyeksi Akhir Periode</span>
                    <span class="w-8 h-8 rounded-lg bg-sky-50 dark:bg-sky-950/40 text-sky-600 flex items-center justify-center">
                        <ArrowUpRight class="h-4 w-4" />
                    </span>
                </div>
                <div>
                    <div class="text-xl font-bold tracking-tight tabular-nums"
                        :class="projectedEnding >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                        {{ projectedEnding >= 0 ? '+' : '-' }}{{ format(Math.abs(projectedEnding)) }}
                    </div>
                    <span class="text-xs text-muted-foreground">Setelah kewajiban rutin</span>
                </div>
                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 self-start text-[11px] font-semibold">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                    <span>Net Ekuitas Positif</span>
                </div>
            </div>

            <!-- Kewajiban Terjadwal -->
            <div class="rounded-xl border bg-card p-5 shadow-sm flex flex-col justify-between gap-3">
                <div class="flex items-center justify-between text-muted-foreground text-xs font-medium">
                    <span>Kewajiban Terjadwal</span>
                    <span class="w-8 h-8 rounded-lg bg-rose-50 dark:bg-rose-950/40 text-rose-600 flex items-center justify-center">
                        <Clock class="h-4 w-4" />
                    </span>
                </div>
                <div>
                    <div class="text-xl font-bold tracking-tight text-rose-600 tabular-nums">
                        -{{ format(projectedObligations) }}
                    </div>
                    <span class="text-xs text-muted-foreground">Jadwal recurring aktif</span>
                </div>
                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-rose-50 dark:bg-rose-950/40 text-rose-600 self-start text-[11px] font-semibold">
                    <span>{{ projected.length }} Agenda Menanti</span>
                </div>
            </div>
        </div>

        <!-- Historical Activity Visual -->
        <div class="rounded-xl border bg-card p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Arus Kas Harian ({{ range }} Hari Terakhir)</h2>
                    <p class="text-xs text-muted-foreground">Distribusi transaksi pemasukan dan pengeluaran per tanggal</p>
                </div>
                <div class="flex gap-4 text-xs">
                    <span class="flex items-center gap-1.5 font-medium text-emerald-600">
                        <span class="h-2.5 w-2.5 rounded-xs bg-emerald-500 inline-block" /> Pemasukan
                    </span>
                    <span class="flex items-center gap-1.5 font-medium text-rose-600">
                        <span class="h-2.5 w-2.5 rounded-xs bg-rose-500 inline-block" /> Pengeluaran
                    </span>
                </div>
            </div>

            <div class="flex items-end gap-1.5 overflow-x-auto pb-2 pt-6 min-h-[140px] border-b">
                <div v-for="d in chartData" :key="d.date"
                    class="flex flex-col items-center gap-1 shrink-0 w-8 group relative cursor-pointer"
                    :title="`${d.date}: +${format(d.income)} | -${format(d.expense)}`">
                    <div class="w-full flex flex-col items-center gap-0.5">
                        <div v-if="d.income > 0" class="w-4 rounded-t-xs bg-emerald-500 hover:bg-emerald-600 transition-all"
                            :style="`height: ${Math.max(4, Math.round((d.income / maxDaily) * 70))}px`" />
                        <div v-if="d.expense > 0" class="w-4 rounded-b-xs bg-rose-500 hover:bg-rose-600 transition-all"
                            :style="`height: ${Math.max(4, Math.round((d.expense / maxDaily) * 70))}px`" />
                    </div>
                    <span class="text-[9px] text-muted-foreground whitespace-nowrap mt-1">
                        {{ new Date(d.date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) }}
                    </span>
                </div>
                <p v-if="!chartData.length" class="text-xs text-muted-foreground w-full py-8 text-center">
                    Belum ada riwayat transaksi pada rentang hari ini.
                </p>
            </div>
        </div>

        <!-- Projected Timeline -->
        <div class="rounded-xl border bg-card p-5 shadow-sm">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white mb-1">Proyeksi Tagihan &amp; Pemasukan Mendatang</h2>
            <p class="text-xs text-muted-foreground mb-4">Dihitung otomatis dari konfigurasi Transaksi Berulang (Recurring)</p>

            <div v-if="projectedDates.length" class="space-y-4">
                <div v-for="date in projectedDates" :key="date" class="border-l-2 border-emerald-600 pl-4 py-1 space-y-2">
                    <p class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                        {{ new Date(date).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}
                    </p>
                    <div class="space-y-1.5">
                        <div v-for="(p, idx) in projectedByDate[date]" :key="idx"
                            class="p-2.5 rounded-lg bg-slate-50/70 dark:bg-slate-900/50 border flex items-center justify-between text-xs">
                            <span class="font-medium text-slate-800 dark:text-slate-200">{{ p.payee || 'Transaksi Berulang' }}</span>
                            <span class="font-bold tabular-nums" :class="p.type === 'income' ? 'text-emerald-600' : 'text-rose-600'">
                                {{ p.type === 'income' ? '+' : '-' }}{{ format(p.amount) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <p v-else class="text-xs text-muted-foreground text-center py-6">
                Tidak ada agenda berulang pada periode proyeksi. Tambahkan di menu Recurring.
            </p>
        </div>
    </div>
</template>

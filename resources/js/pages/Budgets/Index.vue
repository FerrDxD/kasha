<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useMoney } from '@/composables/useMoney';
import type { Budget } from '@/types';
import {
    ChevronLeft,
    ChevronRight,
    Calendar,
    Save,
    Search,
    AlertTriangle,
    TrendingUp,
    Hourglass,
    CheckCircle2,
    Utensils,
    Car,
    Gamepad2,
    Layers,
    Tag,
} from '@lucide/vue';

const props = defineProps<{
    budgets: Budget[];
    period: string;
}>();

const { format, compact } = useMoney();

const currentPeriod = ref(props.period);

function goToPeriod(offset: number) {
    const [y, m] = currentPeriod.value.split('-').map(Number);
    const d = new Date(y, m - 1 + offset, 1);
    currentPeriod.value = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
    router.get('/budgets', { period: currentPeriod.value }, { preserveState: true, replace: true });
}

const periodLabel = computed(() => {
    return new Date(currentPeriod.value + '-01').toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
});

// Inline editing state
const edited = ref<Record<string, { amount: number; rollover: boolean }>>({});

function getAmount(b: Budget) {
    return edited.value[b.category_id]?.amount ?? b.amount;
}
function setAmount(b: Budget, val: number) {
    edited.value[b.category_id] = { amount: val, rollover: getRollover(b) };
}
function getRollover(b: Budget) {
    return edited.value[b.category_id]?.rollover ?? b.rollover;
}
function setRollover(b: Budget, val: boolean) {
    edited.value[b.category_id] = { amount: getAmount(b), rollover: val };
}

function saveAll() {
    const payload = props.budgets.map(b => ({
        category_id: b.category_id,
        period: currentPeriod.value,
        amount: getAmount(b),
        rollover: getRollover(b),
    }));
    router.put('/budgets', { budgets: payload }, {
        preserveScroll: true,
        onSuccess: () => { edited.value = {}; },
    });
}

// Macro summary calculations
const totalBudget = computed(() => props.budgets.reduce((s, b) => s + getAmount(b), 0));
const totalSpent = computed(() => props.budgets.reduce((s, b) => s + b.spent, 0));
const remainingBudget = computed(() => Math.max(0, totalBudget.value - totalSpent.value));
const overallRatio = computed(() =>
    totalBudget.value > 0 ? Math.min(100, Math.round((totalSpent.value / totalBudget.value) * 100)) : 0
);

// Days remaining in month calculation
const daysRemaining = computed(() => {
    const now = new Date();
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();
    return Math.max(1, lastDay - now.getDate());
});
const safeBurnRate = computed(() =>
    daysRemaining.value > 0 ? Math.round(remainingBudget.value / daysRemaining.value) : 0
);

// Filter status
const searchQuery = ref('');
const statusFilter = ref<'all' | 'safe' | 'warning' | 'danger'>('all');

const filteredBudgets = computed(() => {
    return props.budgets.filter(b => {
        if (searchQuery.value && !b.category_name.toLowerCase().includes(searchQuery.value.toLowerCase())) {
            return false;
        }
        const pct = getAmount(b) > 0 ? Math.round((b.spent / getAmount(b)) * 100) : 0;
        if (statusFilter.value === 'safe') return pct < 70;
        if (statusFilter.value === 'warning') return pct >= 70 && pct < 100;
        if (statusFilter.value === 'danger') return pct >= 100;
        return true;
    });
});

function getBudgetStatus(spent: number, amount: number) {
    if (amount <= 0) return { pct: 0, text: 'text-muted-foreground', bg: 'bg-slate-400', state: 'safe' };
    const pct = Math.round((spent / amount) * 100);
    if (pct >= 100) return { pct, text: 'text-rose-600', bg: 'bg-rose-600', state: 'danger' };
    if (pct >= 70) return { pct, text: 'text-amber-500', bg: 'bg-amber-500', state: 'warning' };
    return { pct, text: 'text-emerald-600', bg: 'bg-emerald-600', state: 'safe' };
}
</script>

<template>
    <Head title="Anggaran Bulanan" />

    <div class="max-w-[1200px] mx-auto p-4 sm:p-6 lg:p-6 flex flex-col gap-6">
        <!-- Top Action Row -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <div>
                    <span class="text-xs uppercase tracking-wider text-muted-foreground font-semibold">Pengelolaan &amp; Pagu Biaya</span>
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white mt-0.5">Anggaran Bulanan</h1>
                </div>

                <!-- Month Navigator -->
                <div class="inline-flex items-center bg-card border shadow-xs rounded-lg p-1 self-start sm:self-auto">
                    <button @click="goToPeriod(-1)" type="button" class="w-8 h-8 flex items-center justify-center rounded hover:bg-muted text-muted-foreground hover:text-slate-900 transition-colors">
                        <ChevronLeft class="h-4 w-4" />
                    </button>
                    <div class="px-2.5 flex items-center gap-1.5 font-medium text-xs text-slate-900 dark:text-white">
                        <Calendar class="h-3.5 w-3.5 text-emerald-600" />
                        <span>{{ periodLabel }}</span>
                    </div>
                    <button @click="goToPeriod(1)" type="button" class="w-8 h-8 flex items-center justify-center rounded hover:bg-muted text-muted-foreground hover:text-slate-900 transition-colors">
                        <ChevronRight class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <!-- Action Button -->
            <div class="flex items-center gap-2">
                <button @click="saveAll" type="button"
                    class="inline-flex items-center gap-2 h-10 px-4 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-sm transition-all active:scale-[0.98]">
                    <Save class="h-4 w-4" />
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </div>

        <!-- Summary Banner Card (Radial + Macro metrics) -->
        <div class="rounded-xl border bg-card p-6 shadow-sm relative overflow-hidden">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                <!-- Radial Gauge & Direct Spend Ratio -->
                <div class="lg:col-span-4 flex items-center gap-4">
                    <div class="relative w-24 h-24 shrink-0 flex items-center justify-center">
                        <svg class="w-24 h-24 -rotate-90" viewBox="0 0 100 100">
                            <circle class="text-slate-100 dark:text-slate-800" cx="50" cy="50" fill="transparent" r="40" stroke="currentColor" stroke-width="8" />
                            <circle class="transition-all duration-1000"
                                :class="overallRatio >= 100 ? 'text-rose-600' : overallRatio >= 70 ? 'text-amber-500' : 'text-emerald-600'"
                                cx="50" cy="50" fill="transparent" r="40" stroke="currentColor"
                                stroke-dasharray="251.2" :stroke-dashoffset="251.2 - (251.2 * overallRatio) / 100" stroke-linecap="round" stroke-width="8" />
                        </svg>
                        <div class="absolute flex flex-col items-center justify-center text-center">
                            <span class="text-base font-bold tabular-nums text-slate-900 dark:text-white">{{ overallRatio }}%</span>
                            <span class="text-[9px] uppercase font-semibold text-muted-foreground">Terpakai</span>
                        </div>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-xs font-medium text-muted-foreground">Realisasi Plafon Gabungan</span>
                        <div class="text-xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
                            {{ format(totalSpent) }}
                        </div>
                        <div class="text-xs text-muted-foreground mt-0.5">
                            dari plafon <span class="font-medium text-slate-900 dark:text-white">{{ format(totalBudget) }}</span>
                        </div>
                        <div class="inline-flex items-center gap-1 mt-1 text-xs font-semibold"
                            :class="overallRatio >= 100 ? 'text-rose-600' : overallRatio >= 70 ? 'text-amber-500' : 'text-emerald-600'">
                            <span class="w-1.5 h-1.5 rounded-full" :class="overallRatio >= 100 ? 'bg-rose-600' : overallRatio >= 70 ? 'bg-amber-500' : 'bg-emerald-600'"></span>
                            <span>{{ overallRatio >= 100 ? 'Melebihi Plafon' : overallRatio >= 70 ? 'Mendekati Batas' : 'Dalam Batas Aman' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Center Metrics: Remaining Days & Run Rate -->
                <div class="lg:col-span-4 flex flex-col justify-center space-y-1 lg:border-l lg:pl-6 border-slate-100 dark:border-slate-800">
                    <span class="text-xs font-medium text-muted-foreground">Sisa Anggaran Tersedia</span>
                    <div class="flex items-baseline gap-2">
                        <span class="text-xl font-bold tracking-tight tabular-nums text-slate-900 dark:text-white">
                            {{ format(remainingBudget) }}
                        </span>
                        <span class="text-xs text-muted-foreground font-semibold">
                            ({{ totalBudget > 0 ? Math.round((remainingBudget / totalBudget) * 100) : 0 }}%)
                        </span>
                    </div>
                    <div class="inline-flex items-center gap-1.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 px-2.5 py-1 rounded-md text-xs self-start">
                        <Hourglass class="h-3.5 w-3.5 text-muted-foreground" />
                        <span>{{ daysRemaining }} hari tersisa • Laju aman <strong>{{ format(safeBurnRate) }}</strong>/hari</span>
                    </div>
                </div>

                <!-- Right Metrics: Efficiency -->
                <div class="lg:col-span-4 flex flex-col justify-center space-y-1 lg:border-l lg:pl-6 border-slate-100 dark:border-slate-800">
                    <span class="text-xs font-medium text-muted-foreground">Status Anggaran Periode Ini</span>
                    <div class="flex items-center gap-1.5 text-emerald-600 text-xl font-bold tabular-nums">
                        <span>{{ remainingBudget > 0 ? '+ Surplus Kas' : 'Defisit Anggaran' }}</span>
                        <TrendingUp class="h-4 w-4" />
                    </div>
                    <span class="text-xs text-muted-foreground leading-relaxed">
                        Sisa surplus akhir bulan dapat dialokasikan ke pos Tabungan Goals &amp; Dana Darurat.
                    </span>
                </div>
            </div>
        </div>

        <!-- Filter & View Controls -->
        <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2 flex-1">
                <div class="relative min-w-[200px] max-w-xs">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <input v-model="searchQuery" placeholder="Saring pos kategori..."
                        class="w-full h-9 pl-9 pr-3 rounded-lg border bg-background text-sm focus:outline-none focus:ring-1 focus:ring-emerald-600" />
                </div>
                <div class="inline-flex p-1 bg-slate-100 dark:bg-slate-800 rounded-lg text-xs">
                    <button @click="statusFilter = 'all'" type="button" class="px-3 py-1 rounded-md transition-colors"
                        :class="statusFilter === 'all' ? 'bg-card font-semibold text-slate-900 dark:text-white shadow-xs' : 'text-muted-foreground'">
                        Semua ({{ budgets.length }})
                    </button>
                    <button @click="statusFilter = 'safe'" type="button" class="px-3 py-1 rounded-md transition-colors"
                        :class="statusFilter === 'safe' ? 'bg-card font-semibold text-slate-900 dark:text-white shadow-xs' : 'text-muted-foreground'">
                        Aman &lt;70%
                    </button>
                    <button @click="statusFilter = 'warning'" type="button" class="px-3 py-1 rounded-md transition-colors"
                        :class="statusFilter === 'warning' ? 'bg-card font-semibold text-slate-900 dark:text-white shadow-xs' : 'text-muted-foreground'">
                        Waspada 70–99%
                    </button>
                    <button @click="statusFilter = 'danger'" type="button" class="px-3 py-1 rounded-md transition-colors"
                        :class="statusFilter === 'danger' ? 'bg-card font-semibold text-rose-600 shadow-xs' : 'text-muted-foreground'">
                        Melebihi Plafon
                    </button>
                </div>
            </div>
        </div>

        <!-- Category Budget List -->
        <div class="space-y-3">
            <div v-for="b in filteredBudgets" :key="b.category_id"
                class="rounded-xl border bg-card p-5 shadow-xs flex flex-col gap-3">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 font-bold text-xs"
                            :class="getBudgetStatus(b.spent, getAmount(b)).state === 'danger' ? 'bg-rose-50 text-rose-600 dark:bg-rose-950/40' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40'">
                            <Tag class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">{{ b.category_name }}</h2>
                                <span v-if="getBudgetStatus(b.spent, getAmount(b)).state === 'danger'"
                                    class="inline-flex items-center gap-1 bg-rose-50 text-rose-600 dark:bg-rose-950/40 px-2 py-0.5 rounded-full text-[11px] font-semibold">
                                    <AlertTriangle class="h-3 w-3" />
                                    Melebihi Plafon +{{ format(b.spent - getAmount(b)) }}
                                </span>
                            </div>
                            <span class="text-xs text-muted-foreground mt-0.5">Siklus bulanan aktif</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 self-end md:self-auto">
                        <div class="text-right">
                            <span class="text-sm font-bold tabular-nums" :class="getBudgetStatus(b.spent, getAmount(b)).text">
                                {{ format(b.spent) }}
                            </span>
                            <span class="text-xs text-muted-foreground"> / Plafon {{ format(getAmount(b)) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500"
                        :class="getBudgetStatus(b.spent, getAmount(b)).bg"
                        :style="`width: ${Math.min(getBudgetStatus(b.spent, getAmount(b)).pct, 100)}%;`" />
                </div>

                <!-- Inline Budget Limit + Rollover Controls -->
                <div class="pt-2 border-t flex flex-wrap items-center justify-between gap-3 text-xs">
                    <div class="flex items-center gap-2">
                        <label class="text-muted-foreground font-medium">Ubah Pagu:</label>
                        <input :value="getAmount(b)" @input="setAmount(b, Number(($event.target as HTMLInputElement).value))"
                            type="number" min="0" step="50000"
                            class="h-8 w-36 px-2.5 rounded-md border bg-background text-xs tabular-nums focus:ring-1 focus:ring-emerald-600" />
                    </div>
                    <div class="flex items-center gap-2">
                        <input :checked="getRollover(b)" @change="setRollover(b, ($event.target as HTMLInputElement).checked)"
                            type="checkbox" :id="`rollover-${b.category_id}`"
                            class="rounded border text-emerald-600 focus:ring-emerald-600" />
                        <label :for="`rollover-${b.category_id}`" class="text-slate-700 dark:text-slate-300 cursor-pointer">
                            Rollover sisa saldo ke bulan depan
                        </label>
                    </div>
                </div>
            </div>

            <p v-if="!filteredBudgets.length" class="text-center text-sm text-muted-foreground py-12">
                Tidak ada anggaran pada filter ini.
            </p>
        </div>
    </div>
</template>

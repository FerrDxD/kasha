<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useMoney } from '@/composables/useMoney';
import {
    Plus,
    Pencil,
    Trash2,
    PiggyBank,
    Flag,
    RotateCw,
    Calendar,
    CreditCard,
    Building2,
    CheckCircle2,
    AlertCircle,
    X,
    TrendingUp,
    ArrowRight,
    PauseCircle,
    PlayCircle,
    Sparkles,
    Laptop,
    ShieldCheck,
    Plane,
    Target
} from '@lucide/vue';
import { Button } from '@/components/ui/button';

interface Contribution {
    id: string;
    amount: number;
    contributed_on: string;
}

interface LinkedAccount {
    id: string;
    name: string;
}

interface GoalItem {
    id: string;
    name: string;
    target_amount: number;
    target_date: string | null;
    linked_account_id: string | null;
    linked_account: LinkedAccount | null;
    status: 'active' | 'completed' | 'paused';
    contributed_amount: number;
    progress_percent: number;
    remaining_amount: number;
    months_remaining: number | null;
    monthly_recommendation: number | null;
    health_status: 'On Track' | 'Ahead of Schedule' | 'Perlu Tambahan' | 'Tercapai';
    contributions: Contribution[];
}

interface AccountItem {
    id: string;
    name: string;
    type: string;
    balance: number;
}

interface KpiData {
    total_contributed: number;
    total_target: number;
    overall_percent: number;
    remaining_commitment: number;
    active_count: number;
    nearing_count: number;
    monthly_allocation: number;
}

const props = defineProps<{
    goals: GoalItem[];
    accounts?: AccountItem[];
    kpi?: KpiData;
}>();

const { format } = useMoney();

// Filter & status selection
const filterStatus = ref<'all' | 'active' | 'completed' | 'paused'>('all');

const filteredGoals = computed(() => {
    if (filterStatus.value === 'all') return props.goals;
    return props.goals.filter(g => g.status === filterStatus.value);
});

// Goal Drawer State
const selectedGoal = ref<GoalItem | null>(null);
const isDrawerOpen = ref(false);

function openGoalDrawer(goal: GoalItem) {
    selectedGoal.value = goal;
    isDrawerOpen.value = true;
    quickAmount.value = 500000;
}

function closeGoalDrawer() {
    isDrawerOpen.value = false;
    selectedGoal.value = null;
}

// Quick Contribution inside Drawer
const quickAmount = ref<number>(500000);
const contributeForm = useForm({
    amount: 500000,
    contributed_on: new Date().toISOString().split('T')[0],
});

function addQuickAmount(val: number) {
    quickAmount.value += val;
    contributeForm.amount = quickAmount.value;
}

function setQuickAmount(val: number) {
    quickAmount.value = val;
    contributeForm.amount = val;
}

function submitQuickContribute() {
    if (!selectedGoal.value) return;
    contributeForm.amount = quickAmount.value;
    contributeForm.post(`/goals/${selectedGoal.value.id}/contribute`, {
        preserveScroll: true,
        onSuccess: () => {
            // Update selectedGoal locally or keep drawer synced
            const updated = props.goals.find(g => g.id === selectedGoal.value?.id);
            if (updated) {
                selectedGoal.value = updated;
            }
        },
    });
}

// Goal Create/Edit Modal State
const showGoalModal = ref(false);
const editingGoalId = ref<string | null>(null);

const goalForm = useForm({
    name: '',
    target_amount: '' as unknown as number,
    target_date: '',
    linked_account_id: '' as string | null,
    status: 'active' as 'active' | 'completed' | 'paused',
});

function openCreateGoal() {
    goalForm.reset();
    goalForm.status = 'active';
    goalForm.linked_account_id = props.accounts?.[0]?.id ?? null;
    editingGoalId.value = null;
    showGoalModal.value = true;
}

function openEditGoal(goal: GoalItem) {
    editingGoalId.value = goal.id;
    goalForm.name = goal.name;
    goalForm.target_amount = goal.target_amount;
    goalForm.target_date = goal.target_date ?? '';
    goalForm.linked_account_id = goal.linked_account_id ?? (props.accounts?.[0]?.id ?? null);
    goalForm.status = goal.status;
    showGoalModal.value = true;
}

function submitGoalForm() {
    if (editingGoalId.value) {
        goalForm.put(`/goals/${editingGoalId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                showGoalModal.value = false;
                if (selectedGoal.value && selectedGoal.value.id === editingGoalId.value) {
                    const updated = props.goals.find(g => g.id === editingGoalId.value);
                    if (updated) selectedGoal.value = updated;
                }
            },
        });
    } else {
        goalForm.post('/goals', {
            preserveScroll: true,
            onSuccess: () => {
                showGoalModal.value = false;
                goalForm.reset();
            },
        });
    }
}

function toggleGoalStatus(goal: GoalItem) {
    const nextStatus = goal.status === 'active' ? 'paused' : 'active';
    router.put(`/goals/${goal.id}`, {
        name: goal.name,
        target_amount: goal.target_amount,
        target_date: goal.target_date,
        linked_account_id: goal.linked_account_id,
        status: nextStatus,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            if (selectedGoal.value?.id === goal.id) {
                selectedGoal.value.status = nextStatus;
            }
        },
    });
}

function deleteGoal(goal: GoalItem) {
    if (!confirm(`Hapus target "${goal.name}"? Seluruh riwayat simpanan akan dihapus.`)) return;
    router.delete(`/goals/${goal.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            if (selectedGoal.value?.id === goal.id) {
                closeGoalDrawer();
            }
        },
    });
}

// Icon helper
function getGoalIcon(name: string) {
    const lower = name.toLowerCase();
    if (lower.includes('laptop') || lower.includes('macbook') || lower.includes('pc') || lower.includes('gadget') || lower.includes('hp')) {
        return Laptop;
    }
    if (lower.includes('darurat') || lower.includes('emergency') || lower.includes('asuransi') || lower.includes('proteksi')) {
        return ShieldCheck;
    }
    if (lower.includes('liburan') || lower.includes('trip') || lower.includes('wisata') || lower.includes('tiket') || lower.includes('jepang')) {
        return Plane;
    }
    return Target;
}

// Radial progress calculation helper
const RADIUS = 26;
const CIRCUMFERENCE = 2 * Math.PI * RADIUS; // ~163.36

function getStrokeDashoffset(percent: number) {
    const clamped = Math.min(100, Math.max(0, percent));
    return CIRCUMFERENCE * (1 - clamped / 100);
}
</script>

<template>
    <Head title="Target Keuangan (Goals) - KASHA" />

    <div class="max-w-[1240px] w-full mx-auto px-4 sm:px-6 py-6 flex flex-col gap-6">
        <!-- Top Action / Breadcrumb Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2">
                    <span class="text-xs uppercase tracking-wider font-semibold text-emerald-600 dark:text-emerald-400">Perencanaan Finansial</span>
                    <span class="text-muted-foreground text-xs">•</span>
                    <span class="text-xs text-muted-foreground">Akumulasi Aset Masa Depan</span>
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 flex items-center gap-2.5">
                    Target Keuangan (Goals)
                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 text-xs font-semibold">
                        {{ kpi?.active_count ?? goals.filter(g => g.status === 'active').length }} Berjalan
                    </span>
                </h1>
                <p class="text-sm text-muted-foreground max-w-2xl">
                    Pantau akumulasi tabungan berjangka, pos investasi otomatis, dan impian finansial masa depan Anda secara terukur.
                </p>
            </div>

            <div class="flex items-center gap-2 self-start md:self-auto">
                <div class="inline-flex rounded-lg bg-slate-100 dark:bg-slate-800 p-0.5 border border-slate-200 dark:border-slate-700">
                    <button
                        @click="filterStatus = 'all'"
                        :class="['px-3 py-1.5 rounded-md text-xs font-medium transition-all', filterStatus === 'all' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 shadow-sm' : 'text-muted-foreground hover:text-slate-900 dark:hover:text-slate-100']"
                    >
                        Semua
                    </button>
                    <button
                        @click="filterStatus = 'active'"
                        :class="['px-3 py-1.5 rounded-md text-xs font-medium transition-all', filterStatus === 'active' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 shadow-sm' : 'text-muted-foreground hover:text-slate-900 dark:hover:text-slate-100']"
                    >
                        Aktif
                    </button>
                    <button
                        @click="filterStatus = 'paused'"
                        :class="['px-3 py-1.5 rounded-md text-xs font-medium transition-all', filterStatus === 'paused' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 shadow-sm' : 'text-muted-foreground hover:text-slate-900 dark:hover:text-slate-100']"
                    >
                        Dijeda
                    </button>
                    <button
                        @click="filterStatus = 'completed'"
                        :class="['px-3 py-1.5 rounded-md text-xs font-medium transition-all', filterStatus === 'completed' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-100 shadow-sm' : 'text-muted-foreground hover:text-slate-900 dark:hover:text-slate-100']"
                    >
                        Selesai
                    </button>
                </div>

                <Button @click="openCreateGoal" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium shadow-sm">
                    <Plus class="h-4 w-4 mr-1.5" />
                    Buat Target Baru
                </Button>
            </div>
        </div>

        <!-- Top Summary KPI Strip -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- KPI 1: Total Tabungan -->
            <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 w-28 h-28 bg-emerald-50 dark:bg-emerald-950/20 rounded-full -z-0 opacity-60 group-hover:scale-110 transition-transform duration-300"></div>
                <div class="relative z-10 flex flex-col gap-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Total Tabungan Goals</span>
                        <span class="p-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400">
                            <PiggyBank class="h-4 w-4" />
                        </span>
                    </div>
                    <div class="flex items-baseline gap-2 mt-1">
                        <span class="text-2xl font-bold text-slate-900 dark:text-slate-100 tabular-nums">
                            {{ format(kpi?.total_contributed ?? 0) }}
                        </span>
                    </div>
                    <div class="text-xs text-muted-foreground flex items-center gap-1.5 mt-0.5">
                        <span>Terkumpul dari</span>
                        <span class="font-medium text-slate-800 dark:text-slate-200">{{ format(kpi?.total_target ?? 0) }}</span>
                        <span class="px-1.5 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 font-semibold text-[11px]">
                            {{ kpi?.overall_percent ?? 0 }}% tercapai
                        </span>
                    </div>
                </div>
                <div class="relative z-10 mt-4 pt-2">
                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                        <div class="bg-emerald-600 h-full rounded-full transition-all duration-500" :style="`width: ${Math.min(100, kpi?.overall_percent ?? 0)}%;`"></div>
                    </div>
                    <div class="flex justify-between items-center text-[11px] text-muted-foreground mt-1.5">
                        <span>Sisa komitmen: {{ format(kpi?.remaining_commitment ?? 0) }}</span>
                        <span class="text-emerald-600 dark:text-emerald-400 font-medium">{{ kpi?.overall_percent ?? 0 }}%</span>
                    </div>
                </div>
            </div>

            <!-- KPI 2: Target Aktif -->
            <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 w-28 h-28 bg-slate-100 dark:bg-slate-800/30 rounded-full -z-0 opacity-60 group-hover:scale-110 transition-transform duration-300"></div>
                <div class="relative z-10 flex flex-col gap-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Target Aktif</span>
                        <span class="p-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                            <Flag class="h-4 w-4" />
                        </span>
                    </div>
                    <div class="flex items-baseline gap-2 mt-1">
                        <span class="text-2xl font-bold text-slate-900 dark:text-slate-100">
                            {{ kpi?.active_count ?? 0 }} Target Berjalan
                        </span>
                    </div>
                    <div class="text-xs text-muted-foreground flex items-center gap-1.5 mt-0.5">
                        <span v-if="(kpi?.nearing_count ?? 0) > 0" class="flex items-center gap-1 text-amber-600 dark:text-amber-400 font-medium">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            {{ kpi?.nearing_count }} Mendekati Selesai (≥ 75%)
                        </span>
                        <span v-else class="text-slate-500">Seluruh target berjalan konsisten</span>
                    </div>
                </div>
                <div class="relative z-10 mt-4 pt-2 flex items-center justify-between text-xs text-muted-foreground">
                    <div class="flex -space-x-1.5">
                        <div v-for="i in Math.min(3, kpi?.active_count || 1)" :key="i" class="w-6 h-6 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 ring-2 ring-white dark:ring-slate-900 flex items-center justify-center font-bold text-[10px]">
                            {{ i }}
                        </div>
                    </div>
                    <span class="text-[11px] text-muted-foreground">Rata-rata laju simpanan terukur</span>
                </div>
            </div>

            <!-- KPI 3: Alokasi Rutin Bulanan -->
            <div class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 w-28 h-28 bg-emerald-50 dark:bg-emerald-950/20 rounded-full -z-0 opacity-60 group-hover:scale-110 transition-transform duration-300"></div>
                <div class="relative z-10 flex flex-col gap-1">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Alokasi Rutin Bulanan</span>
                        <span class="p-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400">
                            <RotateCw class="h-4 w-4" />
                        </span>
                    </div>
                    <div class="flex items-baseline gap-2 mt-1">
                        <span class="text-2xl font-bold text-slate-900 dark:text-slate-100 tabular-nums">
                            {{ format(kpi?.monthly_allocation ?? 0) }}
                        </span>
                        <span class="text-xs text-muted-foreground font-normal">/ bulan</span>
                    </div>
                    <div class="text-xs text-emerald-600 dark:text-emerald-400 flex items-center gap-1 mt-0.5">
                        <CheckCircle2 class="h-3.5 w-3.5" />
                        <span>Estimasi kebutuhan untuk capai tenggat</span>
                    </div>
                </div>
                <div class="relative z-10 mt-4 pt-2 flex items-center justify-between">
                    <span class="text-xs text-muted-foreground">Target aktif:</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 font-medium text-xs">
                        {{ filteredGoals.length }} Sasaran
                    </span>
                </div>
            </div>
        </div>

        <!-- Goals Grid Section -->
        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Daftar Impian & Target</h2>
                    <span class="text-xs text-muted-foreground">• {{ filteredGoals.length }} ditampilkan</span>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="!filteredGoals.length" class="text-center py-16 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-8 flex flex-col items-center justify-center">
                <div class="w-14 h-14 rounded-full bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-3">
                    <PiggyBank class="h-7 w-7" />
                </div>
                <h3 class="font-semibold text-base text-slate-900 dark:text-slate-100">Belum ada target keuangan</h3>
                <p class="text-sm text-muted-foreground max-w-sm mt-1">Mulai tetapkan impian finansial Anda seperti tabungan dana darurat, liburan, atau pembelian gadget.</p>
                <Button @click="openCreateGoal" class="mt-4 bg-emerald-600 hover:bg-emerald-700 text-white font-medium">
                    <Plus class="h-4 w-4 mr-1.5" /> Buat Target Sekarang
                </Button>
            </div>

            <!-- Goals Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div
                    v-for="goal in filteredGoals"
                    :key="goal.id"
                    class="bg-white dark:bg-slate-900 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between relative group"
                >
                    <div class="flex flex-col gap-4">
                        <!-- Card Header -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-11 h-11 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                                    <component :is="getGoalIcon(goal.name)" class="h-6 w-6" />
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <h3 class="font-semibold text-sm text-slate-900 dark:text-slate-100 truncate" :title="goal.name">
                                        {{ goal.name }}
                                    </h3>
                                    <span class="text-xs text-muted-foreground truncate">
                                        {{ goal.linked_account?.name ?? 'Akun Pribadi' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Badge Status -->
                            <span
                                v-if="goal.status === 'paused'"
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-medium flex-shrink-0"
                            >
                                Dijeda
                            </span>
                            <span
                                v-else-if="goal.progress_percent >= 100"
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 text-xs font-semibold flex-shrink-0"
                            >
                                <CheckCircle2 class="h-3 w-3 text-emerald-600" />
                                Tercapai
                            </span>
                            <span
                                v-else-if="goal.health_status === 'Ahead of Schedule'"
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 text-xs font-semibold flex-shrink-0"
                            >
                                <Sparkles class="h-3 w-3 text-emerald-600" />
                                Ahead of Schedule
                            </span>
                            <span
                                v-else-if="goal.health_status === 'Perlu Tambahan'"
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-400 text-xs font-semibold flex-shrink-0"
                            >
                                <AlertCircle class="h-3 w-3 text-amber-600" />
                                Perlu Tambahan
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 text-xs font-semibold flex-shrink-0"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                                On Track
                            </span>
                        </div>

                        <!-- Progress Box with Radial Gauge -->
                        <div class="flex items-center justify-between p-3.5 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-100 dark:border-slate-800">
                            <div class="flex flex-col">
                                <span class="text-xs text-muted-foreground">Terkumpul</span>
                                <span class="text-base font-bold text-slate-900 dark:text-slate-100 tabular-nums">
                                    {{ format(goal.contributed_amount) }}
                                </span>
                                <span class="text-xs text-muted-foreground mt-0.5">
                                    dari {{ format(goal.target_amount) }}
                                </span>
                            </div>

                            <!-- Radial SVG Ring -->
                            <div class="relative w-14 h-14 flex items-center justify-center">
                                <svg class="w-14 h-14 transform -rotate-90" viewBox="0 0 64 64">
                                    <circle
                                        class="text-slate-200 dark:text-slate-700"
                                        cx="32"
                                        cy="32"
                                        fill="transparent"
                                        r="26"
                                        stroke="currentColor"
                                        stroke-width="5"
                                    />
                                    <circle
                                        :class="goal.progress_percent >= 100 ? 'text-emerald-600' : (goal.health_status === 'Perlu Tambahan' ? 'text-amber-500' : 'text-emerald-500')"
                                        cx="32"
                                        cy="32"
                                        fill="transparent"
                                        r="26"
                                        stroke="currentColor"
                                        :stroke-dasharray="CIRCUMFERENCE"
                                        :stroke-dashoffset="getStrokeDashoffset(goal.progress_percent)"
                                        stroke-linecap="round"
                                        stroke-width="5"
                                    />
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-xs font-bold text-slate-900 dark:text-slate-100">
                                        {{ goal.progress_percent }}%
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Metadata details -->
                        <div class="flex flex-col gap-1.5 text-xs">
                            <div class="flex items-center justify-between py-1 border-b border-slate-100 dark:border-slate-800">
                                <span class="text-muted-foreground flex items-center gap-1.5">
                                    <Calendar class="h-3.5 w-3.5 text-muted-foreground" /> Tenggat Waktu
                                </span>
                                <span class="font-medium text-slate-900 dark:text-slate-100">
                                    <template v-if="goal.target_date">
                                        {{ new Date(goal.target_date).toLocaleDateString('id-ID', { month: 'short', year: 'numeric' }) }}
                                        <span v-if="goal.months_remaining !== null" class="text-muted-foreground">({{ goal.months_remaining }} bln lagi)</span>
                                    </template>
                                    <template v-else>-</template>
                                </span>
                            </div>

                            <div class="flex items-center justify-between py-1 border-b border-slate-100 dark:border-slate-800">
                                <span class="text-muted-foreground flex items-center gap-1.5">
                                    <CreditCard class="h-3.5 w-3.5 text-muted-foreground" /> Rekomendasi
                                </span>
                                <span class="font-medium text-slate-900 dark:text-slate-100 tabular-nums">
                                    <template v-if="goal.monthly_recommendation">
                                        {{ format(goal.monthly_recommendation) }} / bln
                                    </template>
                                    <template v-else>-</template>
                                </span>
                            </div>

                            <div class="flex items-center justify-between py-1">
                                <span class="text-muted-foreground flex items-center gap-1.5">
                                    <Building2 class="h-3.5 w-3.5 text-muted-foreground" /> Rek. Penampung
                                </span>
                                <span class="font-medium text-slate-900 dark:text-slate-100 truncate max-w-[140px]">
                                    {{ goal.linked_account?.name ?? 'Akun Umum' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Card Actions -->
                    <div class="mt-4 pt-3 flex flex-col gap-2">
                        <Button
                            @click="openGoalDrawer(goal)"
                            class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs h-9 flex items-center justify-center gap-1.5 shadow-sm"
                        >
                            <Plus class="h-3.5 w-3.5" />
                            + Setor Dana (Contribute)
                        </Button>
                        <button
                            @click="openGoalDrawer(goal)"
                            type="button"
                            class="w-full h-8 px-3 rounded-lg bg-slate-50 hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-medium flex items-center justify-center gap-1 transition-colors"
                        >
                            <span>Lihat Detail History</span>
                            <ArrowRight class="h-3 w-3" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Motivational Insight Banner -->
        <div class="p-5 bg-gradient-to-r from-emerald-50 via-slate-50 to-white dark:from-emerald-950/30 dark:via-slate-900 dark:to-slate-900 rounded-xl border border-emerald-100 dark:border-emerald-950/50 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-xl bg-white dark:bg-slate-800 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shadow-sm flex-shrink-0 border border-slate-100 dark:border-slate-700">
                    <TrendingUp class="h-6 w-6" />
                </div>
                <div class="flex flex-col">
                    <span class="font-semibold text-sm text-slate-900 dark:text-slate-100">Proyeksi Otomatisasi KASHA</span>
                    <p class="text-xs text-muted-foreground mt-0.5">
                        Pertahankan konsistensi setoran bulanan Anda untuk memastikan target pensiun, dana darurat, dan impian finansial tercapai sesuai rencana.
                    </p>
                </div>
            </div>
            <Button
                variant="outline"
                @click="openCreateGoal"
                class="whitespace-nowrap text-xs bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700"
            >
                + Tambah Sasaran Finansial
            </Button>
        </div>
    </div>

    <!-- GOAL DETAIL DRAWER / RIGHT PANEL OVERLAY -->
    <Teleport to="body">
        <transition
            enter-active-class="transition-opacity duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isDrawerOpen"
                class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-50"
                @click="closeGoalDrawer"
            />
        </transition>

        <transition
            enter-active-class="transition-transform duration-300 ease-out"
            enter-from-class="translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transition-transform duration-200 ease-in"
            leave-from-class="translate-x-0"
            leave-to-class="translate-x-full"
        >
            <div
                v-if="isDrawerOpen && selectedGoal"
                class="fixed top-0 right-0 h-full w-full max-w-[480px] bg-white dark:bg-slate-900 shadow-2xl z-50 flex flex-col justify-between border-l border-slate-200 dark:border-slate-800"
            >
                <!-- Drawer Header -->
                <div class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between sticky top-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm z-20">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <component :is="getGoalIcon(selectedGoal.name)" class="h-5 w-5" />
                        </div>
                        <div class="flex flex-col">
                            <span class="font-semibold text-sm text-slate-900 dark:text-slate-100 leading-tight">
                                {{ selectedGoal.name }}
                            </span>
                            <span class="text-xs text-muted-foreground">Detail Target &amp; Riwayat Setoran</span>
                        </div>
                    </div>
                    <button
                        @click="closeGoalDrawer"
                        type="button"
                        class="p-2 rounded-lg text-muted-foreground hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <!-- Drawer Content Body -->
                <div class="p-5 flex flex-col gap-5 flex-1 overflow-y-auto">
                    <!-- Big Stats Block -->
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-100 dark:border-slate-800 flex flex-col gap-3">
                        <div class="flex justify-between items-start">
                            <div class="flex flex-col">
                                <span class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Terkumpul Saat Ini</span>
                                <span class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 tabular-nums mt-0.5">
                                    {{ format(selectedGoal.contributed_amount) }}
                                </span>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 text-xs font-semibold">
                                {{ selectedGoal.progress_percent }}% Tercapai
                            </span>
                        </div>
                        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2 overflow-hidden">
                            <div class="bg-emerald-600 h-full rounded-full transition-all duration-300" :style="`width: ${selectedGoal.progress_percent}%;`"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 pt-1 text-xs">
                            <div class="flex flex-col">
                                <span class="text-muted-foreground">Total Target:</span>
                                <span class="font-medium text-slate-900 dark:text-slate-100 tabular-nums">{{ format(selectedGoal.target_amount) }}</span>
                            </div>
                            <div class="flex flex-col text-right">
                                <span class="text-muted-foreground">Sisa Kekurangan:</span>
                                <span class="font-medium text-rose-600 dark:text-rose-400 tabular-nums">-{{ format(selectedGoal.remaining_amount) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Projection Timeline Box -->
                    <div class="p-3.5 bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-950/40 rounded-xl flex flex-col gap-1 text-xs">
                        <div class="flex items-center gap-1.5 text-emerald-700 dark:text-emerald-300 font-semibold">
                            <CheckCircle2 class="h-4 w-4" />
                            <span>Proyeksi Capaian</span>
                        </div>
                        <p class="text-slate-700 dark:text-slate-300 mt-1">
                            <template v-if="selectedGoal.target_date">
                                Ditargetkan selesai pada <span class="font-bold">{{ new Date(selectedGoal.target_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}</span>.
                                <span v-if="selectedGoal.monthly_recommendation" class="block mt-0.5 text-muted-foreground">
                                    Kebutuhan menabung bulanan: <strong class="text-slate-900 dark:text-slate-100">{{ format(selectedGoal.monthly_recommendation) }}/bln</strong>.
                                </span>
                            </template>
                            <template v-else>
                                Belum ada batas waktu spesifik. Anda dapat menabung secara fleksibel setiap bulan.
                            </template>
                        </p>
                    </div>

                    <!-- Quick Action: Tambah Setoran Sekarang -->
                    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-800 flex flex-col gap-3">
                        <label class="text-xs text-slate-900 dark:text-slate-100 font-semibold flex items-center justify-between">
                            <span>Tambah Setoran Cepat</span>
                            <span class="text-[11px] text-emerald-600 font-normal">{{ selectedGoal.linked_account?.name ?? 'Akun Penampung' }}</span>
                        </label>

                        <div class="relative">
                            <div class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-muted-foreground">Rp</div>
                            <input
                                v-model.number="quickAmount"
                                type="number"
                                min="1000"
                                class="w-full h-11 pl-9 pr-3 rounded-lg bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-bold text-lg border border-slate-200 dark:border-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 shadow-sm tabular-nums"
                            />
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                @click="addQuickAmount(100000)"
                                type="button"
                                class="flex-1 py-1 px-2 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-medium text-slate-700 dark:text-slate-300 shadow-xs transition-colors text-center"
                            >
                                +100rb
                            </button>
                            <button
                                @click="addQuickAmount(500000)"
                                type="button"
                                class="flex-1 py-1 px-2 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-medium text-slate-700 dark:text-slate-300 shadow-xs transition-colors text-center"
                            >
                                +500rb
                            </button>
                            <button
                                @click="addQuickAmount(1000000)"
                                type="button"
                                class="flex-1 py-1 px-2 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-medium text-slate-700 dark:text-slate-300 shadow-xs transition-colors text-center"
                            >
                                +1jt
                            </button>
                        </div>

                        <Button
                            @click="submitQuickContribute"
                            :disabled="contributeForm.processing || quickAmount <= 0"
                            class="w-full h-10 mt-1 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs shadow-sm flex items-center justify-center gap-1.5"
                        >
                            <PiggyBank class="h-4 w-4" />
                            <span>Konfirmasi Setoran Sekarang</span>
                        </Button>
                    </div>

                    <!-- Riwayat Setoran -->
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Riwayat Setoran Terbaru</h4>
                            <span class="text-xs text-muted-foreground">{{ selectedGoal.contributions?.length || 0 }} Transaksi</span>
                        </div>

                        <div v-if="!selectedGoal.contributions?.length" class="text-center py-6 text-xs text-muted-foreground bg-slate-50 dark:bg-slate-800/40 rounded-lg border border-dashed border-slate-200 dark:border-slate-800">
                            Belum ada riwayat setoran untuk target ini.
                        </div>

                        <div v-else class="flex flex-col gap-2">
                            <div
                                v-for="c in selectedGoal.contributions"
                                :key="c.id"
                                class="p-3 bg-slate-50 dark:bg-slate-800/40 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg border border-slate-100 dark:border-slate-800 flex items-center justify-between transition-colors"
                            >
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                        <RotateCw class="h-4 w-4" />
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-xs font-medium text-slate-900 dark:text-slate-100">Setoran Simpanan</span>
                                        <span class="text-[11px] text-muted-foreground">{{ new Date(c.contributed_on).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}</span>
                                    </div>
                                </div>
                                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 tabular-nums">
                                    +{{ format(c.amount) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Drawer Footer Actions -->
                <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 flex items-center justify-between gap-2 sticky bottom-0 z-20">
                    <div class="flex items-center gap-1">
                        <button
                            @click="openEditGoal(selectedGoal)"
                            type="button"
                            class="p-2 text-muted-foreground hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-200 dark:hover:bg-slate-800 rounded-lg transition-colors"
                            title="Ubah Target"
                        >
                            <Pencil class="h-4 w-4" />
                        </button>
                        <button
                            @click="toggleGoalStatus(selectedGoal)"
                            type="button"
                            class="p-2 text-muted-foreground hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/50 rounded-lg transition-colors"
                            :title="selectedGoal.status === 'active' ? 'Jeda Target' : 'Aktifkan Target'"
                        >
                            <PauseCircle v-if="selectedGoal.status === 'active'" class="h-4 w-4" />
                            <PlayCircle v-else class="h-4 w-4" />
                        </button>
                        <button
                            @click="deleteGoal(selectedGoal)"
                            type="button"
                            class="p-2 text-muted-foreground hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50 rounded-lg transition-colors"
                            title="Hapus Target"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </div>

                    <Button variant="outline" size="sm" @click="closeGoalDrawer">
                        Tutup Panel
                    </Button>
                </div>
            </div>
        </transition>
    </Teleport>

    <!-- Modal Form: Tambah / Edit Target -->
    <Teleport to="body">
        <div v-if="showGoalModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs p-4" @click.self="showGoalModal = false">
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xl w-full max-w-md p-6">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">
                        {{ editingGoalId ? 'Edit Target Finansial' : 'Buat Target Baru' }}
                    </h2>
                    <button @click="showGoalModal = false" class="text-muted-foreground hover:text-slate-900 dark:hover:text-slate-100">
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <form @submit.prevent="submitGoalForm" class="space-y-4">
                    <div>
                        <label class="text-xs font-medium text-slate-700 dark:text-slate-300">Nama Target / Impian</label>
                        <input
                            v-model="goalForm.name"
                            placeholder="Contoh: Dana Darurat, MacBook Pro, Liburan Jepang"
                            required
                            class="w-full h-10 rounded-lg border border-slate-200 dark:border-slate-700 px-3 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 mt-1 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                        />
                    </div>

                    <div>
                        <label class="text-xs font-medium text-slate-700 dark:text-slate-300">Target Nominal (Rp)</label>
                        <input
                            v-model.number="goalForm.target_amount"
                            type="number"
                            min="1000"
                            placeholder="Contoh: 15000000"
                            required
                            class="w-full h-10 rounded-lg border border-slate-200 dark:border-slate-700 px-3 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 mt-1 focus:ring-2 focus:ring-emerald-500 focus:outline-none tabular-nums"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-medium text-slate-700 dark:text-slate-300">Tenggat Waktu</label>
                            <input
                                v-model="goalForm.target_date"
                                type="date"
                                class="w-full h-10 rounded-lg border border-slate-200 dark:border-slate-700 px-3 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 mt-1 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                            />
                        </div>

                        <div>
                            <label class="text-xs font-medium text-slate-700 dark:text-slate-300">Rekening Penampung</label>
                            <select
                                v-model="goalForm.linked_account_id"
                                class="w-full h-10 rounded-lg border border-slate-200 dark:border-slate-700 px-3 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 mt-1 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                            >
                                <option :value="null">Akun Umum / Tanpa Khusus</option>
                                <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                                    {{ acc.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div v-if="editingGoalId">
                        <label class="text-xs font-medium text-slate-700 dark:text-slate-300">Status</label>
                        <select
                            v-model="goalForm.status"
                            class="w-full h-10 rounded-lg border border-slate-200 dark:border-slate-700 px-3 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 mt-1 focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                        >
                            <option value="active">Aktif (Berjalan)</option>
                            <option value="paused">Dijeda (Paused)</option>
                            <option value="completed">Selesai (Completed)</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <Button type="button" variant="ghost" @click="showGoalModal = false">Batal</Button>
                        <Button type="submit" :disabled="goalForm.processing" class="bg-emerald-600 hover:bg-emerald-700 text-white">
                            {{ editingGoalId ? 'Simpan Perubahan' : 'Buat Target' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

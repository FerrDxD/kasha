<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useMoney } from '@/composables/useMoney';
import type { Account, Transaction, Budget, RecurringTransaction, Goal } from '@/types';
import {
    ArrowUp,
    ArrowDown,
    ArrowRight,
    TrendingUp,
    AlertTriangle,
    Wallet,
    Building2,
    Smartphone,
    Banknote,
    Laptop,
    CheckCircle2,
    Calendar,
    ChevronDown,
    ChevronRight,
    Plus,
    Filter,
} from '@lucide/vue';

const props = defineProps<{
    total_balance: number;
    accounts: (Account & { balance: number })[];
    month_income: number;
    month_expense: number;
    month_net: number;
    prev_net: number;
    budgets: (Budget & { category: { name: string; icon: string | null } | null })[];
    upcoming: RecurringTransaction[];
    goals: Goal[];
    recent_transactions: Transaction[];
    period: string;
}>();

const { format, compact } = useMoney();

const netDelta = props.prev_net !== 0
    ? Math.round(((props.month_net - props.prev_net) / Math.abs(props.prev_net)) * 100)
    : 0;

const savingRatio = props.month_income > 0
    ? Math.max(0, Math.round((props.month_net / props.month_income) * 100))
    : 0;

function budgetColor(pct: number) {
    if (pct >= 100) return 'bg-rose-600';
    if (pct >= 70) return 'bg-amber-500';
    return 'bg-emerald-600';
}

function budgetTextColor(pct: number) {
    if (pct >= 100) return 'text-rose-600';
    if (pct >= 70) return 'text-amber-500';
    return 'text-emerald-600';
}

function accountIcon(type: string) {
    switch (type) {
        case 'bank': return Building2;
        case 'ewallet': return Smartphone;
        case 'cash': return Banknote;
        default: return Wallet;
    }
}

const periodLabel = new Date(props.period + '-01').toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
const primaryGoal = props.goals[0] ?? null;
</script>

<template>
    <Head title="Dashboard" />

    <div class="max-w-[1200px] mx-auto p-4 sm:p-6 lg:p-6 flex flex-col gap-6">
        <!-- Content Header Context -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">Dashboard</h1>
                <div class="relative inline-flex items-center">
                    <select class="appearance-none bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-900 dark:text-slate-100 text-sm font-medium pl-3 pr-8 py-2 rounded-lg cursor-pointer focus:outline-none transition-colors">
                        <option selected>{{ periodLabel }}</option>
                    </select>
                    <ChevronDown class="pointer-events-none absolute right-2 h-4 w-4 text-muted-foreground" />
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button class="inline-flex items-center gap-1.5 px-4 h-10 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100 hover:bg-slate-200 dark:hover:bg-slate-700 text-sm font-medium transition-colors shadow-sm" type="button">
                    <Filter class="h-4 w-4" />
                    <span>Filter</span>
                </button>
                <Link href="/transactions" class="inline-flex items-center gap-1.5 px-4 h-10 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium transition-colors shadow-sm">
                    <Plus class="h-4 w-4" />
                    <span>+ Add</span>
                </Link>
            </div>
        </div>

        <!-- Hero Section: Current Balance & Account Liquidity -->
        <section class="rounded-xl border bg-card p-5 shadow-sm">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                <!-- Left side: Total Balance overview -->
                <div class="lg:col-span-7 flex flex-col justify-center">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs uppercase tracking-wider text-muted-foreground font-semibold">Total Saldo Terkonsolidasi</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 text-xs font-medium">
                            Aktif
                        </span>
                    </div>
                    <div class="flex flex-wrap items-baseline gap-3 mb-2">
                        <span class="text-3xl sm:text-4xl font-bold tracking-tight text-slate-900 dark:text-white tabular-nums">
                            {{ format(total_balance) }}
                        </span>
                        <div class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold"
                            :class="netDelta >= 0 ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40' : 'bg-rose-50 text-rose-600 dark:bg-rose-950/40'">
                            <ArrowUp v-if="netDelta >= 0" class="h-3 w-3" />
                            <ArrowDown v-else class="h-3 w-3" />
                            <span class="tabular-nums">{{ Math.abs(netDelta) }}%</span>
                            <span class="text-muted-foreground font-normal">vs bulan lalu</span>
                        </div>
                    </div>
                    <p class="text-sm text-muted-foreground leading-relaxed">
                        Kondisi kas terkendali. Likuiditas optimal untuk menopang kewajiban rutin hingga penutupan siklus buku.
                    </p>
                </div>

                <!-- Right side: Compact Accounts List -->
                <div class="lg:col-span-5 bg-slate-50/70 dark:bg-slate-900/50 rounded-xl p-3.5 flex flex-col gap-2.5 border">
                    <div class="flex items-center justify-between pb-1">
                        <span class="text-xs uppercase tracking-wider text-muted-foreground font-semibold">Rekening &amp; Dompet</span>
                        <Link href="/accounts" class="text-xs text-emerald-600 hover:underline">
                            {{ accounts.length }} Akun Terhubung
                        </Link>
                    </div>
                    <div v-for="acc in accounts.slice(0, 3)" :key="acc.id"
                        class="flex items-center justify-between p-2.5 rounded-lg bg-card border shadow-xs hover:border-slate-300 transition-colors">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center shrink-0"
                                :style="acc.color ? `background: ${acc.color}15; color: ${acc.color}` : ''">
                                <component :is="accountIcon(acc.type)" class="h-4 w-4" />
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span class="text-sm font-medium text-slate-900 dark:text-slate-100 truncate">{{ acc.name }}</span>
                                <span class="text-xs text-muted-foreground capitalize">{{ acc.type }}</span>
                            </div>
                        </div>
                        <span class="text-sm font-semibold text-slate-900 dark:text-slate-100 tabular-nums">
                            {{ format(acc.balance) }}
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Grid Row 1: Income vs Expense & Budget -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Card: Income vs Expense -->
            <div class="rounded-xl border bg-card p-5 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Income vs Expense</h2>
                            <p class="text-xs text-muted-foreground">Arus kas perbandingan bulan {{ periodLabel }}</p>
                        </div>
                        <div class="text-right">
                            <span class="text-xs uppercase tracking-wider text-muted-foreground block">Net Saving</span>
                            <span class="text-base font-bold tabular-nums inline-flex items-center gap-1"
                                :class="month_net >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                                <span>{{ month_net >= 0 ? '▲ +' : '▼ -' }}</span>{{ format(Math.abs(month_net)) }}
                            </span>
                        </div>
                    </div>

                    <!-- Horizontal Comparative Bars -->
                    <div class="space-y-4 pt-2">
                        <!-- Income Bar -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between text-sm font-medium">
                                <span class="flex items-center gap-2 text-slate-800 dark:text-slate-200">
                                    <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                                    Total Pemasukan
                                </span>
                                <span class="text-emerald-600 font-semibold tabular-nums">+{{ format(month_income) }}</span>
                            </div>
                            <div class="w-full h-2.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-600 rounded-full transition-all duration-500"
                                    :style="`width: ${month_income > 0 ? 100 : 0}%;`" />
                            </div>
                        </div>

                        <!-- Expense Bar -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between text-sm font-medium">
                                <span class="flex items-center gap-2 text-slate-800 dark:text-slate-200">
                                    <span class="w-2 h-2 rounded-full bg-rose-600"></span>
                                    Total Pengeluaran
                                </span>
                                <span class="text-rose-600 font-semibold tabular-nums">-{{ format(month_expense) }}</span>
                            </div>
                            <div class="w-full h-2.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-rose-600 rounded-full transition-all duration-500"
                                    :style="`width: ${month_income > 0 ? Math.min(100, Math.round((month_expense / month_income) * 100)) : 0}%;`" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-3 border-t flex items-center justify-between text-xs text-muted-foreground">
                    <span>Rasio Tabungan: <strong class="text-slate-900 dark:text-white tabular-nums">{{ savingRatio }}%</strong> dari total penerimaan</span>
                    <span class="inline-flex items-center gap-1 text-emerald-600 font-medium">
                        <CheckCircle2 class="h-3.5 w-3.5" />
                        {{ savingRatio >= 20 ? 'Sehat (≥20%)' : 'Perlu Optimasi' }}
                    </span>
                </div>
            </div>

            <!-- Card: Budgets -->
            <div class="rounded-xl border bg-card p-5 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Budget</h2>
                            <p class="text-xs text-muted-foreground">Batas alokasi pos pengeluaran bulanan</p>
                        </div>
                        <Link href="/budgets" class="text-xs font-medium text-emerald-600 hover:text-emerald-700 inline-flex items-center gap-1 transition-colors">
                            <span>Lihat semua</span>
                            <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>

                    <div class="space-y-4 pt-1">
                        <div v-for="b in budgets.slice(0, 3)" :key="b.category_id" class="space-y-1">
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium text-slate-800 dark:text-slate-200">
                                    {{ b.category_name }}
                                </span>
                                <span class="text-xs font-semibold tabular-nums" :class="budgetTextColor(b.percent)">
                                    {{ b.percent }}% spent
                                </span>
                            </div>
                            <div class="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500" :class="budgetColor(b.percent)" :style="`width: ${Math.min(b.percent, 100)}%;`" />
                            </div>
                            <div class="flex justify-between text-xs text-muted-foreground tabular-nums">
                                <span>{{ compact(b.spent) }} terpakai</span>
                                <span>Plafon {{ compact(b.amount) }}</span>
                            </div>
                        </div>
                        <p v-if="!budgets.length" class="text-sm text-muted-foreground py-4 text-center">
                            Belum ada pagu anggaran. <Link href="/budgets" class="text-emerald-600 underline">Atur sekarang</Link>.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grid Row 2: Cash Flow Trend & Goals -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Card: Cash Flow 30 hari -->
            <div class="rounded-xl border bg-card p-5 shadow-sm flex flex-col justify-between">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Cash Flow 30 hari</h2>
                        <p class="text-xs text-muted-foreground">Tren saldo historis dan simulasi sisa bulan</p>
                    </div>
                    <div class="bg-emerald-50 dark:bg-emerald-950/40 px-3 py-1.5 rounded-lg text-right">
                        <span class="text-[10px] uppercase font-semibold text-emerald-700 dark:text-emerald-400 block leading-none mb-0.5">Saldo Terkini</span>
                        <span class="text-sm font-bold text-emerald-700 dark:text-emerald-400 tabular-nums">{{ compact(total_balance) }}</span>
                    </div>
                </div>

                <!-- Trend Line SVG Chart -->
                <div class="w-full h-40 my-2 relative">
                    <svg class="w-full h-full overflow-visible" preserveAspectRatio="none" viewBox="0 0 500 160">
                        <defs>
                            <linearGradient id="flowGradient" x1="0%" x2="0%" y1="0%" y2="100%">
                                <stop offset="0%" stop-color="#059669" stop-opacity="0.25" />
                                <stop offset="100%" stop-color="#059669" stop-opacity="0" />
                            </linearGradient>
                        </defs>
                        <line stroke="#f1f5f9" stroke-dasharray="4,4" stroke-width="1" x1="0" x2="500" y1="40" y2="40" />
                        <line stroke="#f1f5f9" stroke-dasharray="4,4" stroke-width="1" x1="0" x2="500" y1="80" y2="80" />
                        <line stroke="#f1f5f9" stroke-dasharray="4,4" stroke-width="1" x1="0" x2="500" y1="120" y2="120" />
                        <path d="M 0,130 Q 80,115 140,85 T 260,95 T 380,45 T 500,20 L 500,160 L 0,160 Z" fill="url(#flowGradient)" />
                        <path d="M 0,130 Q 80,115 140,85 T 260,95 T 340,65" fill="none" stroke="#059669" stroke-linecap="round" stroke-width="3" />
                        <path d="M 340,65 T 380,45 T 500,20" fill="none" stroke="#059669" stroke-dasharray="5,5" stroke-linecap="round" stroke-width="2.5" />
                        <circle cx="340" cy="65" fill="#059669" r="5" />
                    </svg>
                </div>

                <div class="flex items-center justify-between text-xs text-muted-foreground pt-2 border-t">
                    <span>Awal Bulan</span>
                    <span class="text-emerald-600 font-medium">Hari Ini: {{ compact(total_balance) }}</span>
                    <Link href="/cash-flow" class="hover:underline text-slate-700 dark:text-slate-300">Detail Simulasi →</Link>
                </div>
            </div>

            <!-- Card: Goals -->
            <div class="rounded-xl border bg-card p-5 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Goals</h2>
                            <p class="text-xs text-muted-foreground">Target simpanan &amp; impian finansial</p>
                        </div>
                        <Link href="/goals" class="text-xs font-medium text-emerald-600 hover:text-emerald-700 inline-flex items-center gap-1 transition-colors">
                            <span>Semua Goals</span>
                            <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>

                    <div v-if="primaryGoal" class="p-4 rounded-xl bg-slate-50/70 dark:bg-slate-900/50 border flex flex-col sm:flex-row items-center gap-4">
                        <!-- Circular Progress Ring Visual -->
                        <div class="relative w-20 h-20 shrink-0 flex items-center justify-center">
                            <svg class="w-full h-full -rotate-90" viewBox="0 0 36 36">
                                <path class="text-slate-200 dark:text-slate-700" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="3.5" />
                                <path class="text-emerald-600 stroke-current transition-all duration-700"
                                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                    fill="none" :stroke-dasharray="`${primaryGoal.progress_percent}, 100`" stroke-linecap="round" stroke-width="3.5" />
                            </svg>
                            <div class="absolute flex flex-col items-center justify-center">
                                <span class="text-sm font-bold text-slate-900 dark:text-white tabular-nums">{{ primaryGoal.progress_percent }}%</span>
                            </div>
                        </div>

                        <!-- Goal Metrics -->
                        <div class="flex-1 w-full space-y-1.5">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ primaryGoal.name }}</h3>
                                <span v-if="primaryGoal.target_date" class="text-xs text-muted-foreground">
                                    {{ new Date(primaryGoal.target_date).toLocaleDateString('id-ID', { month: 'short', year: 'numeric' }) }}
                                </span>
                            </div>
                            <div class="flex justify-between text-xs tabular-nums">
                                <span class="font-semibold text-slate-900 dark:text-white">{{ format(primaryGoal.contributed_amount) }}</span>
                                <span class="text-muted-foreground">dari {{ format(primaryGoal.target_amount) }}</span>
                            </div>
                            <div class="w-full h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-600 rounded-full" :style="`width: ${primaryGoal.progress_percent}%;`" />
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-muted-foreground py-6 text-center">
                        Belum ada target finansial. <Link href="/goals" class="text-emerald-600 underline">Buat goal pertama</Link>.
                    </p>
                </div>

                <div v-if="primaryGoal" class="mt-4 pt-3 border-t flex items-center justify-between text-xs text-muted-foreground">
                    <span>Sisa akumulasi: <strong class="text-slate-900 dark:text-white tabular-nums">{{ format(Math.max(0, primaryGoal.target_amount - primaryGoal.contributed_amount)) }}</strong></span>
                    <Link href="/goals" class="text-emerald-600 hover:underline">Kelola →</Link>
                </div>
            </div>
        </div>

        <!-- Strip: Upcoming 7 hari -->
        <section class="rounded-xl border bg-card p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <Calendar class="h-4 w-4 text-emerald-600" />
                    <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Upcoming 7 Hari</h2>
                </div>
                <span class="text-xs text-muted-foreground">Tagihan &amp; Penerimaan terjadwal</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div v-for="r in upcoming" :key="r.id"
                    class="p-3.5 rounded-lg bg-slate-50/70 dark:bg-slate-900/50 border flex items-center justify-between hover:border-slate-300 transition-colors">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
                            :class="r.type === 'income' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40' : 'bg-rose-50 text-rose-600 dark:bg-rose-950/40'">
                            <span class="font-bold text-xs">{{ r.type === 'income' ? '▲' : '▼' }}</span>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="text-sm font-medium text-slate-900 dark:text-slate-100 truncate">{{ r.payee || r.account?.name }}</span>
                            <span class="text-xs text-muted-foreground">{{ new Date(r.next_run_on).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) }} • {{ r.frequency }}</span>
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="text-sm font-semibold tabular-nums block" :class="r.type === 'income' ? 'text-emerald-600' : 'text-rose-600'">
                            {{ r.type === 'income' ? '+' : '-' }}{{ compact(r.amount) }}
                        </span>
                        <span class="text-[10px] text-muted-foreground capitalize">{{ r.mode }}</span>
                    </div>
                </div>
                <p v-if="!upcoming.length" class="text-sm text-muted-foreground col-span-3 py-3 text-center">
                    Tidak ada jadwal tagihan atau transfer dalam 7 hari ke depan.
                </p>
            </div>
        </section>

        <!-- Recent Transactions Table Card -->
        <section class="rounded-xl border bg-card p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Recent Transactions</h2>
                    <p class="text-xs text-muted-foreground">Catatan riwayat transaksi 5 mutasi terkini</p>
                </div>
                <Link href="/transactions" class="text-xs font-medium text-emerald-600 hover:text-emerald-700 inline-flex items-center gap-1 transition-colors">
                    <span>Buka Jurnal</span>
                    <ChevronRight class="h-3.5 w-3.5" />
                </Link>
            </div>

            <!-- Responsive Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 dark:bg-slate-800 text-muted-foreground text-xs uppercase tracking-wider rounded-lg">
                            <th class="py-3 px-4 rounded-l-lg">Tanggal</th>
                            <th class="py-3 px-4">Penerima / Merchant</th>
                            <th class="py-3 px-4">Kategori</th>
                            <th class="py-3 px-4">Akun</th>
                            <th class="py-3 px-4 text-right rounded-r-lg">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y text-sm">
                        <tr v-for="tx in recent_transactions" :key="tx.id" class="hover:bg-slate-50 dark:hover:bg-slate-900/50 transition-colors">
                            <td class="py-3.5 px-4 text-muted-foreground tabular-nums whitespace-nowrap">
                                {{ new Date(tx.occurred_on).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) }}
                            </td>
                            <td class="py-3.5 px-4 font-medium text-slate-900 dark:text-slate-100 whitespace-nowrap">
                                {{ tx.payee || tx.category?.name || '—' }}
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs">
                                    <span class="font-bold text-xs">{{ tx.type === 'income' ? '▲' : '▼' }}</span>
                                    {{ tx.category?.name || '—' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs">
                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-600"></span>
                                    {{ tx.account?.name || '—' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <span class="font-semibold tabular-nums"
                                    :class="tx.type === 'income' ? 'text-emerald-600' : 'text-rose-600'">
                                    {{ tx.type === 'income' ? '+' : '-' }}{{ format(tx.amount) }} {{ tx.type === 'income' ? '▲' : '▼' }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!recent_transactions.length">
                            <td colspan="5" class="py-6 text-center text-sm text-muted-foreground">
                                Belum ada transaksi tercatat.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useMoney } from '@/composables/useMoney';
import {
    Plus,
    Pencil,
    Trash2,
    PlayCircle,
    CheckCircle2,
    Layers,
    ChevronRight,
    GripVertical,
    Sparkles,
    Check,
    FlaskConical,
    X,
    FolderPlus,
    Tag,
    ShieldAlert,
    RotateCw,
    SlidersHorizontal
} from '@lucide/vue';
import { Button } from '@/components/ui/button';

interface ConditionItem {
    field: 'payee' | 'description' | 'amount' | 'account_id' | 'type';
    op: 'contains' | 'equals' | 'starts_with' | 'not_contains' | 'lt' | 'gt' | 'lte' | 'gte';
    value: string | number;
}

interface ActionItem {
    type: 'set_category' | 'add_tag' | 'set_status';
    value: string;
}

interface RuleItem {
    id: string;
    name: string;
    priority: number;
    stop_processing: boolean;
    is_active: boolean;
    conditions: {
        match: 'all' | 'any';
        conditions: ConditionItem[];
    };
    actions: ActionItem[];
    match_count?: number;
}

interface CategoryItem {
    id: string;
    name: string;
    type: string;
    icon?: string;
    color?: string;
}

interface AccountItem {
    id: string;
    name: string;
}

interface RecentTx {
    id: string;
    transacted_at: string;
    payee: string | null;
    note: string | null;
    amount: number;
    type: 'income' | 'expense' | 'transfer';
    category?: { id: string; name: string } | null;
    account?: { id: string; name: string } | null;
}

interface StatsData {
    total_rules: number;
    active_rules: number;
    total_transactions_month: number;
    automated_transactions: number;
    efficiency_percent: number;
}

const props = defineProps<{
    rules: RuleItem[];
    categories: CategoryItem[];
    accounts: AccountItem[];
    recentTransactions: RecentTx[];
    stats: StatsData;
}>();

const { format } = useMoney();

// Selection & Editing State
const selectedRuleId = ref<string | null>(props.rules[0]?.id ?? null);
const isCreatingNew = ref(false);
const showTestSimulation = ref(false);

const selectedRule = computed(() => {
    return props.rules.find(r => r.id === selectedRuleId.value) || null;
});

// Editor Form State
const editorForm = ref<{
    name: string;
    priority: number;
    stop_processing: boolean;
    is_active: boolean;
    match: 'all' | 'any';
    conditions: ConditionItem[];
    categoryId: string;
    tags: string[];
    newTagInput: string;
}>({
    name: '',
    priority: 1,
    stop_processing: true,
    is_active: true,
    match: 'all',
    conditions: [{ field: 'payee', op: 'contains', value: '' }],
    categoryId: '',
    tags: [],
    newTagInput: '',
});

// Load rule into editor
function loadRuleIntoEditor(rule: RuleItem | null) {
    if (!rule) {
        editorForm.value = {
            name: '',
            priority: (props.rules.length > 0 ? Math.max(...props.rules.map(r => r.priority)) + 1 : 1),
            stop_processing: true,
            is_active: true,
            match: 'all',
            conditions: [{ field: 'payee', op: 'contains', value: '' }],
            categoryId: props.categories[0]?.id ?? '',
            tags: [],
            newTagInput: '',
        };
        return;
    }

    const setCatAction = rule.actions?.find(a => a.type === 'set_category');
    const tagsAction = rule.actions?.filter(a => a.type === 'add_tag').map(a => a.value) || [];

    editorForm.value = {
        name: rule.name,
        priority: rule.priority,
        stop_processing: rule.stop_processing,
        is_active: rule.is_active,
        match: rule.conditions?.match || 'all',
        conditions: rule.conditions?.conditions && rule.conditions.conditions.length > 0
            ? JSON.parse(JSON.stringify(rule.conditions.conditions))
            : [{ field: 'payee', op: 'contains', value: '' }],
        categoryId: setCatAction?.value || (props.categories[0]?.id ?? ''),
        tags: tagsAction,
        newTagInput: '',
    };
}

// Watch initial selected rule
if (selectedRule.value) {
    loadRuleIntoEditor(selectedRule.value);
}

function selectRule(rule: RuleItem) {
    isCreatingNew.value = false;
    selectedRuleId.value = rule.id;
    loadRuleIntoEditor(rule);
}

function startCreateNewRule() {
    isCreatingNew.value = true;
    selectedRuleId.value = null;
    loadRuleIntoEditor(null);
}

function addCondition() {
    editorForm.value.conditions.push({ field: 'payee', op: 'contains', value: '' });
}

function removeCondition(index: number) {
    if (editorForm.value.conditions.length > 1) {
        editorForm.value.conditions.splice(index, 1);
    }
}

function addTag() {
    const val = editorForm.value.newTagInput.trim().replace(/^#/, '');
    if (val && !editorForm.value.tags.includes(val)) {
        editorForm.value.tags.push(val);
    }
    editorForm.value.newTagInput = '';
}

function removeTag(tag: string) {
    editorForm.value.tags = editorForm.value.tags.filter(t => t !== tag);
}

// Toggle rule active status directly from list
function toggleRuleActive(rule: RuleItem) {
    const nextActive = !rule.is_active;
    router.put(`/rules/${rule.id}`, {
        name: rule.name,
        priority: rule.priority,
        stop_processing: rule.stop_processing,
        is_active: nextActive,
        conditions: rule.conditions,
        actions: rule.actions,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            if (selectedRule.value?.id === rule.id) {
                editorForm.value.is_active = nextActive;
            }
        },
    });
}

const isSaving = ref(false);

function saveRule() {
    isSaving.value = true;
    const actions: ActionItem[] = [];
    if (editorForm.value.categoryId) {
        actions.push({ type: 'set_category', value: editorForm.value.categoryId });
    }
    editorForm.value.tags.forEach(tag => {
        actions.push({ type: 'add_tag', value: tag });
    });

    const payload = {
        name: editorForm.value.name,
        priority: editorForm.value.priority,
        stop_processing: editorForm.value.stop_processing,
        is_active: editorForm.value.is_active,
        conditions: {
            match: editorForm.value.match,
            conditions: editorForm.value.conditions,
        },
        actions: actions.length > 0 ? actions : [{ type: 'set_category', value: props.categories[0]?.id ?? '' }],
    };

    if (isCreatingNew.value || !selectedRuleId.value) {
        router.post('/rules', payload, {
            preserveScroll: true,
            onSuccess: () => {
                isSaving.value = false;
                isCreatingNew.value = false;
                if (props.rules.length > 0) {
                    selectedRuleId.value = props.rules[0].id;
                }
            },
            onError: () => {
                isSaving.value = false;
            },
        });
    } else {
        router.put(`/rules/${selectedRuleId.value}`, payload, {
            preserveScroll: true,
            onSuccess: () => {
                isSaving.value = false;
            },
            onError: () => {
                isSaving.value = false;
            },
        });
    }
}

function deleteCurrentRule() {
    if (!selectedRuleId.value) return;
    if (!confirm(`Hapus aturan "${editorForm.value.name}"?`)) return;
    router.delete(`/rules/${selectedRuleId.value}`, {
        preserveScroll: true,
        onSuccess: () => {
            if (props.rules.length > 0) {
                selectedRuleId.value = props.rules[0].id;
                loadRuleIntoEditor(props.rules[0]);
            } else {
                startCreateNewRule();
            }
        },
    });
}

const isApplying = ref(false);
function applyRulesToExisting() {
    if (!confirm('Terapkan semua aturan aktif ke transaksi yang ada sekarang?')) return;
    isApplying.value = true;
    router.post('/rules/apply', {}, {
        preserveScroll: true,
        onFinish: () => {
            isApplying.value = false;
        },
    });
}

// Helpers for Condition Summary formatting
function formatConditionSummary(rule: RuleItem) {
    const conds = rule.conditions?.conditions || [];
    if (!conds.length) return 'Tanpa kriteria';
    return conds.map(c => {
        let fieldName = 'Merchant';
        if (c.field === 'amount') fieldName = 'Nominal';
        else if (c.field === 'account_id') fieldName = 'Akun';
        else if (c.field === 'type') fieldName = 'Tipe';

        let opName = ':';
        if (c.op === 'lt') opName = '<';
        else if (c.op === 'gt') opName = '>';
        else if (c.op === 'equals') opName = '=';

        return `${fieldName} ${opName} "${c.value}"`;
    }).join(' & ');
}

function getCategoryName(catId: string) {
    const c = props.categories.find(item => item.id === catId);
    return c ? c.name : 'Kategori';
}

function getRuleCategoryAction(rule: RuleItem) {
    const action = rule.actions?.find(a => a.type === 'set_category');
    return action ? getCategoryName(action.value) : null;
}

// Test Run simulation matches against recent 20 transactions
const simulatedTransactions = computed(() => {
    return props.recentTransactions.map(tx => {
        let matchedRule: RuleItem | null = null;
        let matchedCategory = tx.category?.name || 'Tanpa Kategori';

        for (const rule of props.rules) {
            if (!rule.is_active) continue;
            const conds = rule.conditions?.conditions || [];
            if (!conds.length) continue;

            const isMatch = conds.every(c => {
                const targetText = ((tx.payee || '') + ' ' + (tx.note || '')).toLowerCase();
                const testVal = String(c.value).toLowerCase();
                if (c.field === 'payee' || c.field === 'description') {
                    if (c.op === 'contains') return targetText.includes(testVal);
                    if (c.op === 'equals') return targetText.trim() === testVal.trim();
                    if (c.op === 'starts_with') return targetText.startsWith(testVal);
                    if (c.op === 'not_contains') return !targetText.includes(testVal);
                } else if (c.field === 'amount') {
                    const amt = Number(tx.amount);
                    const valAmt = Number(testVal);
                    if (c.op === 'lt') return amt < valAmt;
                    if (c.op === 'gt') return amt > valAmt;
                    return amt === valAmt;
                }
                return true;
            });

            if (isMatch) {
                matchedRule = rule;
                const catAct = rule.actions?.find(a => a.type === 'set_category');
                if (catAct) {
                    matchedCategory = getCategoryName(catAct.value);
                }
                if (rule.stop_processing) break;
            }
        }

        return {
            ...tx,
            matchedRule,
            matchedCategory,
        };
    });
});
</script>

<template>
    <Head title="Rules Builder - KASHA" />

    <div class="w-full max-w-[1240px] mx-auto px-4 sm:px-6 py-6 space-y-6">
        <!-- Breadcrumb & Top Bar -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="space-y-1">
                <nav class="flex items-center gap-1.5 text-xs text-muted-foreground">
                    <span class="hover:text-slate-900 dark:hover:text-slate-100 transition-colors">Pengaturan</span>
                    <ChevronRight class="h-3 w-3" />
                    <span class="text-emerald-600 dark:text-emerald-400 font-medium">Automasi</span>
                    <ChevronRight class="h-3 w-3" />
                    <span class="text-slate-800 dark:text-slate-200">Rules Builder</span>
                </nav>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 flex items-center gap-2.5">
                    Aturan Otomasi Transaksi (Rules Builder)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400">
                        v2.4 Engine Aktif
                    </span>
                </h1>
                <p class="text-sm text-muted-foreground max-w-2xl">
                    Kategorikan dan beri tag transaksi mutasi rekening secara otomatis berdasarkan kriteria kata kunci, akun, atau nominal.
                </p>
            </div>

            <div class="flex items-center gap-2.5 shrink-0">
                <button
                    @click="showTestSimulation = !showTestSimulation"
                    type="button"
                    class="h-10 px-4 rounded-lg bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-medium text-xs border border-slate-200 dark:border-slate-700 shadow-xs flex items-center gap-2 transition-all active:scale-[0.98]"
                >
                    <PlayCircle class="h-4 w-4 text-emerald-600" />
                    <span>{{ showTestSimulation ? 'Tutup Uji Coba' : 'Jalankan Uji Coba (Test Run)' }}</span>
                </button>
                <Button
                    @click="startCreateNewRule"
                    class="h-10 px-4 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs shadow-sm flex items-center gap-1.5 transition-all active:scale-[0.98]"
                >
                    <Plus class="h-4 w-4" />
                    <span>Tambah Aturan Baru</span>
                </Button>
            </div>
        </div>

        <!-- Notification Banner -->
        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex items-start sm:items-center justify-between gap-3">
            <div class="flex items-start sm:items-center gap-3">
                <div class="p-2 rounded-lg bg-emerald-600 text-white shrink-0 flex items-center justify-center">
                    <Layers class="h-4 w-4" />
                </div>
                <div class="text-xs text-slate-700 dark:text-slate-300">
                    <span class="font-semibold text-slate-900 dark:text-slate-100">Prioritas Bertingkat:</span>
                    <span class="text-muted-foreground ml-1">
                        Aturan aktif dieksekusi secara berurutan dari prioritas tertinggi (#1) ke terendah saat import mutasi CSV atau sinkronisasi mutasi.
                    </span>
                </div>
            </div>
            <div class="hidden md:flex items-center gap-2 shrink-0">
                <span class="text-xs text-muted-foreground">Status Engine:</span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 font-medium">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Siap Memproses
                </span>
            </div>
        </div>

        <!-- Live Test Simulation Drawer/Panel (Collapsible) -->
        <div v-if="showTestSimulation" class="rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 rounded-lg bg-blue-500 text-white">
                        <FlaskConical class="h-4 w-4" />
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Hasil Simulasi Test Run (20 Transaksi Terakhir)</h3>
                        <p class="text-xs text-muted-foreground">Pratinjau mutasi yang cocok dan terpetakan jika seluruh aturan aktif dijalankan.</p>
                    </div>
                </div>
                <button @click="showTestSimulation = false" class="p-1 rounded-lg text-muted-foreground hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800">
                    <X class="h-4 w-4" />
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/60 text-muted-foreground uppercase tracking-wider font-semibold">
                            <th class="py-2.5 px-3 rounded-l-lg">Tanggal</th>
                            <th class="py-2.5 px-3">Deskripsi / Payee</th>
                            <th class="py-2.5 px-3 text-right">Nominal</th>
                            <th class="py-2.5 px-3">Aturan yang Cocok</th>
                            <th class="py-2.5 px-3 rounded-r-lg">Hasil Kategori</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-800 dark:text-slate-200">
                        <tr v-for="tx in simulatedTransactions" :key="tx.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-2.5 px-3 text-muted-foreground">
                                {{ new Date(tx.transacted_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) }}
                            </td>
                            <td class="py-2.5 px-3 font-medium">
                                {{ tx.payee || tx.note || 'Transaksi' }}
                            </td>
                            <td class="py-2.5 px-3 text-right font-medium tabular-nums" :class="tx.type === 'expense' ? 'text-rose-600' : 'text-emerald-600'">
                                {{ tx.type === 'expense' ? '-' : '+' }}{{ format(tx.amount) }}
                            </td>
                            <td class="py-2.5 px-3">
                                <span v-if="tx.matchedRule" class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 font-semibold text-[11px]">
                                    #{{ tx.matchedRule.priority }} {{ tx.matchedRule.name }}
                                </span>
                                <span v-else class="text-muted-foreground text-[11px] italic">Tidak ada aturan cocok</span>
                            </td>
                            <td class="py-2.5 px-3">
                                <span class="px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-[11px] font-medium">
                                    {{ tx.matchedCategory }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Master-Detail Split Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <!-- Left Column: Master List -->
            <div class="lg:col-span-5 space-y-4">
                <div class="flex items-center justify-between px-1">
                    <div class="flex items-center gap-2">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Daftar Aturan Aktif</h2>
                        <span class="px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold">
                            {{ rules.length }} Aturan
                        </span>
                    </div>
                    <span class="text-xs text-muted-foreground flex items-center gap-1">
                        Urutan Prioritas
                    </span>
                </div>

                <!-- Rules List Cards -->
                <div class="space-y-3">
                    <div
                        v-for="rule in rules"
                        :key="rule.id"
                        @click="selectRule(rule)"
                        :class="[
                            'p-4 rounded-xl shadow-xs transition-all cursor-pointer border',
                            selectedRuleId === rule.id && !isCreatingNew
                                ? 'bg-emerald-50/20 dark:bg-emerald-950/20 border-emerald-500 ring-1 ring-emerald-500'
                                : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700',
                            !rule.is_active ? 'opacity-60 bg-slate-50 dark:bg-slate-900/40' : ''
                        ]"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-2 shrink-0 pt-0.5">
                                <GripVertical class="h-4 w-4 text-muted-foreground cursor-grab" />
                                <span :class="['px-2 py-0.5 rounded text-xs font-semibold', rule.is_active ? 'bg-emerald-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300']">
                                    #{{ rule.priority }}
                                </span>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <h3 :class="['text-sm font-semibold truncate', !rule.is_active ? 'line-through text-muted-foreground' : 'text-slate-900 dark:text-slate-100']">
                                        {{ rule.name }}
                                    </h3>

                                    <!-- Active Toggle Switch -->
                                    <label class="relative inline-flex items-center cursor-pointer shrink-0" @click.stop>
                                        <input
                                            type="checkbox"
                                            :checked="rule.is_active"
                                            @change="toggleRuleActive(rule)"
                                            class="sr-only peer"
                                        />
                                        <div class="w-8 h-4 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-emerald-600"></div>
                                    </label>
                                </div>

                                <div class="mt-2 space-y-1 text-xs">
                                    <!-- JIKA -->
                                    <div class="text-muted-foreground flex items-center gap-1.5 flex-wrap">
                                        <span class="font-medium text-slate-700 dark:text-slate-300">JIKA:</span>
                                        <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-[11px]">
                                            {{ formatConditionSummary(rule) }}
                                        </span>
                                    </div>
                                    <!-- MAKA -->
                                    <div class="text-muted-foreground flex items-center gap-1.5 flex-wrap">
                                        <span class="font-medium text-emerald-600 dark:text-emerald-400">MAKA:</span>
                                        <span v-if="getRuleCategoryAction(rule)" class="px-2 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 font-medium text-[11px]">
                                            {{ getRuleCategoryAction(rule) }}
                                        </span>
                                        <span v-for="tag in (rule.actions?.filter(a => a.type === 'add_tag').map(a => a.value) || [])" :key="tag" class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-muted-foreground text-[10px]">
                                            #{{ tag }}
                                        </span>
                                    </div>
                                </div>

                                <div class="mt-3 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-muted-foreground">
                                    <span v-if="rule.is_active" class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-medium">
                                        <CheckCircle2 class="h-3.5 w-3.5" />
                                        {{ rule.match_count ?? 0 }} cocok bulan ini
                                    </span>
                                    <span v-else class="italic text-amber-600 dark:text-amber-400 flex items-center gap-1">
                                        Nonaktif
                                    </span>

                                    <span v-if="selectedRuleId === rule.id && !isCreatingNew" class="text-emerald-600 dark:text-emerald-400 font-medium text-xs">
                                        Sedang Diedit →
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Simulation Quick Stats Box -->
                <div class="p-4 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-semibold text-slate-900 dark:text-slate-100 flex items-center gap-1.5">
                            <Sparkles class="text-emerald-600 h-4 w-4" /> Efisiensi Automasi
                        </span>
                        <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                            {{ stats.efficiency_percent }}% Terotomasi
                        </span>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Sebanyak {{ stats.automated_transactions }} dari {{ stats.total_transactions_month }} mutasi bulan ini terpetakan otomatis tanpa intervensi manual.
                    </p>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                        <div class="bg-emerald-600 h-full rounded-full transition-all duration-500" :style="`width: ${stats.efficiency_percent}%;`"></div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Detail Rule Editor -->
            <div class="lg:col-span-7 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 space-y-5">
                <!-- Header Editor -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="p-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 flex items-center justify-center">
                                <Pencil class="h-4 w-4" />
                            </span>
                            <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100">
                                {{ isCreatingNew ? 'Konfigurasi Aturan Baru' : `Konfigurasi Aturan: ${editorForm.name || 'Aturan'}` }}
                            </h2>
                        </div>
                        <p class="text-xs text-muted-foreground mt-0.5">
                            Aturan ini dieksekusi dengan urutan prioritas ke-{{ editorForm.priority }} saat pemindaian mutasi.
                        </p>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <span class="text-xs text-muted-foreground">Status:</span>
                        <span :class="['px-2.5 py-0.5 rounded-full text-xs font-semibold', editorForm.is_active ? 'bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-600']">
                            {{ editorForm.is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                </div>

                <!-- Rule Name & Priority Input -->
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div class="sm:col-span-3 space-y-1">
                        <label class="text-xs font-medium text-slate-700 dark:text-slate-300">Nama Aturan</label>
                        <input
                            v-model="editorForm.name"
                            placeholder="Contoh: Auto-Category Transportasi Grab/Gojek"
                            class="w-full h-10 px-3 bg-slate-50 dark:bg-slate-800/60 rounded-lg border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>
                    <div class="sm:col-span-1 space-y-1">
                        <label class="text-xs font-medium text-slate-700 dark:text-slate-300">Prioritas (#)</label>
                        <input
                            v-model.number="editorForm.priority"
                            type="number"
                            min="1"
                            class="w-full h-10 px-3 bg-slate-50 dark:bg-slate-800/60 rounded-lg border border-slate-200 dark:border-slate-700 text-xs text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-emerald-500 tabular-nums"
                        />
                    </div>
                </div>

                <!-- SECTION: IF (Kondisi Pemicu) -->
                <div class="space-y-3 p-4 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded bg-slate-700 text-white text-[11px] font-bold tracking-wider">IF / JIKA</span>
                            <span class="text-xs font-medium text-slate-900 dark:text-slate-100">Kriteria berikut terpenuhi:</span>
                        </div>
                        <select
                            v-model="editorForm.match"
                            class="text-xs bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-md px-2 py-1 text-slate-700 dark:text-slate-300"
                        >
                            <option value="all">Semua Kriteria (AND)</option>
                            <option value="any">Salah Satu Kriteria (OR)</option>
                        </select>
                    </div>

                    <!-- Condition Rows -->
                    <div class="space-y-2">
                        <div
                            v-for="(cond, idx) in editorForm.conditions"
                            :key="idx"
                            class="p-3 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xs space-y-2"
                        >
                            <div class="flex items-center justify-between text-xs text-muted-foreground">
                                <span class="font-medium text-slate-800 dark:text-slate-200">Kondisi {{ idx + 1 }}</span>
                                <button
                                    v-if="editorForm.conditions.length > 1"
                                    @click="removeCondition(idx)"
                                    type="button"
                                    class="text-rose-500 hover:text-rose-700 transition-colors"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </button>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                                <div class="sm:col-span-4">
                                    <select
                                        v-model="cond.field"
                                        class="w-full h-9 px-2.5 bg-slate-50 dark:bg-slate-800 rounded-lg text-xs border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                                    >
                                        <option value="payee">Nama Merchant / Deskripsi</option>
                                        <option value="amount">Nominal Transaksi (Amount)</option>
                                        <option value="account_id">Rekening Sumber (Account)</option>
                                        <option value="type">Tipe (Debit/Kredit)</option>
                                    </select>
                                </div>

                                <div class="sm:col-span-4">
                                    <select
                                        v-if="cond.field === 'amount'"
                                        v-model="cond.op"
                                        class="w-full h-9 px-2.5 bg-slate-50 dark:bg-slate-800 rounded-lg text-xs border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                                    >
                                        <option value="lt">Kurang dari (&lt;)</option>
                                        <option value="gt">Lebih dari (&gt;)</option>
                                        <option value="equals">Sama dengan (=)</option>
                                    </select>
                                    <select
                                        v-else
                                        v-model="cond.op"
                                        class="w-full h-9 px-2.5 bg-slate-50 dark:bg-slate-800 rounded-lg text-xs border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                                    >
                                        <option value="contains">Mengandung kata (Contains)</option>
                                        <option value="equals">Sama persis (Exact)</option>
                                        <option value="starts_with">Diawali dengan (Starts with)</option>
                                        <option value="not_contains">Tidak mengandung (Not contains)</option>
                                    </select>
                                </div>

                                <div class="sm:col-span-4">
                                    <select
                                        v-if="cond.field === 'account_id'"
                                        v-model="cond.value"
                                        class="w-full h-9 px-2.5 bg-slate-50 dark:bg-slate-800 rounded-lg text-xs border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                                    >
                                        <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
                                    </select>
                                    <select
                                        v-else-if="cond.field === 'type'"
                                        v-model="cond.value"
                                        class="w-full h-9 px-2.5 bg-slate-50 dark:bg-slate-800 rounded-lg text-xs border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                                    >
                                        <option value="expense">Pengeluaran (Expense)</option>
                                        <option value="income">Pemasukan (Income)</option>
                                        <option value="transfer">Transfer</option>
                                    </select>
                                    <input
                                        v-else
                                        v-model="cond.value"
                                        :type="cond.field === 'amount' ? 'number' : 'text'"
                                        placeholder="Ketik kata kunci..."
                                        class="w-full h-9 px-2.5 bg-slate-50 dark:bg-slate-800 rounded-lg text-xs border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add Condition Button -->
                    <button
                        @click="addCondition"
                        type="button"
                        class="w-full h-9 rounded-lg bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 text-emerald-600 dark:text-emerald-400 font-medium text-xs border border-dashed border-slate-200 dark:border-slate-700 shadow-2xs flex items-center justify-center gap-1.5 transition-colors"
                    >
                        <Plus class="h-3.5 w-3.5" />
                        <span>Tambah Kondisi Tambahan</span>
                    </button>
                </div>

                <!-- SECTION: THEN (Tindakan yang Dijalankan) -->
                <div class="space-y-3 p-4 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded bg-emerald-600 text-white text-[11px] font-bold tracking-wider">THEN / MAKA</span>
                        <span class="text-xs font-medium text-slate-900 dark:text-slate-100">Jalankan instruksi penyesuaian:</span>
                    </div>

                    <div class="space-y-2.5">
                        <!-- Action 1: Set Kategori -->
                        <div class="p-3 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-2 sm:w-1/3">
                                <FolderPlus class="h-4 w-4 text-emerald-600" />
                                <span class="text-xs font-medium text-slate-800 dark:text-slate-200">Set Kategori</span>
                            </div>
                            <div class="flex-1">
                                <select
                                    v-model="editorForm.categoryId"
                                    class="w-full h-9 px-3 bg-slate-50 dark:bg-slate-800 rounded-lg text-xs border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                                >
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                        {{ cat.name }} ({{ cat.type === 'expense' ? 'Pengeluaran' : 'Pemasukan' }})
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Action 2: Tambahkan Tag -->
                        <div class="p-3 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-2 sm:w-1/3">
                                <Tag class="h-4 w-4 text-emerald-600" />
                                <span class="text-xs font-medium text-slate-800 dark:text-slate-200">Tambahkan Tag</span>
                            </div>
                            <div class="flex-1 flex flex-wrap items-center gap-1.5 p-1 bg-slate-50 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 min-h-[36px]">
                                <span
                                    v-for="t in editorForm.tags"
                                    :key="t"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 text-[11px] font-medium"
                                >
                                    #{{ t }}
                                    <button @click="removeTag(t)" type="button" class="hover:text-rose-600">
                                        <X class="h-3 w-3" />
                                    </button>
                                </span>
                                <input
                                    v-model="editorForm.newTagInput"
                                    @keydown.enter.prevent="addTag"
                                    placeholder="+ Ketik tag lalu tekan Enter..."
                                    class="bg-transparent text-xs text-slate-800 dark:text-slate-200 placeholder-muted-foreground px-2 py-1 outline-none flex-1 min-w-[140px]"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Advanced Options: Stop Processing -->
                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 flex items-start justify-between gap-4">
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-slate-900 dark:text-slate-100">Hentikan Pemrosesan Lanjutan</span>
                            <span class="px-2 py-0.2 rounded bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-[10px] font-semibold">Rekomendasi</span>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Jika transaksi cocok dengan aturan ini, jangan eksekusi aturan-aturan berikutnya di bawah daftar prioritas.
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0 mt-0.5">
                        <input
                            type="checkbox"
                            v-model="editorForm.stop_processing"
                            class="sr-only peer"
                        />
                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                </div>

                <!-- Bottom Action Buttons -->
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <button
                        v-if="!isCreatingNew && selectedRuleId"
                        @click="deleteCurrentRule"
                        type="button"
                        class="w-full sm:w-auto h-9 px-3.5 rounded-lg bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 font-medium text-xs flex items-center justify-center gap-1.5 transition-colors"
                    >
                        <Trash2 class="h-3.5 w-3.5" />
                        <span>Hapus Aturan</span>
                    </button>
                    <div v-else class="hidden sm:block"></div>

                    <div class="w-full sm:w-auto flex flex-col sm:flex-row items-center gap-2">
                        <button
                            @click="applyRulesToExisting"
                            :disabled="isApplying"
                            type="button"
                            class="w-full sm:w-auto h-9 px-3.5 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-medium transition-all"
                        >
                            {{ isApplying ? 'Menerapkan...' : 'Terapkan ke Mutasi yang Ada' }}
                        </button>
                        <Button
                            @click="saveRule"
                            :disabled="isSaving || !editorForm.name"
                            class="w-full sm:w-auto h-9 px-4 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs shadow-sm flex items-center justify-center gap-1.5"
                        >
                            <Check class="h-4 w-4" />
                            <span>{{ isSaving ? 'Menyimpan...' : (isCreatingNew ? 'Buat Aturan Baru' : 'Simpan Perubahan Aturan') }}</span>
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

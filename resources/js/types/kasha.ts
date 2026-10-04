export interface Account {
    id: string;
    name: string;
    type: 'cash' | 'bank' | 'ewallet' | 'credit' | 'savings';
    opening_balance: number;
    currency: string;
    color: string | null;
    icon: string | null;
    is_archived: boolean;
    include_in_total: boolean;
    balance?: number;
}

export interface Category {
    id: string;
    name: string;
    type: 'income' | 'expense';
    icon: string | null;
    color: string | null;
    parent_id: string | null;
    is_archived: boolean;
    children?: Category[];
}

export interface Tag {
    id: string;
    name: string;
}

export interface Transaction {
    id: string;
    account_id: string;
    account?: Pick<Account, 'id' | 'name'>;
    category_id: string | null;
    category?: Pick<Category, 'id' | 'name' | 'icon' | 'color'> | null;
    type: 'income' | 'expense' | 'transfer';
    amount: number;
    occurred_on: string;
    payee: string | null;
    note: string | null;
    transfer_group_id: string | null;
    is_reconciled: boolean;
    tags?: Tag[];
}

export interface Budget {
    category_id: string;
    category_name: string;
    period: string;
    amount: number;
    spent: number;
    percent: number;
    rollover: boolean;
}

export interface RecurringTransaction {
    id: string;
    type: 'income' | 'expense';
    amount: number;
    payee: string | null;
    frequency: 'daily' | 'weekly' | 'monthly' | 'yearly';
    interval: number;
    next_run_on: string;
    mode: 'auto' | 'reminder';
    is_active: boolean;
    account?: Pick<Account, 'id' | 'name'>;
    category?: Pick<Category, 'id' | 'name'> | null;
    next_dates?: string[];
}

export interface Rule {
    id: string;
    name: string;
    priority: number;
    stop_processing: boolean;
    is_active: boolean;
    conditions: Record<string, unknown>;
    actions: Record<string, unknown>[];
}

export interface Goal {
    id: string;
    name: string;
    target_amount: number;
    target_date: string | null;
    status: 'active' | 'completed' | 'paused';
    contributed_amount: number;
    progress_percent: number;
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

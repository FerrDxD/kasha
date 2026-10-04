<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Demo User', 'password' => bcrypt('password')]
        );

        auth()->login($user);

        // 1. Accounts
        $bca = \App\Models\Account::firstOrCreate(['name' => 'BCA', 'user_id' => $user->id], [
            'type' => 'bank',
            'opening_balance' => 15000000,
            'color' => '#2563eb',
            'include_in_total' => true,
        ]);

        $gopay = \App\Models\Account::firstOrCreate(['name' => 'GoPay', 'user_id' => $user->id], [
            'type' => 'ewallet',
            'opening_balance' => 750000,
            'color' => '#06b6d4',
            'include_in_total' => true,
        ]);

        $cash = \App\Models\Account::firstOrCreate(['name' => 'Dompet Cash', 'user_id' => $user->id], [
            'type' => 'cash',
            'opening_balance' => 500000,
            'color' => '#10b981',
            'include_in_total' => true,
        ]);

        // 2. Categories
        $gaji = \App\Models\Category::firstOrCreate(['name' => 'Gaji', 'user_id' => $user->id], [
            'type' => 'income', 'color' => '#10b981',
        ]);
        $freelance = \App\Models\Category::firstOrCreate(['name' => 'Freelance', 'user_id' => $user->id], [
            'type' => 'income', 'color' => '#3b82f6',
        ]);

        $makanan = \App\Models\Category::firstOrCreate(['name' => 'Makanan & Minuman', 'user_id' => $user->id], [
            'type' => 'expense', 'color' => '#f97316',
        ]);
        $transport = \App\Models\Category::firstOrCreate(['name' => 'Transportasi', 'user_id' => $user->id], [
            'type' => 'expense', 'color' => '#6366f1',
        ]);
        $tagihan = \App\Models\Category::firstOrCreate(['name' => 'Tagihan & Langganan', 'user_id' => $user->id], [
            'type' => 'expense', 'color' => '#ef4444',
        ]);
        $hiburan = \App\Models\Category::firstOrCreate(['name' => 'Hiburan', 'user_id' => $user->id], [
            'type' => 'expense', 'color' => '#ec4899',
        ]);

        // Subcategories
        \App\Models\Category::firstOrCreate(['name' => 'Restoran', 'user_id' => $user->id], [
            'type' => 'expense', 'parent_id' => $makanan->id, 'color' => '#f97316',
        ]);
        \App\Models\Category::firstOrCreate(['name' => 'Ojek Online', 'user_id' => $user->id], [
            'type' => 'expense', 'parent_id' => $transport->id, 'color' => '#6366f1',
        ]);

        // 3. Budgets for current month
        $currentPeriod = now()->format('Y-m');
        \App\Models\Budget::updateOrCreate(
            ['user_id' => $user->id, 'category_id' => $makanan->id, 'period' => $currentPeriod],
            ['amount' => 3000000, 'rollover' => false]
        );
        \App\Models\Budget::updateOrCreate(
            ['user_id' => $user->id, 'category_id' => $transport->id, 'period' => $currentPeriod],
            ['amount' => 1200000, 'rollover' => false]
        );
        \App\Models\Budget::updateOrCreate(
            ['user_id' => $user->id, 'category_id' => $hiburan->id, 'period' => $currentPeriod],
            ['amount' => 800000, 'rollover' => false]
        );

        // 4. Sample Transactions
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $threeDaysAgo = now()->subDays(3)->toDateString();

        \App\Models\Transaction::create([
            'user_id' => $user->id,
            'account_id' => $bca->id,
            'category_id' => $gaji->id,
            'type' => 'income',
            'amount' => 12000000,
            'occurred_on' => now()->startOfMonth()->toDateString(),
            'payee' => 'PT Teknologi Maju',
            'note' => 'Gaji bulanan',
        ]);

        \App\Models\Transaction::create([
            'user_id' => $user->id,
            'account_id' => $gopay->id,
            'category_id' => $transport->id,
            'type' => 'expense',
            'amount' => 35000,
            'occurred_on' => $today,
            'payee' => 'Gojek Ride',
            'note' => 'Ke kantor',
        ]);

        \App\Models\Transaction::create([
            'user_id' => $user->id,
            'account_id' => $bca->id,
            'category_id' => $makanan->id,
            'type' => 'expense',
            'amount' => 145000,
            'occurred_on' => $yesterday,
            'payee' => 'Kopi Kenangan & Lunch',
            'note' => 'Makan siang bareng tim',
        ]);

        \App\Models\Transaction::create([
            'user_id' => $user->id,
            'account_id' => $cash->id,
            'category_id' => $makanan->id,
            'type' => 'expense',
            'amount' => 25000,
            'occurred_on' => $threeDaysAgo,
            'payee' => 'Warung Nasi',
            'note' => 'Makan malam',
        ]);

        // 5. Goals
        $goal1 = \App\Models\Goal::firstOrCreate(['name' => 'Dana Darurat 6 Bulan', 'user_id' => $user->id], [
            'target_amount' => 30000000,
            'target_date' => now()->addMonths(6)->toDateString(),
            'status' => 'active',
        ]);
        $goal1->contributions()->firstOrCreate(['amount' => 12000000, 'contributed_on' => $yesterday]);

        $goal2 = \App\Models\Goal::firstOrCreate(['name' => 'Liburan Akhir Tahun', 'user_id' => $user->id], [
            'target_amount' => 10000000,
            'target_date' => now()->addMonths(3)->toDateString(),
            'status' => 'active',
        ]);
        $goal2->contributions()->firstOrCreate(['amount' => 4500000, 'contributed_on' => $threeDaysAgo]);

        // 6. Recurring Transactions
        \App\Models\RecurringTransaction::firstOrCreate(['payee' => 'Netflix Premium', 'user_id' => $user->id], [
            'account_id' => $bca->id,
            'category_id' => $tagihan->id,
            'type' => 'expense',
            'amount' => 186000,
            'frequency' => 'monthly',
            'interval' => 1,
            'starts_on' => now()->startOfMonth()->toDateString(),
            'next_run_on' => now()->addDays(5)->toDateString(),
            'mode' => 'auto',
            'is_active' => true,
        ]);

        \App\Models\RecurringTransaction::firstOrCreate(['payee' => 'Internet Indihome', 'user_id' => $user->id], [
            'account_id' => $bca->id,
            'category_id' => $tagihan->id,
            'type' => 'expense',
            'amount' => 385000,
            'frequency' => 'monthly',
            'interval' => 1,
            'starts_on' => now()->startOfMonth()->toDateString(),
            'next_run_on' => now()->addDays(3)->toDateString(),
            'mode' => 'reminder',
            'is_active' => true,
        ]);

        // 7. Rules
        \App\Models\Rule::firstOrCreate(['name' => 'Auto Kategori Gojek/Grab', 'user_id' => $user->id], [
            'priority' => 1,
            'stop_processing' => true,
            'is_active' => true,
            'conditions' => [
                'match' => 'any',
                'conditions' => [
                    ['field' => 'payee', 'op' => 'contains', 'value' => 'Gojek'],
                    ['field' => 'payee', 'op' => 'contains', 'value' => 'Grab'],
                ],
            ],
            'actions' => [
                ['type' => 'set_category', 'value' => $transport->id],
            ],
        ]);
    }
}

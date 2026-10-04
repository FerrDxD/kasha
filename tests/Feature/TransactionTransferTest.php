<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_creates_two_linked_transaction_legs(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $acc1 = Account::create(['name' => 'BCA', 'type' => 'bank', 'opening_balance' => 1000000]);
        $acc2 = Account::create(['name' => 'GoPay', 'type' => 'ewallet', 'opening_balance' => 0]);

        $response = $this->post(route('transactions.transfer'), [
            'from_account_id' => $acc1->id,
            'to_account_id' => $acc2->id,
            'amount' => 250000,
            'occurred_on' => now()->toDateString(),
            'note' => 'Topup GoPay',
        ]);

        $response->assertRedirect();

        $legs = Transaction::whereNotNull('transfer_group_id')->get();
        $this->assertCount(2, $legs);

        $out = $legs->firstWhere('type', 'expense');
        $in = $legs->firstWhere('type', 'income');

        $this->assertNotNull($out);
        $this->assertNotNull($in);
        $this->assertEquals($out->transfer_group_id, $in->transfer_group_id);
        $this->assertEquals(250000, $out->amount);
        $this->assertEquals(250000, $in->amount);
        $this->assertEquals($acc1->id, $out->account_id);
        $this->assertEquals($acc2->id, $in->account_id);

        // Account balances
        $this->assertEquals(750000, $acc1->fresh()->balance);
        $this->assertEquals(250000, $acc2->fresh()->balance);

        // Deleting one leg deletes both (soft delete as defined in PRD)
        $this->delete(route('transactions.destroy', $out->id));
        $this->assertSoftDeleted('transactions', ['id' => $out->id]);
        $this->assertSoftDeleted('transactions', ['id' => $in->id]);
    }
}

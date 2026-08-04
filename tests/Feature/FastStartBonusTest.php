<?php

namespace Tests\Feature;

use App\Constants\Status;
use App\Models\Admin;
use App\Models\Extension;
use App\Models\FastStartBonusLog;
use App\Models\GeneralSetting;
use App\Models\Plan;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserExtra;
use App\Services\FastStartBonusService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class FastStartBonusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Auth::logout();
        Session::flush();
        $this->seedTestingRuntimeDefaults();
        $this->ensurePlanCodes();
    }

    public function test_premium_plus_premium_credits_three_thousand(): void
    {
        $sponsor = $this->createActivatedSponsor();

        $this->activateDirect($sponsor, 'premium_pack');
        $this->activateDirect($sponsor, 'premium_pack');

        $this->assertFastStartPaid($sponsor, 3000.0, 'premium_premium');
    }

    public function test_premium_plus_royal_credits_three_thousand(): void
    {
        $sponsor = $this->createActivatedSponsor();

        $this->activateDirect($sponsor, 'premium_pack');
        $this->activateDirect($sponsor, 'royal_pack');

        $this->assertFastStartPaid($sponsor, 3000.0, 'premium_royal');
    }

    public function test_royal_plus_royal_credits_six_thousand(): void
    {
        $sponsor = $this->createActivatedSponsor();

        $this->activateDirect($sponsor, 'royal_pack');
        $this->activateDirect($sponsor, 'royal_pack');

        $this->assertFastStartPaid($sponsor, 6000.0, 'royal_royal');
    }

    public function test_window_expired_does_not_credit_bonus(): void
    {
        $sponsor = $this->createActivatedSponsor([
            'plan_activated_at' => now()->subDays(16),
        ]);

        $this->activateDirect($sponsor, 'premium_pack');
        $this->activateDirect($sponsor, 'premium_pack');

        $sponsor->refresh();

        $this->assertFalse((bool) $sponsor->fast_start_bonus_claimed);
        $this->assertEquals(0.0, (float) $sponsor->fast_start_bonus_amount);
        $this->assertEquals(0, FastStartBonusLog::where('user_id', $sponsor->id)->count());
        $this->assertEquals(0, Transaction::where('user_id', $sponsor->id)->where('remark', 'fast_start_bonus')->count());
    }

    public function test_bonus_is_credited_only_once_after_first_eligible_combination(): void
    {
        $sponsor = $this->createActivatedSponsor();

        $this->activateDirect($sponsor, 'premium_pack');
        $this->activateDirect($sponsor, 'premium_pack');
        $this->activateDirect($sponsor, 'royal_pack');
        $this->activateDirect($sponsor, 'royal_pack');

        $this->assertFastStartPaid($sponsor, 3000.0, 'premium_premium');
        $this->assertEquals(1, FastStartBonusLog::where('user_id', $sponsor->id)->count());
        $this->assertEquals(1, Transaction::where('user_id', $sponsor->id)->where('remark', 'fast_start_bonus')->count());
    }

    public function test_duplicate_request_protection_does_not_credit_twice(): void
    {
        $sponsor = $this->createActivatedSponsor();

        $this->activateDirect($sponsor, 'royal_pack');
        $this->activateDirect($sponsor, 'royal_pack');

        app(FastStartBonusService::class)->evaluateSponsor($sponsor->id);
        app(FastStartBonusService::class)->evaluateSponsor($sponsor->id);

        $this->assertFastStartPaid($sponsor, 6000.0, 'royal_royal');
        $this->assertEquals(1, FastStartBonusLog::where('user_id', $sponsor->id)->count());
        $this->assertEquals(1, Transaction::where('user_id', $sponsor->id)->where('remark', 'fast_start_bonus')->count());
    }

    public function test_wallet_transaction_and_log_are_created(): void
    {
        $sponsor = $this->createActivatedSponsor();
        $startingBalance = (float) $sponsor->balance;

        $this->activateDirect($sponsor, 'royal_pack');
        $this->activateDirect($sponsor, 'royal_pack');

        $sponsor->refresh();
        $transaction = Transaction::where('user_id', $sponsor->id)->where('remark', 'fast_start_bonus')->firstOrFail();
        $log = FastStartBonusLog::where('user_id', $sponsor->id)->firstOrFail();

        $this->assertEquals(6000.0, (float) $transaction->amount);
        $this->assertSame('+', $transaction->trx_type);
        $this->assertSame('Fast Start Bonus credited after qualifying within 15-day window.', $transaction->details);
        $this->assertGreaterThanOrEqual($startingBalance + 6000.0, (float) $sponsor->balance);
        $this->assertEquals((float) $sponsor->balance, (float) $transaction->post_balance);
        $this->assertEquals(6000.0, (float) $sponsor->fast_start_bonus_amount);
        $this->assertSame($transaction->id, $log->transaction_id);
        $this->assertSame('paid', $log->status);
    }

    public function test_dashboard_card_shows_fast_start_bonus_value(): void
    {
        $sponsor = $this->createActivatedSponsor();
        $this->activateDirect($sponsor, 'premium_pack');
        $this->activateDirect($sponsor, 'premium_pack');

        $this->actingAs($sponsor->fresh())
            ->get(route('user.home'))
            ->assertOk()
            ->assertSee('Fast Start Bonus')
            ->assertSee('3,000');
    }

    public function test_admin_report_shows_fast_start_bonus_log(): void
    {
        $sponsor = $this->createActivatedSponsor();
        $this->activateDirect($sponsor, 'premium_pack');
        $this->activateDirect($sponsor, 'royal_pack');

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.fast.start.bonus'))
            ->assertOk()
            ->assertSee($sponsor->username)
            ->assertSee('Premium + Royal')
            ->assertSee('3,000');
    }

    public function test_admin_profile_card_shows_fast_start_bonus_fields(): void
    {
        $sponsor = $this->createActivatedSponsor();
        $this->activateDirect($sponsor, 'premium_pack');
        $this->activateDirect($sponsor, 'premium_pack');

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.users.detail', $sponsor->id))
            ->assertOk()
            ->assertSee('Fast Start Bonus')
            ->assertSee('Claimed')
            ->assertSee('Yes')
            ->assertSee('Plan Activation Date');
    }

    public function test_history_page_shows_fast_start_bonus_details(): void
    {
        $sponsor = $this->createActivatedSponsor();
        $this->activateDirect($sponsor, 'royal_pack');
        $this->activateDirect($sponsor, 'royal_pack');

        $this->actingAs($sponsor->fresh())
            ->get(route('user.fast.start.bonus.index'))
            ->assertOk()
            ->assertSee('Fast Start Bonus History')
            ->assertSee('Royal + Royal')
            ->assertSee('6,000')
            ->assertSee('Paid');
    }

    private function assertFastStartPaid(User $sponsor, float $amount, string $qualifyingType): void
    {
        $sponsor->refresh();

        $this->assertTrue((bool) $sponsor->fast_start_bonus_claimed);
        $this->assertEquals($amount, (float) $sponsor->fast_start_bonus_amount);
        $this->assertNotNull($sponsor->fast_start_bonus_date);
        $this->assertDatabaseHas('fast_start_bonus_logs', [
            'user_id' => $sponsor->id,
            'qualifying_type' => $qualifyingType,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $sponsor->id,
            'remark' => 'fast_start_bonus',
            'trx_type' => '+',
        ]);
    }

    private function createActivatedSponsor(array $overrides = []): User
    {
        return $this->createReadyUser(array_merge([
            'plan_id' => Plan::where('plan_code', 'premium_pack')->firstOrFail()->id,
            'plan_activated_at' => now(),
            'balance' => 0,
        ], $overrides));
    }

    private function activateDirect(User $sponsor, string $planCode): User
    {
        $plan = Plan::where('plan_code', $planCode)->firstOrFail();
        $user = $this->createReadyUser([
            'ref_by' => $sponsor->id,
            'pos_id' => $sponsor->id,
            'position' => Status::LEFT,
            'balance' => (float) $plan->price,
        ]);
        updateFreeCount($user->id);

        $this->actingAs($user)
            ->from(route('user.plan.index'))
            ->post(route('user.plan.purchase'), ['plan_id' => $plan->id])
            ->assertRedirect(route('user.plan.index'));

        return $user->fresh();
    }

    private function admin(): Admin
    {
        $admin = Admin::first();
        if ($admin) {
            return $admin;
        }

        $admin = new Admin();
        $admin->name = 'Admin';
        $admin->username = 'admin';
        $admin->email = 'admin@example.test';
        $admin->password = Hash::make('secret123');
        $admin->save();

        return $admin;
    }

    private function seedTestingRuntimeDefaults(): void
    {
        $general = GeneralSetting::firstOrFail();
        $general->registration = 1;
        $general->agree = 0;
        $general->secure_password = 0;
        $general->ev = 0;
        $general->sv = 0;
        $general->save();

        Extension::whereIn('act', ['google-recaptcha2', 'custom-captcha'])->update(['status' => Status::DISABLE]);
        Cache::forget('GeneralSetting');
    }

    private function ensurePlanCodes(): void
    {
        Plan::where('name', 'Vision Pack')->update(['plan_code' => 'vision_pack']);
        Plan::where('name', 'Gold Pack')->update(['plan_code' => 'gold_pack']);
        Plan::where('name', 'Premium Pack')->update(['plan_code' => 'premium_pack']);
        Plan::where('name', 'Royal Pack')->update(['plan_code' => 'royal_pack']);
    }

    private function createReadyUser(array $overrides = []): User
    {
        $user = new User();
        $user->ref_by = $overrides['ref_by'] ?? 0;
        $user->pos_id = $overrides['pos_id'] ?? 0;
        $user->position = $overrides['position'] ?? 0;
        $user->plan_id = $overrides['plan_id'] ?? 0;
        $user->plan_activated_at = $overrides['plan_activated_at'] ?? null;
        $user->fast_start_bonus_claimed = $overrides['fast_start_bonus_claimed'] ?? false;
        $user->fast_start_bonus_amount = $overrides['fast_start_bonus_amount'] ?? 0;
        $user->fast_start_bonus_date = $overrides['fast_start_bonus_date'] ?? null;
        $user->total_invest = $overrides['total_invest'] ?? 0;
        $user->firstname = $overrides['firstname'] ?? 'Fast';
        $user->lastname = $overrides['lastname'] ?? 'Start';
        $user->username = $overrides['username'] ?? 'fs' . strtolower(str()->random(12));
        $user->email = $overrides['email'] ?? 'fs' . strtolower(str()->random(12)) . '@example.test';
        $user->dial_code = $overrides['dial_code'] ?? '91';
        $user->country_code = $overrides['country_code'] ?? 'IN';
        $user->mobile = $overrides['mobile'] ?? '8' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
        $user->balance = $overrides['balance'] ?? 0;
        $user->total_binary_com = $overrides['total_binary_com'] ?? 0;
        $user->total_ref_com = $overrides['total_ref_com'] ?? 0;
        $user->total_level_income = $overrides['total_level_income'] ?? 0;
        $user->password = Hash::make($overrides['password'] ?? 'secret123');
        $user->country_name = $overrides['country_name'] ?? 'India';
        $user->status = $overrides['status'] ?? Status::USER_ACTIVE;
        $user->ev = $overrides['ev'] ?? Status::VERIFIED;
        $user->sv = $overrides['sv'] ?? Status::VERIFIED;
        $user->kv = $overrides['kv'] ?? Status::KYC_VERIFIED;
        $user->ts = $overrides['ts'] ?? Status::DISABLE;
        $user->tv = $overrides['tv'] ?? Status::ENABLE;
        $user->profile_complete = $overrides['profile_complete'] ?? Status::YES;
        $user->save();

        $extra = new UserExtra();
        $extra->user_id = $user->id;
        $extra->save();

        return $user;
    }
}

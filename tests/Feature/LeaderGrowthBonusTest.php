<?php

namespace Tests\Feature;

use App\Constants\Status;
use App\Models\Admin;
use App\Models\Extension;
use App\Models\GeneralSetting;
use App\Models\LeaderGrowthBonusLog;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserExtra;
use App\Services\LeaderGrowthBonusService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class LeaderGrowthBonusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Auth::logout();
        Session::flush();
        $this->seedTestingRuntimeDefaults();
    }

    public function test_reward_after_three_lakh_fresh_matching_business(): void
    {
        $user = $this->createReadyUser();

        $this->sendMatching($user, 100000);
        $this->sendMatching($user, 200000);

        $this->assertLeaderGrowthPaid($user, 30000.0, 1);
    }

    public function test_wallet_transaction_and_log_are_created(): void
    {
        $user = $this->createReadyUser();

        $this->sendMatching($user, 300000);
        $user->refresh();

        $transaction = Transaction::where('user_id', $user->id)->where('remark', 'leader_growth_bonus')->firstOrFail();
        $log = LeaderGrowthBonusLog::where('user_id', $user->id)->where('status', 'paid')->firstOrFail();

        $this->assertEquals(30000.0, (float) $user->balance);
        $this->assertEquals(30000.0, (float) $transaction->amount);
        $this->assertSame('+', $transaction->trx_type);
        $this->assertSame('Leader Growth Bonus Fresh Matching Business Target Achieved 300000', $transaction->details);
        $this->assertEquals(300000.0, (float) $log->required_business);
        $this->assertEquals(300000.0, (float) $log->achieved_business);
        $this->assertSame($transaction->id, $log->wallet_transaction_id);
    }

    public function test_current_cycle_resets_and_new_cycle_starts_after_reward(): void
    {
        $user = $this->createReadyUser();

        $this->sendMatching($user, 300000);
        $user->refresh();

        $this->assertEquals(0.0, (float) $user->leader_growth_current_business);
        $this->assertNotNull($user->leader_growth_cycle_start_at);
        $this->assertNotNull($user->leader_growth_last_bonus_at);
        $this->assertEquals(
            $user->leader_growth_last_bonus_at->format('Y-m-d H:i:s'),
            $user->leader_growth_cycle_start_at->format('Y-m-d H:i:s')
        );
    }

    public function test_user_can_earn_again_in_next_cycles(): void
    {
        $user = $this->createReadyUser();

        for ($i = 0; $i < 3; $i++) {
            $this->sendMatching($user, 300000);
        }

        $user->refresh();

        $this->assertEquals(90000.0, (float) $user->leader_growth_total_bonus);
        $this->assertEquals(3, (int) $user->leader_growth_bonus_count);
        $this->assertEquals(3, Transaction::where('user_id', $user->id)->where('remark', 'leader_growth_bonus')->count());
        $this->assertEquals(3, LeaderGrowthBonusLog::where('user_id', $user->id)->where('status', 'paid')->count());
    }

    public function test_duplicate_reward_prevention_for_same_matching_transaction(): void
    {
        $user = $this->createReadyUser();
        $matching = $this->matchingTransaction($user, 300000);

        app(LeaderGrowthBonusService::class)->handleMatchingPaid($user, $matching, 300000);
        app(LeaderGrowthBonusService::class)->handleMatchingPaid($user, $matching, 300000);

        $this->assertLeaderGrowthPaid($user, 30000.0, 1);
        $this->assertEquals(1, LeaderGrowthBonusLog::where('matching_transaction_id', $matching->id)->count());
    }

    public function test_old_matching_records_are_never_counted_again(): void
    {
        $user = $this->createReadyUser();

        $this->matchingTransaction($user, 500000, now()->subDays(10));
        $this->sendMatching($user, 100000);

        $user->refresh();

        $this->assertEquals(0.0, (float) $user->leader_growth_total_bonus);
        $this->assertEquals(100000.0, (float) $user->leader_growth_current_business);
        $this->assertEquals(0, Transaction::where('user_id', $user->id)->where('remark', 'leader_growth_bonus')->count());
    }

    public function test_existing_matching_records_before_module_installation_do_not_generate_bonus(): void
    {
        $user = $this->createReadyUser([
            'leader_growth_cycle_start_at' => null,
            'leader_growth_current_business' => 0,
        ]);

        $this->matchingTransaction($user, 300000, now()->subDays(20));

        $user->refresh();

        $this->assertEquals(0.0, (float) $user->leader_growth_total_bonus);
        $this->assertEquals(0.0, (float) $user->leader_growth_current_business);
        $this->assertEquals(0, LeaderGrowthBonusLog::where('user_id', $user->id)->count());
    }

    public function test_expired_cycle_resets_before_counting_new_matching(): void
    {
        $user = $this->createReadyUser([
            'leader_growth_cycle_start_at' => now()->subDays(31),
            'leader_growth_current_business' => 250000,
        ]);

        $this->sendMatching($user, 60000);
        $user->refresh();

        $this->assertEquals(0.0, (float) $user->leader_growth_total_bonus);
        $this->assertEquals(60000.0, (float) $user->leader_growth_current_business);
    }

    public function test_dashboard_values_are_correct(): void
    {
        $user = $this->createReadyUser();
        $this->sendMatching($user, 120000);

        $this->actingAs($user->fresh())
            ->get(route('user.home'))
            ->assertOk()
            ->assertSee('Leader Growth Bonus')
            ->assertSee('120,000')
            ->assertSee('300,000')
            ->assertDontSee('Times Achieved')
            ->assertDontSee('Cycle Start');
    }

    public function test_user_history_page_is_correct(): void
    {
        $user = $this->createReadyUser();
        $this->sendMatching($user, 300000);

        $this->actingAs($user->fresh())
            ->get(route('user.leader.growth.bonus.index'))
            ->assertOk()
            ->assertSee('Leader Growth Bonus History')
            ->assertSee('30,000')
            ->assertSee('300,000')
            ->assertSee('Paid');
    }

    public function test_admin_report_is_correct(): void
    {
        $user = $this->createReadyUser();
        $this->sendMatching($user, 300000);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.leader.growth.bonus'))
            ->assertOk()
            ->assertSee($user->username)
            ->assertSee('30,000')
            ->assertSee('300,000');
    }

    public function test_admin_profile_card_is_correct(): void
    {
        $user = $this->createReadyUser();
        $this->sendMatching($user, 300000);

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.users.detail', $user->id))
            ->assertOk()
            ->assertSee('Leader Growth Bonus')
            ->assertSee('Total Bonus')
            ->assertSee('Bonus Count')
            ->assertSee('30,000');
    }

    private function sendMatching(User $user, float $freshBusiness): Transaction
    {
        $matching = $this->matchingTransaction($user, $freshBusiness);
        app(LeaderGrowthBonusService::class)->handleMatchingPaid($user->fresh(), $matching, $freshBusiness);

        return $matching;
    }

    private function matchingTransaction(User $user, float $amount, $createdAt = null): Transaction
    {
        $transaction = new Transaction();
        $transaction->user_id = $user->id;
        $transaction->amount = $amount;
        $transaction->charge = 0;
        $transaction->trx_type = '+';
        $transaction->remark = 'binary_commission';
        $transaction->trx = getTrx();
        $transaction->post_balance = $user->balance;
        $transaction->details = 'Test paid matching business.';
        $transaction->created_at = $createdAt ?: now();
        $transaction->updated_at = $createdAt ?: now();
        $transaction->save();

        return $transaction;
    }

    private function assertLeaderGrowthPaid(User $user, float $amount, int $count): void
    {
        $user->refresh();

        $this->assertEquals($amount, (float) $user->leader_growth_total_bonus);
        $this->assertEquals($count, (int) $user->leader_growth_bonus_count);
        $this->assertEquals(0.0, (float) $user->leader_growth_current_business);
        $this->assertNotNull($user->leader_growth_last_bonus_at);
        $this->assertEquals($count, Transaction::where('user_id', $user->id)->where('remark', 'leader_growth_bonus')->count());
        $this->assertEquals($count, LeaderGrowthBonusLog::where('user_id', $user->id)->where('status', 'paid')->count());
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
        $admin->password = Hash::make('123456789');
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

    private function createReadyUser(array $overrides = []): User
    {
        $user = new User();
        $user->ref_by = $overrides['ref_by'] ?? 0;
        $user->pos_id = $overrides['pos_id'] ?? 0;
        $user->position = $overrides['position'] ?? 0;
        $user->plan_id = $overrides['plan_id'] ?? 0;
        $user->plan_activated_at = $overrides['plan_activated_at'] ?? null;
        $user->leader_growth_cycle_start_at = $overrides['leader_growth_cycle_start_at'] ?? null;
        $user->leader_growth_current_business = $overrides['leader_growth_current_business'] ?? 0;
        $user->leader_growth_total_bonus = $overrides['leader_growth_total_bonus'] ?? 0;
        $user->leader_growth_bonus_count = $overrides['leader_growth_bonus_count'] ?? 0;
        $user->leader_growth_last_bonus_at = $overrides['leader_growth_last_bonus_at'] ?? null;
        $user->firstname = $overrides['firstname'] ?? 'Leader';
        $user->lastname = $overrides['lastname'] ?? 'Growth';
        $user->username = $overrides['username'] ?? 'lg' . strtolower(Str::random(12));
        $user->email = $overrides['email'] ?? 'lg' . strtolower(Str::random(12)) . '@example.test';
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

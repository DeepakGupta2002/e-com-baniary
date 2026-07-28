<?php

namespace Tests\Feature;

use App\Constants\Status;
use App\Models\BvLog;
use App\Models\GeneralSetting;
use App\Models\Plan;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserExtra;
use App\Models\UserLogin;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrivaCompensationEngineTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTestingRuntimeDefaults();
    }

    public function test_user_registration_creates_user_and_user_extra_in_tree(): void
    {
        $sponsor = $this->createReadyUser([
            'username' => $this->uniqueUsername('qasponsor'),
        ]);

        $response = $this->post(route('user.register'), [
            'firstname'             => 'Reg',
            'lastname'              => 'Child',
            'referBy'               => $sponsor->username,
            'position'              => Status::LEFT,
            'email'                 => $this->uniqueEmail('reg-child'),
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect();

        $registered = User::where('email', 'like', 'reg-child%@example.test')->latest('id')->first();

        $this->assertNotNull($registered, 'Registered user was not created.');
        $this->assertSame($sponsor->id, $registered->ref_by);
        $this->assertSame($sponsor->id, $registered->pos_id);
        $this->assertSame(Status::LEFT, $registered->position);
        $this->assertDatabaseHas('user_extras', ['user_id' => $registered->id]);

        $sponsorExtra = UserExtra::where('user_id', $sponsor->id)->firstOrFail();
        $this->assertSame(1, $sponsorExtra->free_left);
    }

    public function test_direct_referral_income_uses_sponsor_plan_percentage_on_joining_package_amount(): void
    {
        foreach ($this->sponsorJoinPlanMatrix() as $caseName => $expected) {
            $sponsor = $this->createReadyUser([
                'username' => $this->uniqueUsername('refsp'),
                'plan_id'  => $expected['sponsor_plan_id'],
            ]);

            $childEmail = $this->uniqueEmail('ref-' . Str::slug($caseName));

            $this->post(route('user.register'), [
                'firstname'             => 'Direct',
                'lastname'              => 'Child',
                'referBy'               => $sponsor->username,
                'position'              => Status::LEFT,
                'email'                 => $childEmail,
                'password'              => 'secret123',
                'password_confirmation' => 'secret123',
            ])->assertRedirect();

            $child = User::where('email', $childEmail)->firstOrFail();
            $child->username = $this->uniqueUsername('refch');
            $child->profile_complete = Status::YES;
            $child->country_code = 'IN';
            $child->country_name = 'India';
            $child->dial_code = '91';
            $child->mobile = $this->uniqueMobile();
            $child->balance = $expected['joining_price'];
            $child->save();

            $startingSponsorBalance = (float) $sponsor->balance;

            $this->actingAs($child)
                ->from(route('user.plan.index'))
                ->post(route('user.plan.purchase'), ['plan_id' => $expected['joining_plan_id']])
                ->assertRedirect(route('user.plan.index'));

            $sponsor->refresh();

            $referralTransaction = Transaction::where('user_id', $sponsor->id)
                ->where('remark', 'referral_commission')
                ->latest('id')
                ->first();

            $this->assertNotNull($referralTransaction, "Referral transaction missing for {$caseName}.");
            $this->assertEquals($expected['expected_direct_income'], (float) $referralTransaction->amount, "Referral amount mismatch for {$caseName}.");
            $this->assertEquals($expected['expected_direct_income'], (float) $sponsor->total_ref_com, "Referral summary mismatch for {$caseName}.");
            $this->assertEquals($startingSponsorBalance + $expected['expected_direct_income'], (float) $sponsor->balance, "Sponsor wallet mismatch for {$caseName}.");
        }
    }

    public function test_binary_matching_income_is_credited_for_all_oriva_plans(): void
    {
        foreach ($this->orivaPlans() as $planName => $expected) {
            [$sponsor, $leftChild, $rightChild] = $this->createBinaryScenarioUsers($expected['id']);

            $this->purchasePlan($sponsor, $expected['id'], $expected['price']);
            $this->purchasePlan($leftChild, $expected['id'], $expected['price']);
            $this->purchasePlan($rightChild, $expected['id'], $expected['price']);

            $this->resetMatchingWindow();
            $this->from('/')->get(route('cron', ['alias' => 'matching-bonus']))->assertRedirect('/');

            $sponsor->refresh();
            $extra = UserExtra::where('user_id', $sponsor->id)->firstOrFail();
            $binaryTransaction = Transaction::where('user_id', $sponsor->id)
                ->where('remark', 'binary_commission')
                ->latest('id')
                ->first();

            $expectedIncome = round($expected['bv'] * ($expected['tree_com'] / 100), 8);

            $this->assertNotNull($binaryTransaction, "Binary transaction missing for {$planName}.");
            $this->assertEquals($expectedIncome, (float) $binaryTransaction->amount, "Binary income mismatch for {$planName}.");
            $this->assertEquals($expectedIncome, (float) $sponsor->total_binary_com, "Binary summary mismatch for {$planName}.");
            $this->assertStringContainsString((string) $expected['bv'], $binaryTransaction->details, "Transaction details missing matched BV for {$planName}.");
            $this->assertEquals(0.0, (float) $extra->bv_left, "Left carry mismatch for {$planName}.");
            $this->assertEquals(0.0, (float) $extra->bv_right, "Right carry mismatch for {$planName}.");

            $cutLogs = BvLog::where('user_id', $sponsor->id)->where('trx_type', '-')->get();
            $this->assertCount(2, $cutLogs, "Expected 2 BV cut logs for {$planName}.");
            $this->assertEqualsCanonicalizing(
                [round($expected['bv'], 8), round($expected['bv'], 8)],
                $cutLogs->pluck('amount')->map(fn ($amount) => round((float) $amount, 8))->all(),
                "BV cut logs mismatch for {$planName}."
            );
        }
    }

    public function test_daily_capping_limits_credit_and_carries_forward_bv(): void
    {
        $plan = Plan::findOrFail(3);
        $originalDaily = (float) $plan->daily_capping;
        $originalMonthly = (float) $plan->monthly_capping;

        $plan->daily_capping = 100;
        $plan->monthly_capping = 999999;
        $plan->save();

        [$sponsor, $leftChild, $rightChild] = $this->createBinaryScenarioUsers(3);

        $this->purchasePlan($sponsor, 3, (float) $plan->price);
        $this->purchasePlan($leftChild, 3, (float) $plan->price);
        $this->purchasePlan($rightChild, 3, (float) $plan->price);

        $this->resetMatchingWindow();
        $this->from('/')->get(route('cron', ['alias' => 'matching-bonus']))->assertRedirect('/');

        $sponsor->refresh();
        $extra = UserExtra::where('user_id', $sponsor->id)->firstOrFail();
        $transaction = Transaction::where('user_id', $sponsor->id)->where('remark', 'binary_commission')->latest('id')->firstOrFail();

        $this->assertEquals(100.0, (float) $transaction->amount);
        $this->assertEquals(100.0, (float) $sponsor->total_binary_com);
        $this->assertEquals(1500.0, round((float) $extra->bv_left, 8));
        $this->assertEquals(1500.0, round((float) $extra->bv_right, 8));
        $this->assertStringContainsString('Remaining matching BV carried forward due to capping', $transaction->details);

        $plan->daily_capping = $originalDaily;
        $plan->monthly_capping = $originalMonthly;
        $plan->save();
    }

    public function test_monthly_capping_limits_credit_and_carries_forward_bv(): void
    {
        $plan = Plan::findOrFail(3);
        $originalDaily = (float) $plan->daily_capping;
        $originalMonthly = (float) $plan->monthly_capping;

        $plan->daily_capping = 999999;
        $plan->monthly_capping = 200;
        $plan->save();

        [$sponsor, $leftChild, $rightChild] = $this->createBinaryScenarioUsers(3);

        $this->purchasePlan($sponsor, 3, (float) $plan->price);
        $this->purchasePlan($leftChild, 3, (float) $plan->price);
        $this->purchasePlan($rightChild, 3, (float) $plan->price);

        $this->resetMatchingWindow();
        $this->from('/')->get(route('cron', ['alias' => 'matching-bonus']))->assertRedirect('/');

        $sponsor->refresh();
        $extra = UserExtra::where('user_id', $sponsor->id)->firstOrFail();
        $transaction = Transaction::where('user_id', $sponsor->id)->where('remark', 'binary_commission')->latest('id')->firstOrFail();

        $this->assertEquals(200.0, (float) $transaction->amount);
        $this->assertEquals(200.0, (float) $sponsor->total_binary_com);
        $this->assertEquals(500.0, round((float) $extra->bv_left, 8));
        $this->assertEquals(500.0, round((float) $extra->bv_right, 8));

        $plan->daily_capping = $originalDaily;
        $plan->monthly_capping = $originalMonthly;
        $plan->save();
    }

    public function test_carried_forward_bv_participates_in_next_matching_cycle(): void
    {
        $plan = Plan::findOrFail(3);
        $originalDaily = (float) $plan->daily_capping;
        $originalMonthly = (float) $plan->monthly_capping;

        $plan->daily_capping = 100;
        $plan->monthly_capping = 999999;
        $plan->save();

        [$sponsor, $leftChild, $rightChild] = $this->createBinaryScenarioUsers(3);

        $this->purchasePlan($sponsor, 3, (float) $plan->price);
        $this->purchasePlan($leftChild, 3, (float) $plan->price);
        $this->purchasePlan($rightChild, 3, (float) $plan->price);

        $this->resetMatchingWindow();
        $this->from('/')->get(route('cron', ['alias' => 'matching-bonus']))->assertRedirect('/');

        $extra = UserExtra::where('user_id', $sponsor->id)->firstOrFail();
        $this->assertEquals(1500.0, round((float) $extra->bv_left, 8));
        $this->assertEquals(1500.0, round((float) $extra->bv_right, 8));

        $plan->daily_capping = 10000;
        $plan->save();

        $nextLeft = $this->createReadyUser([
            'ref_by'   => 0,
            'pos_id'   => $sponsor->id,
            'position' => Status::LEFT,
            'balance'  => (float) $plan->price,
        ]);
        $nextRight = $this->createReadyUser([
            'ref_by'   => 0,
            'pos_id'   => $sponsor->id,
            'position' => Status::RIGHT,
            'balance'  => (float) $plan->price,
        ]);
        updateFreeCount($nextLeft->id);
        updateFreeCount($nextRight->id);

        $this->purchasePlan($nextLeft, 3, (float) $plan->price);
        $this->purchasePlan($nextRight, 3, (float) $plan->price);

        $this->resetMatchingWindow();
        $this->from('/')->get(route('cron', ['alias' => 'matching-bonus']))->assertRedirect('/');

        $sponsor->refresh();
        $extra->refresh();

        $binaryTransactions = Transaction::where('user_id', $sponsor->id)
            ->where('remark', 'binary_commission')
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $binaryTransactions);
        $this->assertEquals(100.0, (float) $binaryTransactions[0]->amount);
        $this->assertEquals(250.0, (float) $binaryTransactions[1]->amount);
        $this->assertEquals(350.0, (float) $sponsor->total_binary_com);
        $this->assertEquals(0.0, round((float) $extra->bv_left, 8));
        $this->assertEquals(0.0, round((float) $extra->bv_right, 8));

        $plan->daily_capping = $originalDaily;
        $plan->monthly_capping = $originalMonthly;
        $plan->save();
    }

    private function createBinaryScenarioUsers(int $planId): array
    {
        $plan = Plan::findOrFail($planId);

        $sponsor = $this->createReadyUser();
        $leftChild = $this->createReadyUser([
            'ref_by'   => 0,
            'pos_id'   => $sponsor->id,
            'position' => Status::LEFT,
            'balance'  => (float) $plan->price,
        ]);
        $rightChild = $this->createReadyUser([
            'ref_by'   => 0,
            'pos_id'   => $sponsor->id,
            'position' => Status::RIGHT,
            'balance'  => (float) $plan->price,
        ]);

        updateFreeCount($leftChild->id);
        updateFreeCount($rightChild->id);

        return [$sponsor, $leftChild, $rightChild];
    }

    private function purchasePlan(User $user, int $planId, float $planPrice): void
    {
        if ((float) $user->balance < $planPrice) {
            $user->balance = $planPrice;
            $user->save();
        }

        $this->actingAs($user)
            ->from(route('user.plan.index'))
            ->post(route('user.plan.purchase'), ['plan_id' => $planId])
            ->assertRedirect(route('user.plan.index'));
    }

    private function resetMatchingWindow(): void
    {
        $general = GeneralSetting::firstOrFail();
        $general->matching_bonus_time = 'daily';
        $general->matching_when = date('H');
        $general->last_paid = now()->subDay()->startOfDay();
        $general->cary_flash = 0;
        $general->save();

        Cache::forget('GeneralSetting');
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

        if (!UserLogin::where('user_ip', '127.0.0.1')->exists()) {
            $login = new UserLogin();
            $login->user_id = 0;
            $login->user_ip = '127.0.0.1';
            $login->city = 'Local';
            $login->country = 'Local';
            $login->country_code = 'LO';
            $login->longitude = '0';
            $login->latitude = '0';
            $login->browser = 'PHPUnit';
            $login->os = 'Test';
            $login->save();
        }

        Cache::forget('GeneralSetting');
    }

    private function createReadyUser(array $overrides = []): User
    {
        $user = new User();
        $user->ref_by = $overrides['ref_by'] ?? 0;
        $user->pos_id = $overrides['pos_id'] ?? 0;
        $user->position = $overrides['position'] ?? 0;
        $user->plan_id = $overrides['plan_id'] ?? 0;
        $user->total_invest = $overrides['total_invest'] ?? 0;
        $user->firstname = $overrides['firstname'] ?? 'QA';
        $user->lastname = $overrides['lastname'] ?? 'User';
        $user->username = $overrides['username'] ?? $this->uniqueUsername('qauser');
        $user->email = $overrides['email'] ?? $this->uniqueEmail('qa');
        $user->dial_code = $overrides['dial_code'] ?? '91';
        $user->country_code = $overrides['country_code'] ?? 'IN';
        $user->mobile = $overrides['mobile'] ?? $this->uniqueMobile();
        $user->balance = $overrides['balance'] ?? 0;
        $user->total_binary_com = $overrides['total_binary_com'] ?? 0;
        $user->total_ref_com = $overrides['total_ref_com'] ?? 0;
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

    private function uniqueUsername(string $prefix): string
    {
        return Str::limit($prefix . strtolower(Str::random(12)), 40, '');
    }

    private function uniqueEmail(string $prefix): string
    {
        return Str::limit($prefix . strtolower(Str::random(10)), 28, '') . '@example.test';
    }

    private function uniqueMobile(): string
    {
        return '9' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
    }

    private function orivaPlans(): array
    {
        return [
            'Vision Pack' => ['id' => 3, 'price' => 2500.0, 'bv' => 2500.0, 'ref_com' => 10.0, 'tree_com' => 10.0],
            'Gold Pack' => ['id' => 4, 'price' => 7500.0, 'bv' => 7500.0, 'ref_com' => 12.0, 'tree_com' => 10.0],
            'Premium Pack' => ['id' => 5, 'price' => 15000.0, 'bv' => 15000.0, 'ref_com' => 14.0, 'tree_com' => 12.0],
            'Royal Pack' => ['id' => 6, 'price' => 30000.0, 'bv' => 30000.0, 'ref_com' => 18.0, 'tree_com' => 15.0],
        ];
    }

    private function sponsorJoinPlanMatrix(): array
    {
        return [
            'Vision sponsor, Gold join' => [
                'sponsor_plan_id' => 3,
                'joining_plan_id' => 4,
                'joining_price' => 7500.0,
                'expected_direct_income' => 750.0,
            ],
            'Gold sponsor, Vision join' => [
                'sponsor_plan_id' => 4,
                'joining_plan_id' => 3,
                'joining_price' => 2500.0,
                'expected_direct_income' => 300.0,
            ],
            'Premium sponsor, Royal join' => [
                'sponsor_plan_id' => 5,
                'joining_plan_id' => 6,
                'joining_price' => 30000.0,
                'expected_direct_income' => 4200.0,
            ],
            'Royal sponsor, Vision join' => [
                'sponsor_plan_id' => 6,
                'joining_plan_id' => 3,
                'joining_price' => 2500.0,
                'expected_direct_income' => 450.0,
            ],
        ];
    }
}

<?php

namespace Tests\Feature;

use App\Constants\Status;
use App\Models\BvLog;
use App\Models\Extension;
use App\Models\GeneralSetting;
use App\Models\LevelIncomeLog;
use App\Models\Plan;
use App\Models\Rank;
use App\Models\RankRewardLog;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserExtra;
use App\Models\UserLogin;
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
class OrivaCompensationEngineTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Auth::logout();
        Session::flush();
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
            Auth::logout();
            Session::flush();

            $sponsor = $this->createReadyUser([
                'username' => $this->uniqueUsername('refsp'),
                'plan_id'  => $expected['sponsor_plan_id'],
            ]);

            $child = $this->createReadyUser([
                'ref_by'   => $sponsor->id,
                'pos_id'   => $sponsor->id,
                'position' => Status::LEFT,
                'balance'  => $expected['joining_price'],
            ]);
            updateFreeCount($child->id);

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
        $this->assertEquals(400.0, (float) $binaryTransactions[1]->amount);
        $this->assertEquals(500.0, (float) $sponsor->total_binary_com);
        $this->assertEquals(0.0, round((float) $extra->bv_left, 8));
        $this->assertEquals(0.0, round((float) $extra->bv_right, 8));

        $plan->daily_capping = $originalDaily;
        $plan->monthly_capping = $originalMonthly;
        $plan->save();
    }

    public function test_level_income_is_distributed_to_10_sponsor_levels_from_matching_income(): void
    {
        $receiverPlan = Plan::findOrFail(5);
        $uplines = [];
        $refBy = 0;

        for ($i = 1; $i <= 10; $i++) {
            $uplines[$i] = $this->createReadyUser([
                'ref_by'  => $refBy,
                'plan_id' => 3,
            ]);
            $refBy = $uplines[$i]->id;
        }

        $matchingReceiver = $this->createReadyUser([
            'ref_by' => $uplines[10]->id,
        ]);

        [$leftChild, $rightChild] = $this->attachBinaryChildren($matchingReceiver, (float) $receiverPlan->price);

        $this->purchasePlan($matchingReceiver, 5, (float) $receiverPlan->price);
        $this->purchasePlan($leftChild, 5, (float) $receiverPlan->price);
        $this->purchasePlan($rightChild, 5, (float) $receiverPlan->price);

        $this->resetMatchingWindow();
        $this->from('/')->get(route('cron', ['alias' => 'matching-bonus']))->assertRedirect('/');

        $matchingReceiver->refresh();
        $matchingTransaction = Transaction::where('user_id', $matchingReceiver->id)
            ->where('remark', 'binary_commission')
            ->latest('id')
            ->firstOrFail();

        $this->assertEquals(1800.0, (float) $matchingTransaction->amount);

        $expectedByLevel = [
            1 => 180.0,
            2 => 72.0,
            3 => 54.0,
            4 => 54.0,
            5 => 36.0,
            6 => 36.0,
            7 => 36.0,
            8 => 36.0,
            9 => 18.0,
            10 => 18.0,
        ];

        foreach ($expectedByLevel as $level => $expectedAmount) {
            $receiver = $uplines[11 - $level];
            $receiver->refresh();

            $log = LevelIncomeLog::where('receiver_user_id', $receiver->id)
                ->where('source_user_id', $matchingReceiver->id)
                ->where('matching_transaction_id', $matchingTransaction->id)
                ->where('level_no', $level)
                ->first();

            $transaction = Transaction::where('user_id', $receiver->id)
                ->where('remark', 'level_income')
                ->where('details', 'like', '%Level ' . $level . ' income%')
                ->latest('id')
                ->first();

            $this->assertNotNull($log, "Level income log missing at level {$level}.");
            $this->assertSame('paid', $log->status, "Level income status mismatch at level {$level}.");
            $this->assertEquals($expectedAmount, (float) $log->amount, "Level income amount mismatch in log at level {$level}.");
            $this->assertNotNull($transaction, "Level income transaction missing at level {$level}.");
            $this->assertEquals($expectedAmount, (float) $transaction->amount, "Level income transaction amount mismatch at level {$level}.");
            $this->assertEquals($expectedAmount, (float) $receiver->total_level_income, "Level income summary mismatch at level {$level}.");
        }
    }

    public function test_level_income_is_not_credited_twice_for_same_matching_transaction_and_level(): void
    {
        $upline = $this->createReadyUser([
            'plan_id' => 3,
        ]);
        $matchingReceiver = $this->createReadyUser([
            'ref_by' => $upline->id,
        ]);

        $matchingTransaction = new Transaction();
        $matchingTransaction->user_id = $matchingReceiver->id;
        $matchingTransaction->amount = 250;
        $matchingTransaction->charge = 0;
        $matchingTransaction->trx_type = '+';
        $matchingTransaction->remark = 'binary_commission';
        $matchingTransaction->trx = getTrx();
        $matchingTransaction->post_balance = 250;
        $matchingTransaction->details = 'Test binary matching income.';
        $matchingTransaction->save();

        $service = app(\App\Services\LevelIncomeService::class);
        $service->distribute($matchingReceiver, $matchingTransaction, 250);
        $service->distribute($matchingReceiver, $matchingTransaction, 250);

        $upline->refresh();

        $this->assertEquals(25.0, (float) $upline->balance);
        $this->assertEquals(25.0, (float) $upline->total_level_income);
        $this->assertEquals(1, LevelIncomeLog::where('receiver_user_id', $upline->id)
            ->where('source_user_id', $matchingReceiver->id)
            ->where('matching_transaction_id', $matchingTransaction->id)
            ->where('level_no', 1)
            ->count());
        $this->assertEquals(1, Transaction::where('user_id', $upline->id)
            ->where('remark', 'level_income')
            ->where('details', 'like', '%Level 1 income%')
            ->count());
    }

    public function test_level_income_skips_inactive_upline_and_continues_to_next_levels(): void
    {
        $receiverPlan = Plan::findOrFail(3);
        $level3 = $this->createReadyUser(['plan_id' => 3]);
        $level2 = $this->createReadyUser([
            'ref_by'  => $level3->id,
            'plan_id' => 3,
            'status'  => Status::USER_BAN,
        ]);
        $level1 = $this->createReadyUser([
            'ref_by'  => $level2->id,
            'plan_id' => 3,
        ]);

        $matchingReceiver = $this->createReadyUser([
            'ref_by' => $level1->id,
        ]);

        [$leftChild, $rightChild] = $this->attachBinaryChildren($matchingReceiver, (float) $receiverPlan->price);

        $this->purchasePlan($matchingReceiver, 3, (float) $receiverPlan->price);
        $this->purchasePlan($leftChild, 3, (float) $receiverPlan->price);
        $this->purchasePlan($rightChild, 3, (float) $receiverPlan->price);

        $this->resetMatchingWindow();
        $this->from('/')->get(route('cron', ['alias' => 'matching-bonus']))->assertRedirect('/');

        $matchingTransaction = Transaction::where('user_id', $matchingReceiver->id)
            ->where('remark', 'binary_commission')
            ->latest('id')
            ->firstOrFail();

        $level1->refresh();
        $level2->refresh();
        $level3->refresh();

        $this->assertEquals(25.0, (float) $level1->total_level_income);
        $this->assertEquals(0.0, (float) $level2->total_level_income);
        $this->assertEquals(7.5, (float) $level3->total_level_income);

        $this->assertDatabaseHas('level_income_logs', [
            'receiver_user_id' => $level1->id,
            'source_user_id' => $matchingReceiver->id,
            'matching_transaction_id' => $matchingTransaction->id,
            'level_no' => 1,
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('level_income_logs', [
            'receiver_user_id' => $level2->id,
            'source_user_id' => $matchingReceiver->id,
            'matching_transaction_id' => $matchingTransaction->id,
            'level_no' => 2,
            'status' => 'skipped_inactive',
        ]);

        $this->assertDatabaseHas('level_income_logs', [
            'receiver_user_id' => $level3->id,
            'source_user_id' => $matchingReceiver->id,
            'matching_transaction_id' => $matchingTransaction->id,
            'level_no' => 3,
            'status' => 'paid',
        ]);
    }

    public function test_level_income_respects_daily_capping(): void
    {
        $plan = Plan::findOrFail(3);
        $originalDaily = (float) $plan->daily_capping;
        $originalMonthly = (float) $plan->monthly_capping;

        $plan->daily_capping = 260;
        $plan->monthly_capping = 999999;
        $plan->save();

        $upline = $this->createReadyUser([
            'plan_id' => 3,
        ]);
        $matchingReceiver = $this->createReadyUser([
            'ref_by' => $upline->id,
        ]);
        [$leftChild, $rightChild] = $this->attachBinaryChildren($matchingReceiver, 2500.0);

        $this->purchasePlan($upline, 3, 2500.0);
        $this->purchasePlan($matchingReceiver, 3, 2500.0);
        $this->purchasePlan($leftChild, 3, 2500.0);
        $this->purchasePlan($rightChild, 3, 2500.0);

        $upline->refresh();
        $this->assertEquals(250.0, (float) $upline->total_ref_com);

        $this->resetMatchingWindow();
        $this->from('/')->get(route('cron', ['alias' => 'matching-bonus']))->assertRedirect('/');

        $upline->refresh();
        $log = LevelIncomeLog::where('receiver_user_id', $upline->id)
            ->where('source_user_id', $matchingReceiver->id)
            ->where('level_no', 1)
            ->latest('id')
            ->firstOrFail();

        $this->assertEquals(10.0, (float) $upline->total_level_income);
        $this->assertEquals(10.0, (float) $log->amount);
        $this->assertSame('paid', $log->status);

        $plan->daily_capping = $originalDaily;
        $plan->monthly_capping = $originalMonthly;
        $plan->save();
    }

    public function test_level_income_history_page_loads_for_receiver(): void
    {
        $upline = $this->createReadyUser([
            'plan_id' => 3,
        ]);
        $matchingReceiver = $this->createReadyUser([
            'ref_by' => $upline->id,
        ]);
        [$leftChild, $rightChild] = $this->attachBinaryChildren($matchingReceiver, 2500.0);

        $this->purchasePlan($matchingReceiver, 3, 2500.0);
        $this->purchasePlan($leftChild, 3, 2500.0);
        $this->purchasePlan($rightChild, 3, 2500.0);

        $this->resetMatchingWindow();
        $this->from('/')->get(route('cron', ['alias' => 'matching-bonus']))->assertRedirect('/');

        $this->actingAs($upline)
            ->get(route('user.level.income.index'))
            ->assertOk()
            ->assertSee('Level Income')
            ->assertSee($matchingReceiver->username);
    }

    public function test_rank_reward_is_credited_when_team_dp_reaches_threshold(): void
    {
        $plan = Plan::findOrFail(3);
        $rank = Rank::orderBy('id')->firstOrFail();
        $rank->required_team_dp = 5000;
        $rank->reward_amount = 321.25;
        $rank->save();

        $sponsor = $this->createReadyUser([
            'balance' => (float) $plan->price,
        ]);
        [$leftChild, $rightChild] = $this->attachBinaryChildren($sponsor, (float) $plan->price);

        $this->purchasePlan($sponsor, 3, (float) $plan->price);
        $this->purchasePlan($leftChild, 3, (float) $plan->price);
        $this->purchasePlan($rightChild, 3, (float) $plan->price);

        $sponsor->refresh();

        $transaction = Transaction::where('user_id', $sponsor->id)
            ->where('remark', 'rank_reward')
            ->latest('id')
            ->first();

        $log = RankRewardLog::where('user_id', $sponsor->id)
            ->where('rank_id', $rank->id)
            ->first();

        $this->assertNotNull($transaction, 'Rank reward transaction missing.');
        $this->assertNotNull($log, 'Rank reward log missing.');
        $this->assertEquals(5000.0, (float) $sponsor->total_team_dp);
        $this->assertEquals(321.25, (float) $sponsor->total_rank_reward);
        $this->assertEquals(321.25, (float) $sponsor->balance);
        $this->assertSame($rank->id, $sponsor->current_rank_id);
        $this->assertEquals(321.25, (float) $transaction->amount);
        $this->assertEquals(321.25, (float) $log->reward_amount);
        $this->assertSame('paid', $log->status);
    }

    public function test_rank_reward_is_not_credited_twice_for_same_rank(): void
    {
        $plan = Plan::findOrFail(3);
        $rank = Rank::orderBy('id')->firstOrFail();
        $rank->required_team_dp = 5000;
        $rank->reward_amount = 321.25;
        $rank->save();

        $sponsor = $this->createReadyUser([
            'balance' => (float) $plan->price,
        ]);
        [$leftChild, $rightChild] = $this->attachBinaryChildren($sponsor, (float) $plan->price);

        $this->purchasePlan($sponsor, 3, (float) $plan->price);
        $this->purchasePlan($leftChild, 3, (float) $plan->price);
        $this->purchasePlan($rightChild, 3, (float) $plan->price);

        $extraChild = $this->createReadyUser([
            'pos_id' => $sponsor->id,
        ]);

        updateBV($extraChild->id, 1000, 'Additional team DP');

        $sponsor->refresh();

        $this->assertEquals(6000.0, (float) $sponsor->total_team_dp);
        $this->assertEquals(321.25, (float) $sponsor->total_rank_reward);
        $this->assertEquals(1, RankRewardLog::where('user_id', $sponsor->id)->where('rank_id', $rank->id)->count());
        $this->assertEquals(1, Transaction::where('user_id', $sponsor->id)->where('remark', 'rank_reward')->count());
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

    private function attachBinaryChildren(User $parent, float $planPrice): array
    {
        $leftChild = $this->createReadyUser([
            'ref_by'   => 0,
            'pos_id'   => $parent->id,
            'position' => Status::LEFT,
            'balance'  => $planPrice,
        ]);
        $rightChild = $this->createReadyUser([
            'ref_by'   => 0,
            'pos_id'   => $parent->id,
            'position' => Status::RIGHT,
            'balance'  => $planPrice,
        ]);

        updateFreeCount($leftChild->id);
        updateFreeCount($rightChild->id);

        return [$leftChild, $rightChild];
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
        $general = gs();
        $general->matching_bonus_time = 'manual';
        $general->matching_when = 'manual';
        $general->last_paid = now()->subDay()->startOfDay();
        $general->cary_flash = 0;
        $general->save();
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

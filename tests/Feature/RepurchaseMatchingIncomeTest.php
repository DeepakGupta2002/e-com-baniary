<?php

namespace Tests\Feature;

use App\Constants\Status;
use App\Models\Admin;
use App\Models\Category;
use App\Models\Extension;
use App\Models\FastStartBonusLog;
use App\Models\GeneralSetting;
use App\Models\LeaderGrowthBonusLog;
use App\Models\LevelIncomeLog;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Product;
use App\Models\RepurchaseBvLog;
use App\Models\RepurchaseMatchingLog;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserExtra;
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
class RepurchaseMatchingIncomeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Auth::logout();
        Session::flush();
        $this->seedTestingRuntimeDefaults();
    }

    public function test_active_plan_user_can_purchase_products(): void
    {
        $user = $this->createReadyUser(['plan_id' => $this->plan()->id, 'balance' => 5000]);
        $product = $this->product(['price' => 1000, 'quantity' => 5]);

        $this->actingAs($user)
            ->from(route('product.details', ['id' => $product->id, 'slug' => slug($product->name)]))
            ->post(route('user.purchase'), ['product_id' => $product->id, 'quantity' => 1])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', ['user_id' => $user->id, 'product_id' => $product->id, 'status' => Status::ORDER_PENDING]);
        $this->assertEquals(4000.0, (float) $user->fresh()->balance);
        $this->assertEquals(4, (int) $product->fresh()->quantity);
    }

    public function test_inactive_plan_user_cannot_purchase_products(): void
    {
        $user = $this->createReadyUser(['plan_id' => 0, 'balance' => 5000]);
        $product = $this->product(['price' => 1000, 'quantity' => 5]);

        $this->actingAs($user)
            ->from(route('product.details', ['id' => $product->id, 'slug' => slug($product->name)]))
            ->post(route('user.purchase'), ['product_id' => $product->id, 'quantity' => 1])
            ->assertRedirect();

        $this->assertEquals(5000.0, (float) $user->fresh()->balance);
        $this->assertEquals(5, (int) $product->fresh()->quantity);
        $this->assertEquals(0, Order::where('user_id', $user->id)->count());
        $this->assertEquals(0, RepurchaseBvLog::where('from_user_id', $user->id)->count());
    }

    public function test_pending_and_cancelled_orders_generate_no_repurchase_bv(): void
    {
        [$root, $left] = $this->networkWithLeftBuyer();
        $order = $this->purchaseOrder($left, $this->product(['bv' => 1000]));

        $this->assertEquals(0, RepurchaseBvLog::where('order_id', $order->id)->count());

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.order.status', $order->id), ['status' => Status::ORDER_CANCELED])
            ->assertRedirect();

        $this->assertEquals(0, RepurchaseBvLog::where('order_id', $order->id)->count());
        $this->assertEquals(0.0, (float) $root->fresh()->repurchase_left_bv);
    }

    public function test_shipped_order_generates_repurchase_bv_and_exact_12_percent_income(): void
    {
        [$root, $left, $right] = $this->networkWithBothBuyers();
        $product = $this->product(['price' => 1000, 'bv' => 1000, 'quantity' => 10]);

        $this->shipOrder($this->purchaseOrder($left, $product));
        $this->shipOrder($this->purchaseOrder($right, $product));

        $root->refresh();

        $this->assertEquals(1000.0, (float) $root->repurchase_left_bv);
        $this->assertEquals(1000.0, (float) $root->repurchase_right_bv);
        $this->assertEquals(0.0, (float) $root->repurchase_left_carry);
        $this->assertEquals(0.0, (float) $root->repurchase_right_carry);
        $this->assertEquals(120.0, (float) $root->total_repurchase_matching_income);
        $this->assertEquals(120.0, (float) $root->balance);
        $this->assertDatabaseHas('transactions', ['user_id' => $root->id, 'remark' => 'repurchase_matching_income', 'trx_type' => '+']);
        $this->assertEquals(2, RepurchaseBvLog::where('user_id', $root->id)->count());
        $this->assertEquals(1, RepurchaseMatchingLog::where('user_id', $root->id)->count());
    }

    public function test_carry_forward_works_correctly(): void
    {
        [$root, $left, $right] = $this->networkWithBothBuyers();
        $product = $this->product(['price' => 1000, 'bv' => 1000, 'quantity' => 20]);

        $this->shipOrder($this->purchaseOrder($left, $product, 3));
        $this->shipOrder($this->purchaseOrder($right, $product, 5));

        $root->refresh();
        $log = RepurchaseMatchingLog::where('user_id', $root->id)->firstOrFail();

        $this->assertEquals(3000.0, (float) $log->matched_bv);
        $this->assertEquals(360.0, (float) $log->income);
        $this->assertEquals(0.0, (float) $root->repurchase_left_carry);
        $this->assertEquals(2000.0, (float) $root->repurchase_right_carry);
    }

    public function test_dashboard_history_admin_report_and_profile_are_correct(): void
    {
        [$root, $left, $right] = $this->networkWithBothBuyers();
        $product = $this->product(['bv' => 1000, 'quantity' => 10]);

        $this->shipOrder($this->purchaseOrder($left, $product));
        $this->shipOrder($this->purchaseOrder($right, $product));

        $this->actingAs($root->fresh())
            ->get(route('user.home'))
            ->assertOk()
            ->assertSee('Repurchase Matching Income')
            ->assertSee('120');

        $this->actingAs($root->fresh())
            ->get(route('user.repurchase.matching.index'))
            ->assertOk()
            ->assertSee('Repurchase Matching History')
            ->assertSee('1000')
            ->assertSee('120');

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.report.repurchase.matching'))
            ->assertOk()
            ->assertSee($root->username)
            ->assertSee('120');

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.users.detail', $root->id))
            ->assertOk()
            ->assertSee('Repurchase Matching')
            ->assertSee('Total Income')
            ->assertSee('120');
    }

    public function test_duplicate_shipped_and_returned_like_status_change_do_not_create_duplicate_income(): void
    {
        [$root, $left, $right] = $this->networkWithBothBuyers();
        $product = $this->product(['bv' => 1000, 'quantity' => 10]);

        $this->shipOrder($this->purchaseOrder($left, $product));
        $rightOrder = $this->purchaseOrder($right, $product);
        $this->shipOrder($rightOrder);

        $rightOrder->refresh();
        $rightOrder->status = Status::ORDER_PENDING;
        $rightOrder->save();

        $this->shipOrder($rightOrder);

        $root->refresh();
        $this->assertEquals(120.0, (float) $root->total_repurchase_matching_income);
        $this->assertEquals(1, RepurchaseBvLog::where('order_id', $rightOrder->id)->count());
        $this->assertEquals(1, Transaction::where('user_id', $root->id)->where('remark', 'repurchase_matching_income')->count());
    }

    public function test_product_repurchase_does_not_trigger_other_compensation_modules(): void
    {
        [$root, $left, $right] = $this->networkWithBothBuyers();
        $product = $this->product(['bv' => 1000, 'quantity' => 10]);

        $this->shipOrder($this->purchaseOrder($left, $product));
        $this->shipOrder($this->purchaseOrder($right, $product));

        $this->assertEquals(0, Transaction::whereIn('remark', ['referral_commission', 'level_income', 'fast_start_bonus', 'leader_growth_bonus'])->where('user_id', $root->id)->count());
        $this->assertEquals(0, LevelIncomeLog::where('receiver_user_id', $root->id)->count());
        $this->assertEquals(0, FastStartBonusLog::where('user_id', $root->id)->count());
        $this->assertEquals(0, LeaderGrowthBonusLog::where('user_id', $root->id)->where('status', 'paid')->count());
        $this->assertEquals(1, Transaction::where('user_id', $root->id)->where('remark', 'repurchase_matching_income')->count());
    }

    private function shipOrder(Order $order): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.order.status', $order->id), ['status' => Status::ORDER_SHIPPED])
            ->assertRedirect();
    }

    private function purchaseOrder(User $user, Product $product, int $quantity = 1): Order
    {
        $this->actingAs($user->fresh())
            ->post(route('user.purchase'), ['product_id' => $product->id, 'quantity' => $quantity])
            ->assertRedirect();

        return Order::where('user_id', $user->id)->where('product_id', $product->id)->latest('id')->firstOrFail();
    }

    private function networkWithLeftBuyer(): array
    {
        $plan = $this->plan();
        $root = $this->createReadyUser(['plan_id' => $plan->id, 'balance' => 0]);
        $left = $this->createReadyUser(['plan_id' => $plan->id, 'pos_id' => $root->id, 'position' => Status::LEFT, 'balance' => 20000]);

        return [$root, $left];
    }

    private function networkWithBothBuyers(): array
    {
        [$root, $left] = $this->networkWithLeftBuyer();
        $right = $this->createReadyUser(['plan_id' => $this->plan()->id, 'pos_id' => $root->id, 'position' => Status::RIGHT, 'balance' => 20000]);

        return [$root, $left, $right];
    }

    private function product(array $overrides = []): Product
    {
        $category = Category::where('name', 'Repurchase Test')->first();
        if (!$category) {
            $category = new Category();
            $category->name = 'Repurchase Test';
        }
        $category->status = Status::ENABLE;
        $category->save();

        $product = new Product();
        $product->category_id = $category->id;
        $product->name = $overrides['name'] ?? 'Repurchase Product ' . Str::random(6);
        $product->price = $overrides['price'] ?? 1000;
        $product->quantity = $overrides['quantity'] ?? 20;
        $product->bv = $overrides['bv'] ?? 1000;
        $product->description = $overrides['description'] ?? 'Repurchase product for tests.';
        $product->thumbnail = $overrides['thumbnail'] ?? 'default.png';
        $product->meta_title = $overrides['meta_title'] ?? $product->name;
        $product->meta_description = $overrides['meta_description'] ?? $product->name;
        $product->meta_keyword = $overrides['meta_keyword'] ?? ['repurchase'];
        $product->status = $overrides['status'] ?? Status::ENABLE;
        $product->is_featured = $overrides['is_featured'] ?? Status::DISABLE;
        $product->save();

        return $product;
    }

    private function plan(): Plan
    {
        return Plan::active()->first() ?: Plan::firstOrFail();
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
        $user->firstname = $overrides['firstname'] ?? 'Repurchase';
        $user->lastname = $overrides['lastname'] ?? 'User';
        $user->username = $overrides['username'] ?? 'rp' . strtolower(Str::random(12));
        $user->email = $overrides['email'] ?? 'rp' . strtolower(Str::random(12)) . '@example.test';
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

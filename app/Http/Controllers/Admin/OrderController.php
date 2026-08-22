<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use App\Constants\Status;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\RepurchaseBVService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index($userId = null)
    {
        $pageTitle = 'Orders';
        $orders    = Order::searchable(['trx', 'user:username', 'product:name']);
        if ($userId) {
            $orders = $orders->where('user_id', $userId);
        }
        $orders = $orders->with('product', 'user')->orderBy('id', 'desc')->paginate(getPaginate());

        $emptyMessage = 'Order not found';
        return view('admin.orders', compact('pageTitle', 'orders', 'emptyMessage'));
    }

    public function invoice($id)
    {
        $order = Order::with(['product', 'user'])->findOrFail($id);
        $general = gs();
        $pageTitle = 'Invoice #' . invoiceNumber($order);
        $logoUrl = getImage(getFilePath('logoIcon') . '/logo_dark.png');
        $downloadUrl = route('admin.order.invoice.download', $order->id);

        return view('invoice.order', compact('pageTitle', 'order', 'general', 'logoUrl', 'downloadUrl'));
    }

    public function invoiceDownload($id)
    {
        $order = Order::with(['product', 'user'])->findOrFail($id);
        $general = gs();
        $pageTitle = 'Invoice #' . invoiceNumber($order);
        $logoUrl = invoiceLogoDataUri();
        $isPdf = true;

        $pdf = Pdf::loadView('invoice.order_pdf', compact('pageTitle', 'order', 'general', 'logoUrl', 'isPdf'))
            ->setPaper('a4');

        return $pdf->download(invoiceNumber($order) . '.pdf');
    }

    public function status(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:1,2'
        ]);

        $order = null;
        $template = null;

        DB::transaction(function () use ($request, $id, &$order, &$template) {
            $order   = Order::where('status', Status::ORDER_PENDING)->lockForUpdate()->findOrFail($id);
            $product = $order->product;
            $user    = $order->user;

            if ($request->status == Status::ORDER_SHIPPED) {
                $order->status = Status::ORDER_SHIPPED;

                if (!$order->repurchase_processed_at) {
                    app(RepurchaseBVService::class)->processOrder($order);
                    $order->repurchase_processed_at = now();
                }

                $template = 'ORDER_SHIPPED';
            } else {
                $order->status  = Status::ORDER_CANCELED;
                $user->balance += $order->total_price;
                $user->save();

                $transaction               = new Transaction();
                $transaction->user_id      = $order->user_id;
                $transaction->amount       = $order->total_price;
                $transaction->post_balance = $user->balance;
                $transaction->charge       = 0;
                $transaction->trx_type     = '+';
                $transaction->details      = $product->name . ' Order cancel';
                $transaction->trx          = $order->trx;
                $transaction->save();

                $product->quantity += $order->quantity;
                $product->save();

                $template = 'ORDER_CANCELED';
            }

            $order->save();
        });

        $order->loadMissing(['product', 'user']);
        $product = $order->product;
        $user = $order->user;

        notify($user, $template, [
            'product_name' => $product->name,
            'quantity'     => $order->quantity,
            'price'        => showAmount($product->price, currencyFormat: false),
            'total_price'  => showAmount($order->total_price, currencyFormat: false),
            'trx'          => $order->trx,
        ]);

        $notify[] = ['success', 'Product status updated successfully'];
        return back()->withNotify($notify);
    }
}

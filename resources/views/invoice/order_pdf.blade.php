<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>{{ $pageTitle }}</title>
    <style>
        body {
            color: #172033;
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.45;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header {
            border-bottom: 3px solid #0f766e;
            padding-bottom: 12px;
        }

        .logo {
            width: 82px;
            max-height: 58px;
        }

        .company-name {
            color: #0f766e;
            font-size: 20px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .muted {
            color: #667085;
        }

        .title {
            font-size: 26px;
            font-weight: 700;
            text-align: right;
        }

        .badge {
            color: #047857;
            font-size: 11px;
            font-weight: 700;
            text-align: right;
            text-transform: uppercase;
        }

        .box {
            border: 1px solid #e5e7eb;
            padding: 10px;
            vertical-align: top;
        }

        .section-title {
            color: #0f766e;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .items th {
            background: #0f766e;
            color: #fff;
            padding: 8px;
            text-align: left;
        }

        .items td {
            border-bottom: 1px solid #e5e7eb;
            padding: 8px;
            vertical-align: top;
        }

        .text-end {
            text-align: right;
        }

        .totals {
            width: 42%;
            margin-left: auto;
        }

        .totals td {
            border: 1px solid #e5e7eb;
            padding: 8px;
        }

        .grand td {
            background: #0f766e;
            color: #fff;
            font-weight: 700;
        }

        .signature {
            border-top: 1px solid #111827;
            padding-top: 8px;
            text-align: center;
            font-weight: 700;
        }

        .signature-mark {
            color: #0f766e;
            font-size: 24px;
            font-style: italic;
            font-weight: 400;
        }
    </style>
</head>

<body>
    @php
        $companyName = $general->company_name ?: $general->site_name;
        $companyAddress = $general->company_address ?: 'N/A';
        $companyMobile = $general->company_mobile ?: 'N/A';
        $companyEmail = $general->company_email ?: $general->email_from ?: 'N/A';
        $companyWebsite = rtrim(config('app.url'), '/');
        $companyGstin = $general->company_gstin ?: 'N/A';
        $companyPan = $general->company_pan ?: 'N/A';
        $invoiceNo = invoiceNumber($order);
        $statusText = $order->status == \App\Constants\Status::ORDER_SHIPPED ? 'Shipped' : ($order->status == \App\Constants\Status::ORDER_CANCELED ? 'Canceled' : 'Pending');
        $subtotal = (float) ($order->subtotal ?: ($order->price * $order->quantity));
        $gstAmount = (float) ($order->gst_amount ?? 0);
        $customerName = $order->delivery_name ?: ($order->user->fullname ?? 'Customer');
        $customerUsername = $order->user->username ?? 'N/A';
        $customerEmail = $order->user->email ?? 'N/A';
        $customerMobile = $order->delivery_mobile ?: ($order->user->mobileNumber ?? 'N/A');
        $productName = $order->product->name ?? 'Product';
    @endphp

    <table class="header">
        <tr>
            @if ($logoUrl)
                <td style="width: 92px;">
                    <img class="logo" src="{{ $logoUrl }}" alt="Logo">
                </td>
            @endif
            <td>
                <div class="company-name">{{ __($companyName) }}</div>
                <div>{{ __($companyAddress) }}</div>
                <div class="muted">Mobile: {{ __($companyMobile) }} | Email: {{ __($companyEmail) }}</div>
                <div class="muted">Website: {{ $companyWebsite }}</div>
                <div class="muted">GSTIN: {{ __($companyGstin) }} | PAN: {{ __($companyPan) }}</div>
            </td>
            <td style="width: 180px;">
                <div class="title">TAX INVOICE</div>
                <div class="badge">{{ __($statusText) }}</div>
            </td>
        </tr>
    </table>

    <table style="margin-top: 18px;">
        <tr>
            <td class="box" style="width: 50%;">
                <div class="section-title">Invoice Details</div>
                <table>
                    <tr><td>Invoice No</td><td class="text-end"><strong>{{ $invoiceNo }}</strong></td></tr>
                    <tr><td>Invoice Date</td><td class="text-end"><strong>{{ showDateTime($order->created_at, 'd M Y, h:i A') }}</strong></td></tr>
                    <tr><td>Order ID</td><td class="text-end"><strong>#{{ $order->id }}</strong></td></tr>
                    <tr><td>Transaction ID</td><td class="text-end"><strong>{{ $order->trx }}</strong></td></tr>
                    <tr><td>Payment Mode</td><td class="text-end"><strong>Wallet</strong></td></tr>
                </table>
            </td>
            <td style="width: 12px;"></td>
            <td class="box" style="width: 50%;">
                <div class="section-title">Bill To / Ship To</div>
                <strong>{{ __($customerName) }}</strong><br>
                <span class="muted">{{ '@' . $customerUsername }} | {{ $customerEmail }}</span><br>
                {{ __($customerMobile) }}<br>
                {{ __($order->delivery_address ?: 'N/A') }}<br>
                {{ __($order->delivery_city) }}, {{ __($order->delivery_state) }} - {{ __($order->delivery_zip) }}<br>
                {{ __($order->delivery_country) }}
            </td>
        </tr>
    </table>

    <table class="items" style="margin-top: 18px;">
        <thead>
            <tr>
                <th style="width: 35px;">#</th>
                <th>Product</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Unit Price</th>
                <th class="text-end">Subtotal</th>
                <th class="text-end">GST</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>{{ __($productName) }}</td>
                <td class="text-end">{{ $order->quantity }}</td>
                <td class="text-end">{{ showAmount($order->price) }}</td>
                <td class="text-end">{{ showAmount($subtotal) }}</td>
                <td class="text-end">
                    @if ($order->gst_status)
                        {{ getAmount($order->gst_percent) }}% {{ __(ucfirst($order->gst_type)) }}<br>{{ showAmount($gstAmount) }}
                    @else
                        GST Not Applicable<br>{{ showAmount(0) }}
                    @endif
                </td>
                <td class="text-end">{{ showAmount($order->total_price) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="totals" style="margin-top: 16px;">
        <tr><td>Subtotal</td><td class="text-end"><strong>{{ showAmount($subtotal) }}</strong></td></tr>
        <tr><td>GST Amount</td><td class="text-end"><strong>{{ showAmount($gstAmount) }}</strong></td></tr>
        <tr class="grand"><td>Grand Total</td><td class="text-end">{{ showAmount($order->total_price) }}</td></tr>
    </table>

    <table style="margin-top: 36px;">
        <tr>
            <td style="width: 65%; vertical-align: bottom;">
                <strong>Terms & Conditions</strong>
                <div class="muted">This invoice is generated for wallet-based product purchase. Please contact support for any billing or delivery related query.</div>
                <div class="muted">This is a computer-generated invoice and does not require a signature.</div>
            </td>
            <td style="width: 35%; vertical-align: bottom;">
                <div class="signature">
                    <span class="signature-mark">Oriva</span><br>
                    Authorized Signatory
                </div>
            </td>
        </tr>
    </table>
</body>

</html>

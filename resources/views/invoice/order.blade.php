<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>{{ $pageTitle }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #172033;
            background: #eef2f7;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 13px;
            line-height: 1.55;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 18px auto;
            background: #fff;
            padding: 22mm 18mm;
            box-shadow: 0 18px 45px rgba(15, 23, 42, .12);
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            border-bottom: 3px solid #0f766e;
            padding-bottom: 18px;
        }

        .brand {
            display: flex;
            gap: 14px;
            align-items: flex-start;
        }

        .logo {
            width: 86px;
            max-height: 62px;
            object-fit: contain;
        }

        .company-name {
            margin: 0 0 5px;
            font-size: 22px;
            color: #0f766e;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .muted {
            color: #667085;
        }

        .invoice-title {
            text-align: right;
        }

        .invoice-title h1 {
            margin: 0;
            font-size: 30px;
            color: #111827;
            letter-spacing: 1.6px;
        }

        .badge {
            display: inline-block;
            margin-top: 8px;
            padding: 4px 10px;
            border-radius: 999px;
            background: #ecfdf5;
            color: #047857;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 11px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-top: 22px;
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 15px;
            background: #fff;
        }

        .card h3 {
            margin: 0 0 10px;
            font-size: 13px;
            color: #0f766e;
            text-transform: uppercase;
            letter-spacing: .7px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 4px 0;
        }

        .info-row strong {
            color: #111827;
            text-align: right;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 22px;
        }

        th {
            background: #0f766e;
            color: #fff;
            padding: 10px;
            text-align: left;
            font-size: 12px;
        }

        td {
            border-bottom: 1px solid #e5e7eb;
            padding: 10px;
            vertical-align: top;
        }

        .text-end {
            text-align: right;
        }

        .totals {
            width: 330px;
            margin-left: auto;
            margin-top: 18px;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            overflow: hidden;
        }

        .totals .line {
            display: flex;
            justify-content: space-between;
            padding: 9px 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .totals .line:last-child {
            border-bottom: 0;
            background: #0f766e;
            color: #fff;
            font-weight: 800;
            font-size: 15px;
        }

        .footer {
            display: grid;
            grid-template-columns: 1.4fr .8fr;
            gap: 20px;
            margin-top: 34px;
            align-items: end;
        }

        .signature {
            text-align: center;
            border-top: 1px solid #111827;
            padding-top: 8px;
            font-weight: 700;
        }

        .signature-mark {
            display: inline-block;
            margin-bottom: 8px;
            color: #0f766e;
            font-family: "Brush Script MT", cursive;
            font-size: 28px;
            font-weight: 400;
            line-height: 1;
            transform: rotate(-4deg);
        }

        .actions {
            width: 210mm;
            margin: 18px auto;
            text-align: right;
        }

        .print-btn {
            border: 0;
            border-radius: 8px;
            background: #0f766e;
            color: #fff;
            padding: 10px 16px;
            cursor: pointer;
            font-weight: 700;
        }

        @media print {
            @page {
                size: A4;
                margin: 8mm;
            }

            body {
                background: #fff;
                font-size: 11px;
                line-height: 1.3;
            }

            .page {
                margin: 0;
                box-shadow: none;
                width: auto;
                min-height: auto;
                padding: 0;
            }

            .actions {
                display: none;
            }

            .topbar {
                padding-bottom: 10px;
                gap: 12px;
            }

            .logo {
                width: 70px;
                max-height: 48px;
            }

            .company-name {
                font-size: 18px;
                margin-bottom: 2px;
            }

            .invoice-title h1 {
                font-size: 24px;
            }

            .grid {
                gap: 10px;
                margin-top: 12px;
            }

            .card {
                border-radius: 8px;
                padding: 9px;
            }

            .card h3 {
                margin-bottom: 5px;
                font-size: 11px;
            }

            .info-row {
                padding: 2px 0;
            }

            table {
                margin-top: 12px;
            }

            th,
            td {
                padding: 6px;
            }

            .totals {
                width: 285px;
                margin-top: 10px;
                border-radius: 8px;
            }

            .totals .line {
                padding: 6px 9px;
            }

            .footer {
                margin-top: 16px;
                gap: 12px;
            }

            .footer p {
                margin: 4px 0;
            }
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

    @if (empty($isPdf))
    <div class="actions">
        @isset($downloadUrl)
            <a class="print-btn" href="{{ $downloadUrl }}">Download PDF</a>
        @endisset
        <button class="print-btn" onclick="window.print()">Print / Save PDF</button>
    </div>
    @endif

    <main class="page">
        <section class="topbar">
            <div class="brand">
                <img class="logo" src="{{ $logoUrl }}" alt="Logo">
                <div>
                    <h2 class="company-name">{{ __($companyName) }}</h2>
                    <div>{{ __($companyAddress) }}</div>
                    <div class="muted">Mobile: {{ __($companyMobile) }} | Email: {{ __($companyEmail) }}</div>
                    <div class="muted">Website: {{ $companyWebsite }}</div>
                    <div class="muted">GSTIN: {{ __($companyGstin) }} | PAN: {{ __($companyPan) }}</div>
                </div>
            </div>
            <div class="invoice-title">
                <h1>TAX INVOICE</h1>
                <div class="badge">{{ __($statusText) }}</div>
            </div>
        </section>

        <section class="grid">
            <div class="card">
                <h3>Invoice Details</h3>
                <div class="info-row"><span>Invoice No</span><strong>{{ $invoiceNo }}</strong></div>
                <div class="info-row"><span>Invoice Date</span><strong>{{ showDateTime($order->created_at, 'd M Y, h:i A') }}</strong></div>
                <div class="info-row"><span>Order ID</span><strong>#{{ $order->id }}</strong></div>
                <div class="info-row"><span>Transaction ID</span><strong>{{ $order->trx }}</strong></div>
                <div class="info-row"><span>Payment Mode</span><strong>Wallet</strong></div>
            </div>
            <div class="card">
                <h3>Bill To / Ship To</h3>
                <div><strong>{{ __($customerName) }}</strong></div>
                <div class="muted">{{ '@' . $customerUsername }} | {{ $customerEmail }}</div>
                <div>{{ __($customerMobile) }}</div>
                <div>{{ __($order->delivery_address ?: 'N/A') }}</div>
                <div>{{ __($order->delivery_city) }}, {{ __($order->delivery_state) }} - {{ __($order->delivery_zip) }}</div>
                <div>{{ __($order->delivery_country) }}</div>
            </div>
        </section>

        <table>
            <thead>
                <tr>
                    <th style="width: 46px;">#</th>
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
                            {{ getAmount($order->gst_percent) }}%<br>
                            <span class="muted">{{ __(ucfirst($order->gst_type)) }}</span><br>
                            {{ showAmount($gstAmount) }}
                        @else
                            GST Not Applicable<br>{{ showAmount(0) }}
                        @endif
                    </td>
                    <td class="text-end">{{ showAmount($order->total_price) }}</td>
                </tr>
            </tbody>
        </table>

        <section class="totals">
            <div class="line"><span>Subtotal</span><strong>{{ showAmount($subtotal) }}</strong></div>
            <div class="line"><span>GST Amount</span><strong>{{ showAmount($gstAmount) }}</strong></div>
            <div class="line"><span>Grand Total</span><strong>{{ showAmount($order->total_price) }}</strong></div>
        </section>

        <section class="footer">
            <div>
                <strong>Terms & Conditions</strong>
                <p class="muted">
                    This invoice is generated for wallet-based product purchase. Please contact support for any billing or delivery related query.
                </p>
                <p class="muted">This is a computer-generated invoice and does not require a signature.</p>
            </div>
            <div class="signature">
                <span class="signature-mark">Oriva</span><br>
                Authorized Signatory
            </div>
        </section>
    </main>
</body>

</html>

@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="card custom--card p-0 p-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="custom--table table">
                    <thead>
                        <tr>
                            <th>@lang('Product')</th>
                            <th>@lang('Quantity')</th>
                            <th>@lang('Price')</th>
                            <th>@lang('GST')</th>
                            <th>@lang('Total Price')</th>
                            <th>@lang('Delivery Address')</th>
                            <th>@lang('Status')</th>
                            <th>@lang('Action')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td>
                                    @if (@$order->product)
                                        <a href="{{ route('product.details', ['id' => @$order->product->id, 'slug' => slug($order->product->name)]) }}">
                                            {{ __(strLimit($order->product->name, '30')) }}</a>
                                    @endif
                                </td>
                                <td>{{ $order->quantity }}</td>
                                <td>{{ showAmount($order->price) }}</td>
                                <td>
                                    @if ($order->gst_status)
                                        {{ getAmount($order->gst_percent) }}%<br>
                                        <small>{{ showAmount($order->gst_amount) }} {{ __(ucfirst($order->gst_type)) }}</small>
                                    @else
                                        <span class="text-muted">@lang('N/A')</span>
                                    @endif
                                </td>
                                <td>{{ showAmount($order->total_price) }}</td>
                                <td>
                                    @if ($order->delivery_address)
                                        <span class="fw-bold">{{ __($order->delivery_name) }}</span><br>
                                        <span>{{ __($order->delivery_mobile) }}</span><br>
                                        <small>{{ __($order->delivery_address) }}, {{ __($order->delivery_city) }}, {{ __($order->delivery_state) }} - {{ __($order->delivery_zip) }}, {{ __($order->delivery_country) }}</small>
                                    @else
                                        <span class="text-muted">@lang('Not provided')</span>
                                    @endif
                                </td>
                                <td>
                                    @php echo $order->statusOrderBadge @endphp
                                </td>
                                <td>
                                    <a class="btn btn--base btn-sm" href="{{ route('user.orders.invoice', $order->id) }}" target="_blank">
                                        @lang('Invoice')
                                    </a>
                                    <a class="btn btn--dark btn-sm mt-1" href="{{ route('user.orders.invoice.download', $order->id) }}">
                                        @lang('PDF')
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center" colspan="100%">@lang('No order found')</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @if ($orders->hasPages())
        <div class="mt-4">
            {{ paginateLinks($orders) }}
        </div>
    @endif
@endsection

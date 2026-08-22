@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--lg table-responsive">
                        <table class="table--light style--two table">
                            <thead>
                                <tr>
                                    <th>@lang('User')</th>
                                    <th>@lang('Trx')</th>
                                    <th>@lang('Price')</th>
                                    <th>@lang('GST')</th>
                                    <th>@lang('Total Price')</th>
                                    <th>@lang('Quantity')</th>
                                    <th>@lang('Delivery')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orders as $order)
                                    <tr>

                                        <td>
                                            <span class="fw-bold">{{ $order->user->fullname ?? __('Deleted User') }}</span>
                                            <br>
                                            <span class="small">
                                                @if ($order->user)
                                                    <a href="{{ route('admin.users.detail', $order->user_id) }}"><span>@</span>{{ $order->user->username }}</a>
                                                @else
                                                    <span class="text-muted">@lang('N/A')</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td>{{ '#' . $order->trx }}</td>

                                        <td>{{ showAmount($order->price) }} </td>
                                        <td>
                                            @if ($order->gst_status)
                                                {{ getAmount($order->gst_percent) }}%<br>
                                                <small>{{ showAmount($order->gst_amount) }} {{ __(ucfirst($order->gst_type)) }}</small>
                                            @else
                                                <span class="text-muted">@lang('N/A')</span>
                                            @endif
                                        </td>
                                        <td>{{ showAmount($order->total_price) }}</td>
                                        <td>{{ $order->quantity }}</td>
                                        <td>
                                            @if ($order->delivery_address)
                                                <span class="fw-bold">{{ __($order->delivery_name) }}</span><br>
                                                <span>{{ __($order->delivery_mobile) }}</span>
                                            @else
                                                <span class="text-muted">@lang('Not provided')</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php echo $order->statusOrderBadge @endphp
                                        </td>
                                        <td>
                                            <div class="button--group">
                                                <button class="btn btn-outline--primary btn-sm orderBtn"
                                                    data-action="{{ route('admin.order.status', $order->id) }}"
                                                    @if ($order->status != 0) disabled @endif>@lang('Order Status')</button>
                                                <button class="btn btn-sm btn-outline--success orderDetailsBtn" data-order='@json($order)'
                                                    data-date="{{ showDateTime($order->created_at) }}" data-status="{{ $order->statusOrderBadge }}"><i
                                                        class="las la-desktop"></i>@lang('Details')</button>
                                                <a class="btn btn-sm btn-outline--info" href="{{ route('admin.order.invoice', $order->id) }}" target="_blank">
                                                    <i class="las la-file-invoice"></i>@lang('Invoice')
                                                </a>
                                                <a class="btn btn-sm btn-outline--dark" href="{{ route('admin.order.invoice.download', $order->id) }}">
                                                    <i class="las la-file-pdf"></i>@lang('PDF')
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($orders->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($orders) }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="modal fade" id="orderStatusModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Update Order Status')</h5>
                    <button class="close" data-bs-dismiss="modal" type="button" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form method="post">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>@lang('Order Status')</label>
                            <select class="form-control select2" name="status" data-minimum-results-for-search="-1">
                                <option value="1">@lang('Shipped')</option>
                                <option value="2">@lang('Cancel')</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn--dark" data-bs-dismiss="modal" type="button">@lang('Cancel')</button>
                        <button class="btn btn--primary" type="submit">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="orderDetailsModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Order Details')</h5>
                    <button class="close" data-bs-dismiss="modal" type="button" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <b>@lang('Product')</b> <a class="product" href=""></a>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <b>@lang('Quantity') </b> <span class="quantity"></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <b>@lang('Price') </b> <span class="price"></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <b>@lang('Subtotal') </b> <span class="subtotal"></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <b>@lang('GST') </b> <span class="gst"></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <b>@lang('Total Price') </b> <span class="total-price"></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <b>@lang('Username')</b> <span class="username"></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <b>@lang('Transition No')</b> <span class="trx"></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <b>@lang('Order Date') </b> <span class="date"></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <b>@lang('Status') </b> <span class="status"></span>
                        </li>
                        <li class="list-group-item">
                            <b>@lang('Delivery Address')</b>
                            <div class="delivery-address mt-2 text-muted"></div>
                        </li>

                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <x-search-form />
@endpush

@push('script')
    <script>
        (function($) {
            "use strict";

            $('.orderBtn').on('click', function() {
                var modal = $('#orderStatusModal');
                modal.find('form').attr('action', $(this).data('action'));
                modal.modal('show');
            });

            $('.orderDetailsBtn').on('click', function() {
                var modal = $('#orderDetailsModal');
                var order = $(this).data('order');
                var date = $(this).data('date');
                var status = $(this).data('status');
                var curSym = `{{ gs('cur_sym') }}`;
                var escapeHtml = function(value) {
                    return $('<div>').text(value || '').html();
                };
                var price = curSym + parseFloat(order.price).toFixed(2);
                var subtotal = curSym + parseFloat(order.subtotal || (order.price * order.quantity)).toFixed(2);
                var gst = order.gst_status ? `${parseFloat(order.gst_percent || 0).toFixed(2)}% (${curSym}${parseFloat(order.gst_amount || 0).toFixed(2)}) ${order.gst_type || ''}` : `@lang('N/A')`;
                var totalPrice = curSym + parseFloat(order.total_price).toFixed(2);
                var url = (`{{ route('admin.product.edit', ':id') }}`).replace(":id", order.product_id);
                modal.find('.username').text(order.user ? order.user.username : `@lang('N/A')`);
                modal.find('.trx').text(order.trx);
                modal.find('.product').text(order.product ? order.product.name : `@lang('Product not found')`);
                modal.find('.product').attr('href', order.product ? url : '#');
                modal.find('.quantity').text(order.quantity);
                modal.find('.quantity').text(order.quantity);
                modal.find('.price').text(price);
                modal.find('.subtotal').text(subtotal);
                modal.find('.gst').text(gst);
                modal.find('.total-price').text(totalPrice);
                modal.find('.status').html(status);
                modal.find('.date').html(date);
                if (order.delivery_address) {
                    modal.find('.delivery-address').html(
                        `<strong>${escapeHtml(order.delivery_name)}</strong><br>${escapeHtml(order.delivery_mobile)}<br>${escapeHtml(order.delivery_address)}, ${escapeHtml(order.delivery_city)}, ${escapeHtml(order.delivery_state)} - ${escapeHtml(order.delivery_zip)}, ${escapeHtml(order.delivery_country)}`
                    );
                } else {
                    modal.find('.delivery-address').text(`@lang('Not provided')`);
                }
                modal.modal('show');
            });
        })(jQuery);
    </script>
@endpush

@extends('admin.layouts.app')

@section('panel')
    <div class="card">
        <div class="tree-wrapper">
            <div class="tree-layout">
                <div class="row justify-content-center llll text-center">
                    <div class="w-1">
                        @php echo showSingleUserinTree($tree['a']); @endphp
                    </div>
                </div>
                <div class="row justify-content-center llll text-center">
                    <div class="w-2">
                        @php echo showSingleUserinTree($tree['b']); @endphp
                    </div>
                    <div class="w-2">
                        @php echo showSingleUserinTree($tree['c']); @endphp
                    </div>
                </div>
                <div class="row justify-content-center llll text-center">
                    <div class="w-4">
                        @php echo showSingleUserinTree($tree['d']); @endphp
                    </div>
                    <div class="w-4">
                        @php echo showSingleUserinTree($tree['e']); @endphp
                    </div>
                    <div class="w-4">
                        @php echo showSingleUserinTree($tree['f']); @endphp
                    </div>
                    <div class="w-4">
                        @php echo showSingleUserinTree($tree['g']); @endphp
                    </div>
                </div>
                <div class="row justify-content-center llll text-center">
                    <div class="w-8">
                        @php echo showSingleUserinTree($tree['h']); @endphp
                    </div>
                    <div class="w-8">
                        @php echo showSingleUserinTree($tree['i']); @endphp
                    </div>
                    <div class="w-8">
                        @php echo showSingleUserinTree($tree['j']); @endphp
                    </div>
                    <div class="w-8">
                        @php echo showSingleUserinTree($tree['k']); @endphp
                    </div>
                    <div class="w-8">
                        @php echo showSingleUserinTree($tree['l']); @endphp
                    </div>
                    <div class="w-8">
                        @php echo showSingleUserinTree($tree['m']); @endphp
                    </div>
                    <div class="w-8">
                        @php echo showSingleUserinTree($tree['n']); @endphp
                    </div>
                    <div class="w-8">
                        @php echo showSingleUserinTree($tree['o']); @endphp
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade user-details-modal-area" id="exampleModalCenter" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalCenterTitle">@lang('User Details')</h5>
                    <button class="close" data-bs-dismiss="modal" type="button" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="user-details-modal">
                        <div class="user-details-header">
                            <div class="thumb"><img class="w-h-100-p tree_image" src="#" alt="*"></div>
                            <div class="content">
                                <a class="user-name tree_url tree_name" href=""></a>
                                <span class="user-status tree_status"></span>
                                <span class="user-status tree_plan"></span>
                            </div>
                        </div>
                        <div class="user-details-body text-center">
                            <h6 class="my-3">@lang('Referred By'): <span class="tree_ref"></span></h6>
                            <table class="table-bordered table">
                                <tr>
                                    <th>&nbsp;</th>
                                    <th>@lang('LEFT')</th>
                                    <th>@lang('RIGHT')</th>
                                </tr>
                                <tr>
                                    <td>@lang('Current BV')</td>
                                    <td><span class="lbv"></span></td>
                                    <td><span class="rbv"></span></td>
                                </tr>
                                <tr>
                                    <td>@lang('Free Member')</td>
                                    <td><span class="lfree"></span></td>
                                    <td><span class="rfree"></span></td>
                                </tr>
                                <tr>
                                    <td>@lang('Paid Member')</td>
                                    <td><span class="lpaid"></span></td>
                                    <td><span class="rpaid"></span></td>
                                </tr>
                            </table>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('script')
    <script>
        'use strict';
        (function($) {
            const clickDelay = 250;
            let clickTimer = null;
            let lastTap = 0;

            function openTree($node) {
                const treeUrl = $node.data('treeurl');

                if (treeUrl) {
                    window.location.href = treeUrl;
                }
            }

            function openModal($node) {
                $('.tree_name').text($node.data('name'));
                $('.tree_url').attr({
                    "href": $node.data('treeurl')
                });
                $('.tree_status').text($node.data('status'));
                $('.tree_plan').text($node.data('plan'));
                $('.tree_image').attr({
                    "src": $node.data('image')
                });
                $('.user-details-header').removeClass('Paid Free').addClass($node.data('status'));
                $('.tree_ref').text($node.data('refby'));
                $('.lbv').text($node.data('lbv'));
                $('.rbv').text($node.data('rbv'));
                $('.lpaid').text($node.data('lpaid'));
                $('.rpaid').text($node.data('rpaid'));
                $('.lfree').text($node.data('lfree'));
                $('.rfree').text($node.data('rfree'));
                $('#exampleModalCenter').modal('show');
            }

            $('.tree-node-link').on('click touchend', function(e) {
                if (e.type === 'touchend') {
                    e.preventDefault();
                }

                const $node = $(this);
                const now = Date.now();

                if (clickTimer) {
                    clearTimeout(clickTimer);
                    clickTimer = null;
                }

                if (now - lastTap < clickDelay) {
                    lastTap = 0;
                    openModal($node);
                    return;
                }

                lastTap = now;
                clickTimer = setTimeout(function() {
                    openTree($node);
                    clickTimer = null;
                    lastTap = 0;
                }, clickDelay);
            });
        })(jQuery)
    </script>
@endpush
@push('breadcrumb-plugins')
    <form class="form-inline bg--white float-right" action="{{ route('admin.users.other.tree.search') }}" method="GET">
        <div class="input-group flex-fill w-auto">
            <input class="form-control" name="username" type="text" placeholder="@lang('Search by username')">
            <button class="btn btn--primary" type="submit"><i class="fa fa-search"></i></button>
        </div>
    </form>
@endpush

@push('style')
    <link href="{{ asset('assets/global/css/tree.css') }}" rel="stylesheet">
    <style>
        .user.tree-node-link {
            cursor: pointer;
            position: relative;
        }
    </style>
@endpush

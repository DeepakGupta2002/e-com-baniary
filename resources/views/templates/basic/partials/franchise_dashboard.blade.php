<section class="user-dashboard padding-top padding-bottom">
    <div class="container">
        <div class="row">
            <div class="col-lg-3">
                <div class="dashboard-sidebar">
                    <div class="close-dashboard d-lg-none">
                        <i class="las la-times"></i>
                    </div>
                    <div class="dashboard-user">
                        <div class="user-thumb">
                            <img id="output" src="{{ getImage('assets/images/user/profile/'. auth()->user()->image, '350x300', true) }}" alt="franchise-dashboard">
                        </div>
                        <div class="user-content">
                            <span>@lang('Franchise Panel')</span>
                            <h5 class="name">{{ auth()->user()->fullname }}</h5>
                        </div>
                    </div>
                    <ul class="user-dashboard-tab">
                        <li>
                            <a class="{{ menuActive('user.franchise.dashboard') }}" href="{{ route('user.franchise.dashboard') }}">@lang('Dashboard')</a>
                        </li>
                        <li>
                            <a href="{{ route('user.home') }}">@lang('Switch To MLM Panel')</a>
                        </li>
                        <li>
                            <a class="{{ menuActive('user.franchise.transactions') }}" href="{{ route('user.franchise.transactions') }}">@lang('Transactions')</a>
                        </li>
                        <li>
                            <a class="{{ menuActive('user.franchise.transfer*') }}" href="{{ route('user.franchise.transfer') }}">@lang('Wallet Transfer')</a>
                        </li>
                        {{-- <li>
                            <a class="{{ menuActive('user.franchise.invoices') }}" href="{{ route('user.franchise.invoices') }}">@lang('Invoices')</a>
                        </li> --}}
                        <li>
                            <a href="{{ route('user.logout') }}">@lang('Sign Out')</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-9">
                <div class="user-toggler-wrapper d-flex d-lg-none">
                    <h4 class="title">{{ __($pageTitle) }}</h4>
                    <div class="user-toggler">
                        <i class="las la-sliders-h"></i>
                    </div>
                </div>
                @yield('content')
            </div>
        </div>
    </div>
</section>

@push('style')
    <style>
        .dashboard-sidebar .user-dashboard-tab li a {
            white-space: normal;
            overflow-wrap: anywhere;
        }

        @media (max-width: 991px) {
            .user-dashboard .container {
                max-width: 100%;
            }

            .user-toggler-wrapper {
                margin-bottom: 18px;
            }
        }
    </style>
@endpush

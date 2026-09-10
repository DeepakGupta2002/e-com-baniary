@extends($activeTemplate . 'layouts.frontend')

@section('content')
    <section class="padding-top padding-bottom">
        <div class="container">
            <div class="section-header text-center">
                <span class="subtitle">@lang('Franchise Module')</span>
                <h2 class="title">@lang('Choose Your ORIVA Franchise Plan')</h2>
            </div>

            <div class="row gy-4 justify-content-center">
                @forelse($plans as $plan)
                    <div class="col-md-6 col-lg-4">
                        <div class="card custom--card h-100">
                            <div class="card-body text-center">
                                <h4 class="mb-3">{{ __($plan->name) }}</h4>
                                <h2 class="text--base mb-2">{{ showAmount($plan->amount) }}</h2>
                                <p class="mb-3">@lang('Plan commission on approval'): <strong>{{ getAmount($plan->direct_commission) }}%</strong></p>
                                @auth
                                    <a href="{{ route('user.franchise.apply', ['plan' => $plan->id]) }}" class="btn btn--base w-100">@lang('Apply Now')</a>
                                @else
                                    <a href="{{ route('user.login') }}" class="btn btn--base w-100">@lang('Login To Apply')</a>
                                @endauth
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-lg-8">
                        <div class="card custom--card">
                            <div class="card-body text-center">
                                <h5 class="mb-0">@lang('No franchise plan is available right now.')</h5>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
@endsection

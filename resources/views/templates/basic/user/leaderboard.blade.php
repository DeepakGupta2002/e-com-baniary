@extends($activeTemplate . 'layouts.master')

@section('content')
    <div class="leaderboard-shell">
        <div class="leaderboard-head">
            <div>
                <h4 class="leaderboard-title">@lang('Leaderboard')</h4>
                <p class="leaderboard-subtitle">@lang('Track top performers by team BV and rank progress.')</p>
            </div>
            <div class="leaderboard-self">
                <span>@lang('Your Position')</span>
                <strong>{{ $userPosition ? '#' . $userPosition : 'N/A' }}</strong>
            </div>
        </div>

        @if ($topLeaders->count())
            <div class="leaderboard-podium">
                @foreach ($topLeaders as $topLeader)
                    @php
                        $position = $loop->iteration;
                        $positionClass = match ($position) {
                            1 => 'first',
                            2 => 'second',
                            default => 'third',
                        };
                    @endphp
                    <div class="podium-item {{ $positionClass }}">
                        <div class="podium-position">#{{ $position }}</div>
                        <div class="podium-avatar">{{ strtoupper(substr($topLeader->username, 0, 1)) }}</div>
                        <div class="podium-meta">
                            <h6>{{ $topLeader->username }}</h6>
                            <span>{{ getCurrentRankName($topLeader) }}</span>
                        </div>
                        <div class="podium-dp">{{ getAmount($topLeader->total_team_dp) }} @lang('BV')</div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="leaderboard-toolbar">
            <div class="leaderboard-search">
                <i class="las la-search"></i>
                <input type="search" id="leaderboardSearch" placeholder="@lang('Search username or rank')">
            </div>
            <div class="leaderboard-filters" id="leaderboardFilters">
                <button class="active" type="button" data-rank-filter="all">@lang('All')</button>
                @foreach ($activeRanks as $rank)
                    <button type="button" data-rank-filter="{{ strtolower($rank->name) }}">{{ __($rank->name) }}</button>
                @endforeach
            </div>
        </div>

        <div class="card custom--card leaderboard-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table--responsive--md leaderboard-table">
                        <thead>
                            <tr>
                                <th>@lang('Position')</th>
                                <th>@lang('Member')</th>
                                <th>@lang('Current Rank')</th>
                                <th>@lang('Total Team BV')</th>
                                <th>@lang('Progress')</th>
                            </tr>
                        </thead>
                        <tbody id="leaderboardRows">
                            @forelse($leaders as $leader)
                                @php
                                    $currentRank = getCurrentRankName($leader);
                                    $nextRank = $activeRanks->first(fn ($rank) => (float) $rank->required_team_dp > (float) $leader->total_team_dp);
                                    $previousRankDp = optional($activeRanks->filter(fn ($rank) => (float) $rank->required_team_dp <= (float) $leader->total_team_dp)->last())->required_team_dp ?? 0;
                                    $nextRequiredDp = $nextRank?->required_team_dp ?? max((float) $leader->total_team_dp, 1);
                                    $progressBase = max((float) $nextRequiredDp - (float) $previousRankDp, 1);
                                    $progressValue = min(100, max(0, (((float) $leader->total_team_dp - (float) $previousRankDp) / $progressBase) * 100));
                                    if (!$nextRank) {
                                        $progressValue = 100;
                                    }
                                @endphp
                                <tr class="{{ $leader->id == auth()->id() ? 'is-self' : '' }}"
                                    data-username="{{ strtolower($leader->username) }}"
                                    data-rank="{{ strtolower($currentRank) }}">
                                    <td>
                                        <span class="rank-number">#{{ $leaders->firstItem() + $loop->index }}</span>
                                    </td>
                                    <td>
                                        <div class="leader-member">
                                            <span>{{ strtoupper(substr($leader->username, 0, 1)) }}</span>
                                            <div>
                                                <strong>{{ $leader->username }}</strong>
                                                @if ($leader->id == auth()->id())
                                                    <small>@lang('You')</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge badge--primary">{{ $currentRank }}</span></td>
                                    <td>{{ getAmount($leader->total_team_dp) }}</td>
                                    <td>
                                        <div class="leader-progress">
                                            <div class="leader-progress-bar" style="width: {{ getAmount($progressValue) }}%"></div>
                                        </div>
                                        <small>
                                            @if ($nextRank)
                                                {{ getAmount(max(0, (float) $nextRank->required_team_dp - (float) $leader->total_team_dp)) }} @lang('BV to') {{ __($nextRank->name) }}
                                            @else
                                                @lang('Top rank achieved')
                                            @endif
                                        </small>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="100%" class="text-center text-muted">@lang('No data found')</td>
                                </tr>
                            @endforelse
                            <tr class="leaderboard-empty d-none">
                                <td colspan="100%" class="text-center text-muted">@lang('No matching member found')</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($leaders->hasPages())
                <div class="card-footer">
                    {{ paginateLinks($leaders) }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('style')
    <style>
        .leaderboard-shell {
            display: grid;
            gap: 18px;
        }

        .leaderboard-head,
        .leaderboard-toolbar,
        .leaderboard-podium,
        .podium-item,
        .leader-member {
            display: flex;
        }

        .leaderboard-head {
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .leaderboard-title {
            margin: 0;
            font-size: 24px;
        }

        .leaderboard-subtitle {
            margin: 5px 0 0;
            color: #6b7280;
        }

        .leaderboard-self {
            min-width: 150px;
            padding: 12px 16px;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            text-align: right;
            background: #ffffff;
        }

        .leaderboard-self span,
        .leaderboard-self strong {
            display: block;
        }

        .leaderboard-self span {
            color: #6b7280;
            font-size: 13px;
        }

        .leaderboard-self strong {
            color: #111827;
            font-size: 22px;
        }

        .leaderboard-podium {
            gap: 14px;
            align-items: stretch;
        }

        .podium-item {
            flex: 1;
            min-width: 0;
            align-items: center;
            gap: 12px;
            padding: 16px;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            background: #ffffff;
        }

        .podium-item.first {
            border-color: rgba(32, 201, 151, 0.35);
            box-shadow: 0 10px 30px rgba(32, 201, 151, 0.12);
        }

        .podium-position,
        .podium-avatar,
        .leader-member span {
            display: grid;
            place-items: center;
            flex: 0 0 auto;
        }

        .podium-position {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            color: #ffffff;
            background: #111827;
            font-weight: 700;
        }

        .podium-avatar,
        .leader-member span {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            color: #ffffff;
            background: #2563eb;
            font-weight: 700;
        }

        .podium-meta {
            min-width: 0;
            flex: 1;
        }

        .podium-meta h6 {
            margin: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .podium-meta span,
        .podium-dp,
        .leader-member small,
        .leader-progress + small {
            color: #6b7280;
            font-size: 13px;
        }

        .podium-dp {
            font-weight: 600;
            white-space: nowrap;
        }

        .leaderboard-toolbar {
            align-items: center;
            justify-content: space-between;
            gap: 14px;
        }

        .leaderboard-search {
            position: relative;
            flex: 1 1 260px;
        }

        .leaderboard-search i {
            position: absolute;
            top: 50%;
            left: 14px;
            transform: translateY(-50%);
            color: #6b7280;
        }

        .leaderboard-search input {
            width: 100%;
            height: 44px;
            padding: 0 14px 0 40px;
            border: 1px solid rgba(0, 0, 0, 0.12);
            border-radius: 8px;
            background: #ffffff;
            outline: none;
        }

        .leaderboard-search input:focus {
            border-color: #2563eb;
        }

        .leaderboard-filters {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 2px;
        }

        .leaderboard-filters button {
            flex: 0 0 auto;
            height: 38px;
            padding: 0 14px;
            border: 1px solid rgba(0, 0, 0, 0.12);
            border-radius: 8px;
            background: #ffffff;
            color: #374151;
            font-weight: 600;
        }

        .leaderboard-filters button.active {
            border-color: #2563eb;
            background: #2563eb;
            color: #ffffff;
        }

        .leaderboard-card {
            overflow: hidden;
        }

        .leaderboard-table tbody tr {
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .leaderboard-table tbody tr:not(.leaderboard-empty):hover {
            background: rgba(37, 99, 235, 0.06);
        }

        .leaderboard-table tbody tr.is-self {
            background: rgba(32, 201, 151, 0.08);
        }

        .rank-number {
            font-weight: 700;
        }

        .leader-member {
            align-items: center;
            gap: 10px;
        }

        .leader-member div {
            min-width: 0;
        }

        .leader-member strong {
            display: block;
            color: #111827;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .leader-progress {
            width: min(180px, 100%);
            height: 8px;
            overflow: hidden;
            border-radius: 999px;
            background: #e5e7eb;
        }

        .leader-progress-bar {
            height: 100%;
            border-radius: inherit;
            background: #20c997;
        }

        @media (max-width: 991px) {
            .leaderboard-head,
            .leaderboard-toolbar,
            .leaderboard-podium {
                flex-direction: column;
                align-items: stretch;
            }

            .leaderboard-self {
                text-align: left;
            }
        }
    </style>
@endpush

@push('script')
    <script>
        (function($) {
            "use strict";

            const searchInput = document.getElementById('leaderboardSearch');
            const filterButtons = document.querySelectorAll('[data-rank-filter]');
            const rows = Array.from(document.querySelectorAll('#leaderboardRows tr[data-username]'));
            const emptyRow = document.querySelector('.leaderboard-empty');
            let activeRank = 'all';

            function applyLeaderboardFilters() {
                const query = (searchInput.value || '').trim().toLowerCase();
                let visibleRows = 0;

                rows.forEach((row) => {
                    const textMatches = !query || row.dataset.username.includes(query) || row.dataset.rank.includes(query);
                    const rankMatches = activeRank === 'all' || row.dataset.rank === activeRank;
                    const isVisible = textMatches && rankMatches;

                    row.classList.toggle('d-none', !isVisible);
                    if (isVisible) {
                        visibleRows++;
                    }
                });

                if (emptyRow) {
                    emptyRow.classList.toggle('d-none', visibleRows > 0);
                }
            }

            searchInput?.addEventListener('input', applyLeaderboardFilters);

            filterButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    filterButtons.forEach((item) => item.classList.remove('active'));
                    button.classList.add('active');
                    activeRank = button.dataset.rankFilter;
                    applyLeaderboardFilters();
                });
            });
        })(jQuery);
    </script>
@endpush

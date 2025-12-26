@extends('layouts.mobile')

@section('content')
    {{-- Header --}}
    <div class="mobile-header">
        <div class="mobile-header-row">
            <div class="mobile-header-left">
                <button type="button" class="mobile-header-btn" onclick="openSidebar()">
                    <img src="{{ asset('fromfigma/menu_mobile.svg') }}" alt=""
                        onerror="this.outerHTML='<svg width=24 height=24 viewBox=\'0 0 24 24\' fill=none stroke=#FF0049 stroke-width=2><line x1=3 y1=6 x2=21 y2=6></line><line x1=3 y1=12 x2=21 y2=12></line><line x1=3 y1=18 x2=21 y2=18></line></svg>'">
                </button>
                <a href="{{ route('mobile.notifications.index') }}" class="mobile-header-btn">
                    <img src="{{ asset('fromfigma/bell_mobile.svg') }}" alt=""
                        onerror="this.outerHTML='<svg width=24 height=24 viewBox=\'0 0 24 24\' fill=none stroke=#FF0049 stroke-width=2><path d=\'M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9\'></path><path d=\'M13.73 21a2 2 0 0 1-3.46 0\'></path></svg>'">
                </a>
            </div>
            <div class="mobile-header-right">
                <div class="mobile-logo">
                    <img src="{{ asset('fromfigma/jobsi_mobile.png') }}" alt="JOBSI">
                </div>
            </div>
        </div>
    </div>

    <div class="mobile-content">
        {{-- Page Title --}}
        <div class="mobile-section-title">
            <div class="mobile-section-title-left">
                <img src="{{ asset('fromfigma/cashbox.svg') }}" alt="" width="22" height="22">
                <span>{{ __('Cashbox') }}</span>
            </div>
            @can('cashbox_deposit')
                <button type="button" class="mobile-add-btn" data-bs-toggle="modal" data-bs-target="#depositModal">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#FF0049" stroke-width="2.5">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                </button>
            @endcan
        </div>

        {{-- Current Balance Card --}}
        @if ($currentPeriod)
            <div class="mobile-card mb-3" style="background: linear-gradient(135deg, #FF0049, #FF6B6B); color: #fff;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small style="opacity: 0.8;">{{ __('Current Period') }}</small>
                        <h5 class="mb-0">{{ $currentPeriod->name }}</h5>
                    </div>
                    <div class="text-end">
                        <small style="opacity: 0.8;">{{ __('Balance') }}</small>
                        <h4 class="mb-0">{{ formatCashboxCurrency($balance['received'] - $balance['sent']) }}</h4>
                    </div>
                </div>
                <div class="mt-3 d-flex justify-content-between" style="font-size: 13px;">
                    <div>
                        <span style="opacity: 0.8;">{{ __('Received') }}:</span>
                        <span class="fw-bold">{{ formatCashboxCurrency($balance['received']) }}</span>
                    </div>
                    <div>
                        <span style="opacity: 0.8;">{{ __('Distributed') }}:</span>
                        <span class="fw-bold">{{ formatCashboxCurrency($balance['sent']) }}</span>
                    </div>
                </div>
            </div>
        @endif

        {{-- Quick Actions --}}
        @if ($currentPeriod)
            <div class="mobile-card mb-3">
                <h6 class="mb-3"><i class="ti ti-bolt me-2 text-primary"></i>{{ __('Quick Actions') }}</h6>
                <div class="d-flex flex-wrap gap-2">
                    @if ($balance['received'] > $balance['sent'])
                        @can('cashbox_distribute')
                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal"
                                data-bs-target="#distributeModal">
                                <i class="ti ti-send me-1"></i>{{ __('Distribute') }}
                            </button>
                        @endcan
                        @can('cashbox_refund')
                            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                data-bs-target="#refundModal">
                                <i class="ti ti-arrow-back me-1"></i>{{ __('Refund') }}
                            </button>
                        @endcan
                    @endif
                    @can('cashbox_view_audit')
                        <a href="{{ route('cashbox.audit') }}" class="btn btn-sm btn-secondary">
                            <i class="ti ti-history me-1"></i>{{ __('Audit') }}
                        </a>
                    @endcan
                </div>
            </div>
        @endif

        {{-- Periods List --}}
        <div class="mobile-card mb-3">
            <h6 class="mb-3"><i class="ti ti-calendar me-2 text-primary"></i>{{ __('Periods') }}</h6>

            @forelse($periods as $period)
                <a href="{{ route('mobile.cashbox.show', $period->id) }}"
                    class="mobile-period-item d-block text-decoration-none">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-medium text-dark">{{ $period->name }}</div>
                            <small class="text-muted">
                                @if ($period->is_frozen)
                                    <span class="badge bg-secondary"><i
                                            class="ti ti-lock me-1"></i>{{ __('Frozen') }}</span>
                                @else
                                    <span class="badge bg-success"><i
                                            class="ti ti-circle-check me-1"></i>{{ __('Active') }}</span>
                                @endif
                            </small>
                        </div>
                        <div class="text-end">
                            @can('cashbox_view_boss')
                                <div class="fw-bold text-primary">{{ formatCashboxCurrency($period->total_deposited) }}</div>
                                <small class="text-muted">{{ __('Deposited') }}</small>
                            @endcan
                        </div>
                    </div>
                </a>
            @empty
                <div class="text-center py-4 text-muted">
                    <i class="ti ti-cash" style="font-size: 32px; opacity: 0.5;"></i>
                    <p class="small mt-2 mb-0">{{ __('No cashbox periods') }}</p>
                    <p class="small text-muted">{{ __('Periods are created automatically when depositing money') }}</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Deposit Modal --}}
    @can('cashbox_deposit')
        @if ($currentPeriod)
            <div class="modal fade" id="depositModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form id="depositForm">
                            @csrf
                            <input type="hidden" name="period_id" value="{{ $currentPeriod->id }}">
                            <div class="modal-header">
                                <h5 class="modal-title">{{ __('Deposit Money') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group mb-3">
                                    <label class="form-label">{{ __('Period') }}</label>
                                    <input type="text" class="form-control" value="{{ $currentPeriod->name }}" disabled>
                                </div>
                                <div class="form-group mb-3">
                                    <label class="form-label">{{ __('Amount') }} <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" name="amount" class="form-control" step="0.01"
                                            min="0.01" required placeholder="0.00">
                                        <span class="input-group-text">{{ getCashboxCurrencySymbol() }}</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">{{ __('Comment') }}</label>
                                    <textarea name="comment" class="form-control" rows="2" placeholder="{{ __('Optional comment...') }}"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                <button type="submit" class="btn mobile-btn-primary"
                                    id="depositSubmitBtn">{{ __('Deposit') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endcan


    {{-- Distribute Modal --}}
    @can('cashbox_distribute')
        @if ($currentPeriod && $balance['received'] > $balance['sent'])
            <div class="modal fade" id="distributeModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form id="distributeForm">
                            @csrf
                            <input type="hidden" name="period_id" value="{{ $currentPeriod->id }}">
                            <input type="hidden" name="recipient_id" id="recipient_id" value="">
                            <input type="hidden" name="recipient_type" id="recipient_type" value="">
                            <div class="modal-header">
                                <h5 class="modal-title">{{ __('Distribute Money') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group mb-3">
                                    <label class="form-label">{{ __('Distribution Type') }} <span
                                            class="text-danger">*</span></label>
                                    <select name="distribution_type" id="distributionType" class="form-control" required>
                                        <option value="">{{ __('Select distribution type') }}</option>
                                        <option value="salary">{{ __('Employee Salary') }}</option>
                                        <option value="transfer">{{ __('Fund Transfer') }}</option>
                                    </select>
                                    <small class="text-muted" id="distributionTypeHint"></small>
                                </div>
                                <div class="form-group mb-3">
                                    <label class="form-label">{{ __('Recipient') }} <span
                                            class="text-danger">*</span></label>

                                    {{-- Search Input --}}
                                    <div class="recipient-search-box mb-2">
                                        <i class="ti ti-search recipient-search-icon"></i>
                                        <input type="text" id="recipientSearchInput"
                                            class="form-control recipient-search-input"
                                            placeholder="{{ __('Search recipient') }}..." autocomplete="off">
                                    </div>

                                    {{-- Recipients List --}}
                                    <div class="recipients-list" id="recipientsList">
                                        @php
                                            $managers = collect($recipients)->filter(
                                                fn($r) => !isset($r['is_self']) && $r['role'] === 'manager',
                                            );
                                            $curators = collect($recipients)->filter(
                                                fn($r) => !isset($r['is_self']) && $r['role'] === 'curator',
                                            );
                                            $workers = collect($recipients)->filter(
                                                fn($r) => !isset($r['is_self']) && $r['role'] === 'worker',
                                            );
                                        @endphp

                                        @if ($managers->count() > 0)
                                            <div class="recipient-group" data-group="managers">
                                                <div class="recipient-group-title">
                                                    <i class="ti ti-users"></i> {{ __('Managers') }}
                                                </div>
                                                @foreach ($managers as $recipient)
                                                    <div class="recipient-card"
                                                        data-value="{{ $recipient['type'] === 'App\\Models\\Worker' ? 'worker' : 'user' }}_{{ $recipient['id'] }}"
                                                        data-name="{{ strtolower($recipient['name']) }}" data-role="manager">
                                                        <div class="recipient-avatar">
                                                            {{ strtoupper(substr($recipient['name'], 0, 1)) }}
                                                        </div>
                                                        <div class="recipient-info">
                                                            <div class="recipient-name">{{ $recipient['name'] }}</div>
                                                            <div class="recipient-role">{{ __('Manager') }}</div>
                                                        </div>
                                                        <div class="recipient-check">
                                                            <i class="ti ti-check"></i>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        @if ($curators->count() > 0)
                                            <div class="recipient-group" data-group="curators">
                                                <div class="recipient-group-title">
                                                    <i class="ti ti-users"></i> {{ __('Curators') }}
                                                </div>
                                                @foreach ($curators as $recipient)
                                                    <div class="recipient-card"
                                                        data-value="{{ $recipient['type'] === 'App\\Models\\Worker' ? 'worker' : 'user' }}_{{ $recipient['id'] }}"
                                                        data-name="{{ strtolower($recipient['name']) }}" data-role="curator">
                                                        <div class="recipient-avatar curator">
                                                            {{ strtoupper(substr($recipient['name'], 0, 1)) }}
                                                        </div>
                                                        <div class="recipient-info">
                                                            <div class="recipient-name">{{ $recipient['name'] }}</div>
                                                            <div class="recipient-role">{{ __('Curator') }}</div>
                                                        </div>
                                                        <div class="recipient-check">
                                                            <i class="ti ti-check"></i>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        @if ($workers->count() > 0)
                                            <div class="recipient-group" data-group="workers">
                                                <div class="recipient-group-title">
                                                    <i class="ti ti-users"></i> {{ __('Workers') }}
                                                </div>
                                                @foreach ($workers as $recipient)
                                                    <div class="recipient-card"
                                                        data-value="{{ $recipient['type'] === 'App\\Models\\Worker' ? 'worker' : 'user' }}_{{ $recipient['id'] }}"
                                                        data-name="{{ strtolower($recipient['name']) }}" data-role="worker">
                                                        <div class="recipient-avatar worker">
                                                            {{ strtoupper(substr($recipient['name'], 0, 1)) }}
                                                        </div>
                                                        <div class="recipient-info">
                                                            <div class="recipient-name">{{ $recipient['name'] }}</div>
                                                            <div class="recipient-role">{{ __('Worker') }}</div>
                                                        </div>
                                                        <div class="recipient-check">
                                                            <i class="ti ti-check"></i>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        {{-- No Results --}}
                                        <div class="recipients-no-results" id="recipientsNoResults" style="display: none;">
                                            <i class="ti ti-user-off"></i>
                                            <span>{{ __('No recipients found') }}</span>
                                        </div>
                                    </div>

                                    {{-- Selected Recipient Display --}}
                                    <div class="selected-recipient" id="selectedRecipient" style="display: none;">
                                        <div class="selected-recipient-info">
                                            <span class="selected-recipient-label">{{ __('Selected') }}:</span>
                                            <span class="selected-recipient-name" id="selectedRecipientName"></span>
                                        </div>
                                        <button type="button" class="selected-recipient-clear" id="clearRecipient">
                                            <i class="ti ti-x"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="form-group mb-3">
                                    <label class="form-label">{{ __('Amount') }} <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" name="amount" class="form-control" step="0.01"
                                            min="0.01" required placeholder="0.00">
                                        <span class="input-group-text">{{ getCashboxCurrencySymbol() }}</span>
                                    </div>
                                    <small class="text-muted">{{ __('Available:') }}
                                        {{ formatCashboxCurrency($balance['received'] - $balance['sent']) }}</small>
                                </div>
                                <div class="form-group mb-3">
                                    <label class="form-label">{{ __('Task') }}</label>
                                    <input type="text" name="task" class="form-control"
                                        placeholder="{{ __('Task description...') }}">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">{{ __('Comment') }}</label>
                                    <textarea name="comment" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                <button type="submit" class="btn btn-success"
                                    id="distributeSubmitBtn">{{ __('Distribute') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endcan

    {{-- Refund Modal --}}
    @can('cashbox_refund')
        @if ($currentPeriod && $refundableTransactions->count() > 0)
            <div class="modal fade" id="refundModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form id="refundForm">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">{{ __('Refund Money') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group mb-3">
                                    <label class="form-label">{{ __('Transaction to Refund') }} <span
                                            class="text-danger">*</span></label>
                                    <select name="transaction_id" id="refundTransactionId" class="form-control" required>
                                        <option value="">{{ __('Select transaction') }}</option>
                                        @foreach ($refundableTransactions as $transaction)
                                            <option value="{{ $transaction->id }}" data-amount="{{ $transaction->amount }}">
                                                #{{ $transaction->id }} | {{ formatCashboxCurrency($transaction->amount) }} |
                                                {{ $transaction->created_at->format('d.m.Y') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group mb-3">
                                    <label class="form-label">{{ __('Amount') }} <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" name="amount" class="form-control" step="0.01"
                                            min="0.01" required>
                                        <span class="input-group-text">{{ getCashboxCurrencySymbol() }}</span>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">{{ __('Refund Reason') }} <span
                                            class="text-danger">*</span></label>
                                    <textarea name="comment" class="form-control" rows="2" required></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                <button type="submit" class="btn btn-warning"
                                    id="refundSubmitBtn">{{ __('Refund') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endcan

    <style>
        .mobile-btn-primary {
            background: #FF0049 !important;
            border-color: #FF0049 !important;
            color: #fff !important;
        }

        .mobile-card {
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .mobile-period-item {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .mobile-period-item:last-child {
            border-bottom: none;
        }

        .text-primary {
            color: #FF0049 !important;
        }

        .mobile-add-btn {
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
        }

        .mobile-add-btn:focus {
            border: none !important;
            outline: none !important;
            box-shadow: none !important;
        }

        /* Recipient Search Styles */
        .recipient-search-box {
            position: relative;
        }

        .recipient-search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 16px;
            pointer-events: none;
        }

        .recipient-search-input {
            padding-left: 38px !important;
            border-radius: 10px !important;
            border: 1px solid #e0e0e0 !important;
        }

        .recipient-search-input:focus {
            border-color: #FF0049 !important;
            box-shadow: 0 0 0 2px rgba(255, 0, 73, 0.1) !important;
        }

        /* Recipients List */
        .recipients-list {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #e8e8e8;
            border-radius: 10px;
            background: #fafafa;
        }

        .recipient-group {
            padding: 0;
        }

        .recipient-group-title {
            padding: 8px 12px;
            font-size: 11px;
            font-weight: 600;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #f0f0f0;
            position: sticky;
            top: 0;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .recipient-group-title i {
            font-size: 14px;
            color: #FF0049;
        }

        .recipient-card {
            display: flex;
            align-items: center;
            padding: 10px 12px;
            cursor: pointer;
            transition: all 0.15s ease;
            border-bottom: 1px solid #f0f0f0;
            background: #fff;
        }

        .recipient-card:last-child {
            border-bottom: none;
        }

        .recipient-card:hover {
            background: #FFF5F7;
        }

        .recipient-card.selected {
            background: linear-gradient(135deg, #FFF0F4 0%, #FFE8EE 100%);
            border-left: 3px solid #FF0049;
        }

        .recipient-card.hidden {
            display: none !important;
        }

        .recipient-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FF0049, #FF6B6B);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
            flex-shrink: 0;
        }

        .recipient-avatar.curator {
            background: linear-gradient(135deg, #3B82F6, #60A5FA);
        }

        .recipient-avatar.worker {
            background: linear-gradient(135deg, #22B404, #4ADE80);
        }

        .recipient-info {
            flex: 1;
            margin-left: 10px;
            min-width: 0;
        }

        .recipient-name {
            font-size: 14px;
            font-weight: 500;
            color: #333;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .recipient-card.selected .recipient-name {
            color: #FF0049;
        }

        .recipient-role {
            font-size: 11px;
            color: #888;
        }

        .recipient-check {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 2px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            color: transparent;
            transition: all 0.15s ease;
            flex-shrink: 0;
        }

        .recipient-card.selected .recipient-check {
            background: #FF0049;
            border-color: #FF0049;
            color: #fff;
        }

        /* No Results */
        .recipients-no-results {
            padding: 20px;
            text-align: center;
            color: #999;
            font-size: 13px;
        }

        .recipients-no-results i {
            font-size: 24px;
            display: block;
            margin-bottom: 8px;
            opacity: 0.5;
        }

        /* Selected Recipient Display */
        .selected-recipient {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px;
            background: linear-gradient(135deg, #FFF0F4 0%, #FFE8EE 100%);
            border: 1px solid #FFD6E0;
            border-radius: 10px;
            margin-top: 8px;
        }

        .selected-recipient-info {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .selected-recipient-label {
            font-size: 12px;
            color: #888;
        }

        .selected-recipient-name {
            font-size: 14px;
            font-weight: 600;
            color: #FF0049;
        }

        .selected-recipient-clear {
            width: 24px;
            height: 24px;
            border: none;
            background: rgba(255, 0, 73, 0.1);
            border-radius: 50%;
            color: #FF0049;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .selected-recipient-clear:hover {
            background: #FF0049;
            color: #fff;
        }

        /* Highlight */
        .recipient-name .highlight {
            background: #FFE0E8;
            color: #FF0049;
            padding: 0 2px;
            border-radius: 2px;
        }
    </style>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Distribution type hint and recipient filtering
            var distributionTypeSelect = document.getElementById('distributionType');
            var distributionTypeHint = document.getElementById('distributionTypeHint');
            
            // Recipient search and selection
            var recipientSearchInput = document.getElementById('recipientSearchInput');
            var recipientCards = document.querySelectorAll('.recipient-card');
            var recipientGroups = document.querySelectorAll('.recipient-group');
            var noResults = document.getElementById('recipientsNoResults');
            var selectedRecipientDiv = document.getElementById('selectedRecipient');
            var selectedRecipientName = document.getElementById('selectedRecipientName');
            var clearRecipientBtn = document.getElementById('clearRecipient');

            function filterRecipients(distributionType) {
                var managersGroup = document.querySelector('.recipients-list [data-group="managers"]');
                var curatorsGroup = document.querySelector('.recipients-list [data-group="curators"]');
                var workersGroup = document.querySelector('.recipients-list [data-group="workers"]');
                
                if (!distributionType) {
                    // No action selected - show only staff (managers and curators)
                    if (managersGroup) managersGroup.style.display = '';
                    if (curatorsGroup) curatorsGroup.style.display = '';
                    if (workersGroup) workersGroup.style.display = 'none';
                } else if (distributionType === 'salary') {
                    // Salary - show only curators and workers
                    if (managersGroup) managersGroup.style.display = 'none';
                    if (curatorsGroup) curatorsGroup.style.display = '';
                    if (workersGroup) workersGroup.style.display = '';
                } else if (distributionType === 'transfer') {
                    // Transfer - show only staff (managers and curators)
                    if (managersGroup) managersGroup.style.display = '';
                    if (curatorsGroup) curatorsGroup.style.display = '';
                    if (workersGroup) workersGroup.style.display = 'none';
                }
                
                // Clear selection when filtering changes
                recipientCards.forEach(function(c) { c.classList.remove('selected'); });
                document.getElementById('recipient_type').value = '';
                document.getElementById('recipient_id').value = '';
                if (selectedRecipientDiv) selectedRecipientDiv.style.display = 'none';
                if (recipientSearchInput) { recipientSearchInput.value = ''; }
            }

            if (distributionTypeSelect) {
                distributionTypeSelect.addEventListener('change', function() {
                    var value = this.value;
                    if (value === 'salary') {
                        distributionTypeHint.textContent =
                            '{{ __('Final salary payment. Transaction will be completed immediately.') }}';
                    } else if (value === 'transfer') {
                        distributionTypeHint.textContent =
                            '{{ __('Money transfer for further distribution to other employees.') }}';
                    } else {
                        distributionTypeHint.textContent = '';
                    }
                    
                    // Filter recipients based on distribution type
                    filterRecipients(value);
                });
                
                // Initial filter on page load
                filterRecipients(distributionTypeSelect.value);
            }
            var originalNames = {};

            // Store original names
            recipientCards.forEach(function(card, index) {
                var nameEl = card.querySelector('.recipient-name');
                if (nameEl) {
                    originalNames[index] = nameEl.innerHTML;
                }
            });

            // Search functionality
            if (recipientSearchInput) {
                recipientSearchInput.addEventListener('input', function() {
                    var query = this.value.toLowerCase().trim();
                    var visibleCount = 0;

                    recipientCards.forEach(function(card, index) {
                        var name = card.dataset.name || '';
                        var nameEl = card.querySelector('.recipient-name');

                        if (query.length < 2) {
                            card.classList.remove('hidden');
                            if (nameEl && originalNames[index]) {
                                nameEl.innerHTML = originalNames[index];
                            }
                            visibleCount++;
                        } else if (name.includes(query)) {
                            card.classList.remove('hidden');
                            visibleCount++;
                            // Highlight
                            if (nameEl && originalNames[index]) {
                                var regex = new RegExp('(' + escapeRegex(query) + ')', 'gi');
                                nameEl.innerHTML = originalNames[index].replace(regex,
                                    '<span class="highlight">$1</span>');
                            }
                        } else {
                            card.classList.add('hidden');
                            if (nameEl && originalNames[index]) {
                                nameEl.innerHTML = originalNames[index];
                            }
                        }
                    });

                    // Show/hide groups based on visible cards
                    recipientGroups.forEach(function(group) {
                        var visibleInGroup = group.querySelectorAll('.recipient-card:not(.hidden)')
                            .length;
                        group.style.display = visibleInGroup > 0 ? '' : 'none';
                    });

                    // Show no results
                    if (noResults) {
                        noResults.style.display = visibleCount === 0 ? 'block' : 'none';
                    }
                });
            }

            // Card selection
            recipientCards.forEach(function(card) {
                card.addEventListener('click', function() {
                    // Remove selection from all
                    recipientCards.forEach(function(c) {
                        c.classList.remove('selected');
                    });

                    // Select this card
                    this.classList.add('selected');

                    // Update hidden fields
                    var value = this.dataset.value;
                    if (value) {
                        var parts = value.split('_');
                        document.getElementById('recipient_type').value = parts[0];
                        document.getElementById('recipient_id').value = parts[1];
                    }

                    // Show selected display
                    var name = this.querySelector('.recipient-name').textContent;
                    if (selectedRecipientDiv && selectedRecipientName) {
                        selectedRecipientName.textContent = name;
                        selectedRecipientDiv.style.display = 'flex';
                    }
                });
            });

            // Clear selection
            if (clearRecipientBtn) {
                clearRecipientBtn.addEventListener('click', function() {
                    recipientCards.forEach(function(c) {
                        c.classList.remove('selected');
                    });
                    document.getElementById('recipient_type').value = '';
                    document.getElementById('recipient_id').value = '';
                    if (selectedRecipientDiv) {
                        selectedRecipientDiv.style.display = 'none';
                    }
                    if (recipientSearchInput) {
                        recipientSearchInput.value = '';
                        recipientSearchInput.dispatchEvent(new Event('input'));
                    }
                });
            }

            function escapeRegex(string) {
                return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            }

            // Refund transaction amount auto-fill
            var refundTransactionSelect = document.getElementById('refundTransactionId');
            if (refundTransactionSelect) {
                refundTransactionSelect.addEventListener('change', function() {
                    var selected = this.options[this.selectedIndex];
                    var amount = selected.getAttribute('data-amount');
                    if (amount) {
                        document.querySelector('#refundModal input[name="amount"]').value = amount;
                    }
                });
            }

            // Deposit form
            var depositForm = document.getElementById('depositForm');
            if (depositForm) {
                depositForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    submitCashboxForm(this, '{{ route('cashbox.deposit') }}', 'depositSubmitBtn',
                        '{{ __('Deposit') }}');
                });
            }

            // Distribute form
            var distributeForm = document.getElementById('distributeForm');
            if (distributeForm) {
                distributeForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    submitCashboxForm(this, '{{ route('cashbox.distribute') }}', 'distributeSubmitBtn',
                        '{{ __('Distribute') }}');
                });
            }

            // Refund form
            var refundForm = document.getElementById('refundForm');
            if (refundForm) {
                refundForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    submitCashboxForm(this, '{{ route('cashbox.refund') }}', 'refundSubmitBtn',
                        '{{ __('Refund') }}');
                });
            }

            function submitCashboxForm(form, url, btnId, btnText) {
                var submitBtn = document.getElementById(btnId);
                submitBtn.disabled = true;
                submitBtn.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-1"></span>{{ __('Loading...') }}';

                var formData = new FormData(form);

                fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            show_toastr('success', data.message);
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        } else {
                            show_toastr('error', data.error || '{{ __('An error occurred') }}');
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = btnText;
                        }
                    })
                    .catch(error => {
                        show_toastr('error', '{{ __('An error occurred') }}');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = btnText;
                    });
            }
        });
    </script>
@endpush

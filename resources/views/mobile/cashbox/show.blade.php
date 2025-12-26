@extends('layouts.mobile')

@section('content')
    {{-- Header --}}
    <div class="mobile-header">
        <div class="mobile-header-row">
            <div class="mobile-header-left">
                <a href="{{ route('mobile.cashbox.index') }}" class="mobile-header-btn">
                    <i class="ti ti-arrow-left" style="font-size: 24px; color: #FF0049;"></i>
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
        <div class="mobile-section-title mb-3">
            <div class="mobile-section-title-left">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#FF0049" stroke-width="2">
                    <path d="M17 8V5a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v6a1 1 0 0 0 1 1h3"></path>
                    <path d="M21 12v6a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1v-6a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1z"></path>
                    <circle cx="14" cy="15" r="2"></circle>
                </svg>
                <span>{{ $period->name }}</span>
            </div>
            @if($period->is_frozen)
                <span class="badge bg-secondary"><i class="ti ti-lock me-1"></i>{{ __('Frozen') }}</span>
            @endif
        </div>

        {{-- Balance Card --}}
        <div class="mobile-card mb-3" style="background: linear-gradient(135deg, #FF0049, #FF6B6B); color: #fff;">
            <div class="row text-center">
                <div class="col-4">
                    <small style="opacity: 0.8;">{{ __('Received') }}</small>
                    <h5 class="mb-0">{{ formatCashboxCurrency($balance['received']) }}</h5>
                </div>
                <div class="col-4">
                    <small style="opacity: 0.8;">{{ __('Distributed') }}</small>
                    <h5 class="mb-0">{{ formatCashboxCurrency($balance['sent']) }}</h5>
                </div>
                <div class="col-4">
                    <small style="opacity: 0.8;">{{ __('Remaining') }}</small>
                    <h5 class="mb-0">{{ formatCashboxCurrency($balance['received'] - $balance['sent']) }}</h5>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        @if(!$period->is_frozen)
            <div class="mobile-card mb-3">
                <h6 class="mb-3"><i class="ti ti-bolt me-2 text-primary"></i>{{ __('Actions') }}</h6>
                <div class="d-flex flex-wrap gap-2">
                    @if($canDeposit)
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#depositModal">
                            <i class="ti ti-plus me-1"></i>{{ __('Deposit') }}
                        </button>
                    @endif
                    @if($balance['received'] > $balance['sent'] && $canDistribute)
                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#distributeModal">
                            <i class="ti ti-send me-1"></i>{{ __('Distribute') }}
                        </button>
                    @endif
                    @if($balance['received'] > $balance['sent'] && $canRefund && $userRole !== 'boss')
                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#refundModal">
                            <i class="ti ti-arrow-back me-1"></i>{{ __('Refund') }}
                        </button>
                    @endif
                    @if($canSelfSalary && !$hasSelfSalaryThisPeriod && $balance['received'] > $balance['sent'])
                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#selfSalaryModal">
                            <i class="ti ti-wallet me-1"></i>{{ __('Self Salary') }}
                        </button>
                    @endif
                </div>
            </div>
        @endif

        {{-- Period Info --}}
        <div class="mobile-card mb-3">
            <h6 class="mb-3"><i class="ti ti-info-circle me-2 text-primary"></i>{{ __('Information') }}</h6>
            <div class="mobile-info-list">
                <div class="mobile-info-item">
                    <span class="mobile-info-label">{{ __('Period') }}</span>
                    <span class="mobile-info-value">{{ $period->name }}</span>
                </div>
                @if($canViewTotalDeposited)
                    <div class="mobile-info-item">
                        <span class="mobile-info-label">{{ __('Total Deposited') }}</span>
                        <span class="mobile-info-value">{{ formatCashboxCurrency($period->total_deposited) }}</span>
                    </div>
                @endif
                <div class="mobile-info-item">
                    <span class="mobile-info-label">{{ __('Status') }}</span>
                    <span class="mobile-info-value">
                        @if($period->is_frozen)
                            <span class="badge bg-secondary">{{ __('Frozen') }}</span>
                        @else
                            <span class="badge bg-success">{{ __('Active') }}</span>
                        @endif
                    </span>
                </div>
                <div class="mobile-info-item">
                    <span class="mobile-info-label">{{ __('Your Role') }}</span>
                    <span class="mobile-info-value">
                        @if($userRole === 'boss')
                            {{ __('Boss') }}
                        @elseif($userRole === 'manager')
                            {{ __('Manager') }}
                        @elseif($userRole === 'curator')
                            {{ __('Curator') }}
                        @else
                            {{ __('Worker') }}
                        @endif
                    </span>
                </div>
            </div>
        </div>

        {{-- Transactions List --}}
        <div class="mobile-card mb-3">
            <h6 class="mb-3"><i class="ti ti-list me-2 text-primary"></i>{{ __('Transactions') }}</h6>
            
            @forelse($transactions as $transaction)
                <div class="mobile-transaction-item" onclick="showTransactionDetail({{ $transaction->id }})">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                @if($transaction->type === 'deposit')
                                    <span class="badge bg-primary">{{ __('Deposit') }}</span>
                                @elseif($transaction->type === 'distribution')
                                    @if($transaction->distribution_type === 'salary')
                                        <span class="badge bg-success">{{ __('Salary') }}</span>
                                    @elseif($transaction->distribution_type === 'transfer')
                                        <span class="badge bg-info">{{ __('Transfer') }}</span>
                                    @else
                                        <span class="badge bg-success">{{ __('Distribution') }}</span>
                                    @endif
                                @elseif($transaction->type === 'refund')
                                    <span class="badge bg-warning">{{ __('Refund') }}</span>
                                @elseif($transaction->type === 'self_salary')
                                    <span class="badge bg-info">{{ __('Self Salary') }}</span>
                                @endif
                                <span class="badge bg-{{ $transaction->status === 'completed' ? 'success' : ($transaction->status === 'pending' ? 'warning' : 'info') }}">
                                    {{ $transaction->status === 'completed' ? __('Completed') : ($transaction->status === 'pending' ? __('Pending') : __('In Progress')) }}
                                </span>
                            </div>
                            <small class="text-muted">
                                @if($transaction->sender)
                                    {{ $transaction->sender->name ?? __('Unknown') }}
                                @endif
                                @if($transaction->recipient)
                                    → {{ $transaction->recipient->name ?? ($transaction->recipient->first_name ?? '') . ' ' . ($transaction->recipient->last_name ?? '') }}
                                @endif
                            </small>
                            @if($transaction->task)
                                <div class="small text-muted mt-1">
                                    <i class="ti ti-clipboard me-1"></i>{{ Str::limit($transaction->task, 30) }}
                                </div>
                            @endif
                        </div>
                        <div class="text-end">
                            <div class="fw-bold {{ $transaction->type === 'deposit' || $transaction->type === 'refund' ? 'text-success' : 'text-danger' }}">
                                {{ $transaction->type === 'deposit' || $transaction->type === 'refund' ? '+' : '-' }}{{ formatCashboxCurrency($transaction->amount) }}
                            </div>
                            <small class="text-muted">{{ $transaction->created_at->format('d.m H:i') }}</small>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-4 text-muted">
                    <i class="ti ti-receipt-off" style="font-size: 32px; opacity: 0.5;"></i>
                    <p class="small mt-2 mb-0">{{ __('No transactions in this period') }}</p>
                </div>
            @endforelse
        </div>
    </div>


    {{-- Deposit Modal --}}
    @if($canDeposit && !$period->is_frozen)
        <div class="modal fade" id="depositModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form id="depositForm">
                        @csrf
                        <input type="hidden" name="period_id" value="{{ $period->id }}">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('Deposit Money') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group mb-3">
                                <label class="form-label">{{ __('Amount') }} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required placeholder="0.00">
                                    <span class="input-group-text">{{ getCashboxCurrencySymbol() }}</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ __('Comment') }}</label>
                                <textarea name="comment" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-primary" id="depositSubmitBtn">{{ __('Deposit') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Distribute Modal --}}
    @if($canDistribute && !$period->is_frozen && $balance['received'] > $balance['sent'])
        <div class="modal fade" id="distributeModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form id="distributeForm">
                        @csrf
                        <input type="hidden" name="period_id" value="{{ $period->id }}">
                        <input type="hidden" name="recipient_id" id="recipient_id" value="">
                        <input type="hidden" name="recipient_type" id="recipient_type" value="">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('Distribute Money') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group mb-3">
                                <label class="form-label">{{ __('Distribution Type') }} <span class="text-danger">*</span></label>
                                <select name="distribution_type" id="distributionType" class="form-control" required>
                                    <option value="">{{ __('Select distribution type') }}</option>
                                    <option value="salary">{{ __('Employee Salary') }}</option>
                                    <option value="transfer">{{ __('Fund Transfer') }}</option>
                                </select>
                                <small class="text-muted" id="distributionTypeHint"></small>
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label">{{ __('Recipient') }} <span class="text-danger">*</span></label>
                                
                                {{-- Search Input --}}
                                <div class="recipient-search-box mb-2">
                                    <i class="ti ti-search recipient-search-icon"></i>
                                    <input type="text" id="recipientSearchInput" class="form-control recipient-search-input" 
                                           placeholder="{{ __('Search recipient') }}..." autocomplete="off">
                                </div>
                                
                                {{-- Recipients List --}}
                                <div class="recipients-list" id="recipientsList">
                                    @php
                                        $managers = collect($recipients)->filter(fn($r) => !isset($r['is_self']) && $r['role'] === 'manager');
                                        $curators = collect($recipients)->filter(fn($r) => !isset($r['is_self']) && $r['role'] === 'curator');
                                        $workers = collect($recipients)->filter(fn($r) => !isset($r['is_self']) && $r['role'] === 'worker');
                                    @endphp
                                    
                                    @if($managers->count() > 0)
                                        <div class="recipient-group" data-group="managers">
                                            <div class="recipient-group-title">
                                                <i class="ti ti-user"></i> {{ __('Managers') }}
                                            </div>
                                            @foreach($managers as $recipient)
                                                <div class="recipient-card" 
                                                     data-value="{{ $recipient['type'] === 'App\\Models\\Worker' ? 'worker' : 'user' }}_{{ $recipient['id'] }}"
                                                     data-name="{{ strtolower($recipient['name']) }}"
                                                     data-role="manager">
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
                                    
                                    @if($curators->count() > 0)
                                        <div class="recipient-group" data-group="curators">
                                            <div class="recipient-group-title">
                                                <i class="ti ti-user"></i> {{ __('Curators') }}
                                            </div>
                                            @foreach($curators as $recipient)
                                                <div class="recipient-card" 
                                                     data-value="{{ $recipient['type'] === 'App\\Models\\Worker' ? 'worker' : 'user' }}_{{ $recipient['id'] }}"
                                                     data-name="{{ strtolower($recipient['name']) }}"
                                                     data-role="curator">
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
                                    
                                    @if($workers->count() > 0)
                                        <div class="recipient-group" data-group="workers">
                                            <div class="recipient-group-title">
                                                <i class="ti ti-user"></i> {{ __('Workers') }}
                                            </div>
                                            @foreach($workers as $recipient)
                                                <div class="recipient-card" 
                                                     data-value="{{ $recipient['type'] === 'App\\Models\\Worker' ? 'worker' : 'user' }}_{{ $recipient['id'] }}"
                                                     data-name="{{ strtolower($recipient['name']) }}"
                                                     data-role="worker">
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
                                    <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required placeholder="0.00">
                                    <span class="input-group-text">{{ getCashboxCurrencySymbol() }}</span>
                                </div>
                                <small class="text-muted">{{ __('Available:') }} {{ formatCashboxCurrency($balance['received'] - $balance['sent']) }}</small>
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label">{{ __('Task') }}</label>
                                <input type="text" name="task" class="form-control" placeholder="{{ __('Task description...') }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ __('Comment') }}</label>
                                <textarea name="comment" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-success" id="distributeSubmitBtn">{{ __('Distribute') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Refund Modal --}}
    @if($canRefund && !$period->is_frozen && $refundableTransactions->count() > 0)
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
                                <label class="form-label">{{ __('Transaction to Refund') }} <span class="text-danger">*</span></label>
                                <select name="transaction_id" id="refundTransactionId" class="form-control" required>
                                    <option value="">{{ __('Select transaction') }}</option>
                                    @foreach($refundableTransactions as $transaction)
                                        <option value="{{ $transaction->id }}" data-amount="{{ $transaction->amount }}">
                                            #{{ $transaction->id }} | {{ formatCashboxCurrency($transaction->amount) }} | {{ $transaction->created_at->format('d.m.Y') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label">{{ __('Amount') }} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required>
                                    <span class="input-group-text">{{ getCashboxCurrencySymbol() }}</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ __('Refund Reason') }} <span class="text-danger">*</span></label>
                                <textarea name="comment" class="form-control" rows="2" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-warning" id="refundSubmitBtn">{{ __('Refund') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Self Salary Modal --}}
    @if($canSelfSalary && !$period->is_frozen && !$hasSelfSalaryThisPeriod)
        <div class="modal fade" id="selfSalaryModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form id="selfSalaryForm">
                        @csrf
                        <input type="hidden" name="period_id" value="{{ $period->id }}">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('Self Salary') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group mb-3">
                                <label class="form-label">{{ __('Amount') }} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required placeholder="0.00">
                                    <span class="input-group-text">{{ getCashboxCurrencySymbol() }}</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ __('Comment') }}</label>
                                <textarea name="comment" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-info" id="selfSalarySubmitBtn">{{ __('Receive') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Transaction Detail Modal --}}
    <div class="modal fade" id="transactionDetailModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Transaction Details') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="transactionDetailContent">
                    <div class="text-center py-3">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .mobile-card {
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .mobile-info-list {
            display: flex;
            flex-direction: column;
        }
        .mobile-info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .mobile-info-item:last-child {
            border-bottom: none;
        }
        .mobile-info-label {
            color: #666;
            font-size: 13px;
        }
        .mobile-info-value {
            font-weight: 500;
            font-size: 13px;
        }
        .mobile-transaction-item {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
        }
        .mobile-transaction-item:last-child {
            border-bottom: none;
        }
        .mobile-transaction-item:active {
            background: #f8f9fa;
        }
        .text-primary {
            color: #FF0049 !important;
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
        .recipients-list {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #e8e8e8;
            border-radius: 10px;
            background: #fafafa;
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
    var originalNames = {};

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
                distributionTypeHint.textContent = '{{ __("Final salary payment. Transaction will be completed immediately.") }}';
            } else if (value === 'transfer') {
                distributionTypeHint.textContent = '{{ __("Money transfer for further distribution to other employees.") }}';
            } else {
                distributionTypeHint.textContent = '';
            }
            
            // Filter recipients based on distribution type
            filterRecipients(value);
        });
        
        // Initial filter on page load
        filterRecipients(distributionTypeSelect.value);
    }
    
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
                    if (nameEl && originalNames[index]) {
                        var regex = new RegExp('(' + escapeRegex(query) + ')', 'gi');
                        nameEl.innerHTML = originalNames[index].replace(regex, '<span class="highlight">$1</span>');
                    }
                } else {
                    card.classList.add('hidden');
                    if (nameEl && originalNames[index]) {
                        nameEl.innerHTML = originalNames[index];
                    }
                }
            });
            
            recipientGroups.forEach(function(group) {
                var visibleInGroup = group.querySelectorAll('.recipient-card:not(.hidden)').length;
                group.style.display = visibleInGroup > 0 ? '' : 'none';
            });
            
            if (noResults) {
                noResults.style.display = visibleCount === 0 ? 'block' : 'none';
            }
        });
    }
    
    // Card selection
    recipientCards.forEach(function(card) {
        card.addEventListener('click', function() {
            recipientCards.forEach(function(c) {
                c.classList.remove('selected');
            });
            this.classList.add('selected');
            
            var value = this.dataset.value;
            if (value) {
                var parts = value.split('_');
                document.getElementById('recipient_type').value = parts[0];
                document.getElementById('recipient_id').value = parts[1];
            }
            
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

    // Form submissions
    setupFormSubmit('depositForm', '{{ route("cashbox.deposit") }}', 'depositSubmitBtn', '{{ __("Deposit") }}');
    setupFormSubmit('distributeForm', '{{ route("cashbox.distribute") }}', 'distributeSubmitBtn', '{{ __("Distribute") }}');
    setupFormSubmit('refundForm', '{{ route("cashbox.refund") }}', 'refundSubmitBtn', '{{ __("Refund") }}');
    setupFormSubmit('selfSalaryForm', '{{ route("cashbox.self-salary") }}', 'selfSalarySubmitBtn', '{{ __("Receive") }}');

    function setupFormSubmit(formId, url, btnId, btnText) {
        var form = document.getElementById(formId);
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                submitCashboxForm(this, url, btnId, btnText);
            });
        }
    }

    function submitCashboxForm(form, url, btnId, btnText) {
        var submitBtn = document.getElementById(btnId);
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>{{ __("Loading...") }}';
        
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
                show_toastr('error', data.error || '{{ __("An error occurred") }}');
                submitBtn.disabled = false;
                submitBtn.innerHTML = btnText;
            }
        })
        .catch(error => {
            show_toastr('error', '{{ __("An error occurred") }}');
            submitBtn.disabled = false;
            submitBtn.innerHTML = btnText;
        });
    }
});

function showTransactionDetail(transactionId) {
    var modal = new bootstrap.Modal(document.getElementById('transactionDetailModal'));
    var content = document.getElementById('transactionDetailContent');
    
    content.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>';
    modal.show();
    
    fetch('{{ route("cashbox.transaction.show", "") }}/' + transactionId, {
        headers: {
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            var t = data.transaction;
            var html = '<div class="mobile-info-list">';
            html += '<div class="mobile-info-item"><span class="mobile-info-label">{{ __("Type") }}</span><span class="mobile-info-value">' + getTypeName(t.type, t.distribution_type) + '</span></div>';
            html += '<div class="mobile-info-item"><span class="mobile-info-label">{{ __("Amount") }}</span><span class="mobile-info-value fw-bold">' + t.formatted_amount + '</span></div>';
            html += '<div class="mobile-info-item"><span class="mobile-info-label">{{ __("Status") }}</span><span class="mobile-info-value">' + getStatusBadge(t.status) + '</span></div>';
            if (t.sender_name) {
                html += '<div class="mobile-info-item"><span class="mobile-info-label">{{ __("Sender") }}</span><span class="mobile-info-value">' + t.sender_name + '</span></div>';
            }
            if (t.recipient_name) {
                html += '<div class="mobile-info-item"><span class="mobile-info-label">{{ __("Recipient") }}</span><span class="mobile-info-value">' + t.recipient_name + '</span></div>';
            }
            if (t.task) {
                html += '<div class="mobile-info-item"><span class="mobile-info-label">{{ __("Task") }}</span><span class="mobile-info-value">' + t.task + '</span></div>';
            }
            if (t.comment) {
                html += '<div class="mobile-info-item"><span class="mobile-info-label">{{ __("Comment") }}</span><span class="mobile-info-value">' + t.comment + '</span></div>';
            }
            html += '<div class="mobile-info-item"><span class="mobile-info-label">{{ __("Date") }}</span><span class="mobile-info-value">' + t.created_at + '</span></div>';
            html += '</div>';
            content.innerHTML = html;
        } else {
            content.innerHTML = '<div class="alert alert-danger">{{ __("Error loading transaction") }}</div>';
        }
    })
    .catch(error => {
        content.innerHTML = '<div class="alert alert-danger">{{ __("Error loading transaction") }}</div>';
    });
}

function getTypeName(type, distributionType) {
    if (type === 'distribution') {
        if (distributionType === 'salary') {
            return '{{ __("Salary") }}';
        } else if (distributionType === 'transfer') {
            return '{{ __("Transfer") }}';
        }
        return '{{ __("Distribution") }}';
    }
    var types = {
        'deposit': '{{ __("Deposit") }}',
        'refund': '{{ __("Refund") }}',
        'self_salary': '{{ __("Self Salary") }}'
    };
    return types[type] || type;
}

function getStatusBadge(status) {
    var badges = {
        'pending': '<span class="badge bg-warning">{{ __("Pending") }}</span>',
        'in_progress': '<span class="badge bg-info">{{ __("In Progress") }}</span>',
        'completed': '<span class="badge bg-success">{{ __("Completed") }}</span>'
    };
    return badges[status] || status;
}
</script>
@endpush

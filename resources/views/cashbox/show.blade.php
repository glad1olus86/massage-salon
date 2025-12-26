@extends('layouts.admin')

@section('page-title')
    {{ __('Cashbox') }} - {{ $period->name }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('cashbox.index') }}">{{ __('Cashbox') }}</a></li>
    <li class="breadcrumb-item">{{ $period->name }}</li>
@endsection

@section('action-btn')
    <div class="float-end d-flex gap-2">
        @if ($balance['received'] > 0)
            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#distributeModal">
                <i class="ti ti-send"></i> {{ __('Distribute') }}
            </button>
        @endif

        @if ($balance['received'] > $balance['sent'] && $userRole !== 'boss')
            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#refundModal">
                <i class="ti ti-arrow-back"></i> {{ __('Refund') }}
            </button>
        @endif

        @if ($canSelfSalary && !$hasSelfSalaryThisPeriod && $balance['received'] > $balance['sent'])
            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#selfSalaryModal">
                <i class="ti ti-wallet"></i> {{ __('Self Salary') }}
            </button>
        @endif

        @if ($canDeposit)
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#depositModal">
                <i class="ti ti-plus"></i> {{ __('Deposit') }}
            </button>
        @endif
    </div>
@endsection

@push('css-page')
    <style>
        .budget-sidebar {
            position: sticky;
            top: 80px;
        }

        .budget-card {
            /* Card styling */
        }

        .diagram-container {
            height: 600px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 8px;
            overflow-x: auto;
            overflow-y: hidden;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .diagram-container #diagramContent {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
        }

        .diagram-container::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .diagram-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .diagram-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .diagram-container::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Status filter buttons */
        .status-filter-btn {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            border: 1px solid #dee2e6;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
        }

        .status-filter-btn:hover {
            background: #f8f9fa;
        }

        .status-filter-btn.active {
            background: #0d6efd;
            color: white;
            border-color: #0d6efd;
        }

        .status-filter-btn.filter-pending.active {
            background: #ffc107;
            border-color: #ffc107;
            color: #000;
        }

        .status-filter-btn.filter-in_progress.active {
            background: #0dcaf0;
            border-color: #0dcaf0;
        }

        .status-filter-btn.filter-completed.active {
            background: #198754;
            border-color: #198754;
        }

        /* Transaction detail modal enhancements */
        #transactionDetailContent .mb-3 {
            padding-bottom: 8px;
            border-bottom: 1px solid #f0f0f0;
        }

        #transactionDetailContent .mb-3:last-child {
            border-bottom: none;
        }

        /* Legacy styles for fallback */
        .transaction-node {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px;
            min-width: 200px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .transaction-node.status-pending {
            border-left: 4px solid #ffc107;
        }

        .transaction-node.status-in_progress {
            border-left: 4px solid #17a2b8;
        }

        .transaction-node.status-completed {
            border-left: 4px solid #28a745;
        }

        .transaction-node.status-overdue {
            border-left: 4px solid #dc3545;
        }

        .node-role-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .role-boss {
            background: #6f42c1;
            color: white;
        }

        .role-manager {
            background: #007bff;
            color: white;
        }

        .role-curator {
            background: #20c997;
            color: white;
        }

        .role-worker {
            background: #6c757d;
            color: white;
        }

        /* Balance card animation */
        .budget-card .card-body h4 {
            transition: all 0.3s ease;
        }

        .budget-card .card-body h4.updated {
            animation: balance-update 0.5s ease;
        }

        @keyframes balance-update {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); color: #0d6efd; }
            100% { transform: scale(1); }
        }
        
        /* Recipient Cards Styles */
        .recipient-search-box { position: relative; }
        .recipient-search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #999; }
        .recipient-search-input { padding-left: 36px; }
        .recipients-list { max-height: 200px; overflow-y: auto; border: 1px solid #e0e0e0; border-radius: 8px; }
        .recipient-group-title { padding: 8px 12px; background: #f8f9fa; font-size: 11px; font-weight: 600; color: #FF0049; text-transform: uppercase; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 6px; }
        .recipient-card { display: flex; align-items: center; padding: 10px 12px; cursor: pointer; transition: background 0.15s; border-bottom: 1px solid #f0f0f0; }
        .recipient-card:hover { background: #fff5f7; }
        .recipient-card.selected { background: #ffe0e8; }
        .recipient-card.selected .recipient-check { opacity: 1; }
        .recipient-avatar { width: 36px; height: 36px; border-radius: 50%; background: #FF0049; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px; margin-right: 10px; }
        .recipient-avatar.curator { background: #17a2b8; }
        .recipient-avatar.worker { background: #6c757d; }
        .recipient-info { flex: 1; }
        .recipient-name { font-weight: 500; font-size: 14px; }
        .recipient-role { font-size: 12px; color: #888; }
        .recipient-check { opacity: 0; color: #FF0049; transition: opacity 0.15s; }
        .recipients-no-results { padding: 20px; text-align: center; color: #999; }
        .recipients-no-results i { font-size: 24px; display: block; margin-bottom: 8px; }
        .selected-recipient { display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: #fff5f7; border-radius: 8px; margin-top: 8px; }
        .selected-recipient-label { font-size: 12px; color: #888; }
        .selected-recipient-name { font-size: 14px; font-weight: 600; color: #FF0049; }
        .selected-recipient-clear { width: 24px; height: 24px; border: none; background: rgba(255, 0, 73, 0.1); border-radius: 50%; color: #FF0049; display: flex; align-items: center; justify-content: center; cursor: pointer; }
        .selected-recipient-clear:hover { background: #FF0049; color: #fff; }
    </style>
@endpush

@section('content')
    <div class="row">
        {{-- Main Diagram Area --}}
        <div class="col-lg-9 col-md-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="mb-0">{{ __('Money Flow Diagram') }}</h5>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            {{-- Status filter buttons --}}
                            <div class="btn-group" role="group" id="statusFilter">
                                <button type="button" class="status-filter-btn active"
                                    data-status="all">{{ __('All') }}</button>
                                <button type="button" class="status-filter-btn filter-pending"
                                    data-status="pending">{{ __('Pending') }}</button>
                                <button type="button" class="status-filter-btn filter-in_progress"
                                    data-status="in_progress">{{ __('In Progress') }}</button>
                                <button type="button" class="status-filter-btn filter-completed"
                                    data-status="completed">{{ __('Completed') }}</button>
                            </div>
                            @if ($period->is_frozen)
                                <span class="badge bg-secondary">
                                    <i class="ti ti-lock me-1"></i>{{ __('Period Frozen') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="diagram-container p-3" id="diagramContainer">
                        <div class="text-center py-5" id="diagramLoading">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">{{ __('Loading...') }}</span>
                            </div>
                            <p class="mt-2 text-muted">{{ __('Loading diagram...') }}</p>
                        </div>
                        <div id="diagramContent" style="display: none;"></div>
                        <div id="diagramEmpty" class="text-center py-5" style="display: none;">
                            <i class="ti ti-chart-dots" style="font-size: 48px; color: #ccc;"></i>
                            <p class="mt-2 text-muted">{{ __('No transactions in this period') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Budget Sidebar --}}
        <div class="col-lg-3 col-md-4">
            <div class="budget-sidebar">
                <div class="card budget-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ti ti-wallet me-2"></i>{{ __('Budget') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted">{{ __('Received') }}</small>
                            <h4 class="text-success mb-0" id="balanceReceived">
                                {{ formatCashboxCurrency($balance['received']) }}
                            </h4>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">{{ __('Distributed') }}</small>
                            <h4 class="text-danger mb-0" id="balanceSent">
                                {{ formatCashboxCurrency($balance['sent']) }}
                            </h4>
                        </div>
                        <hr>
                        <div>
                            <small class="text-muted">{{ __('Remaining') }}</small>
                            <h4 class="text-primary mb-0" id="balanceRemaining">
                                {{ formatCashboxCurrency($balance['received'] - $balance['sent']) }}
                            </h4>
                        </div>
                    </div>
                </div>

                {{-- Period Info --}}
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ti ti-info-circle me-2"></i>{{ __('Information') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <small class="text-muted">{{ __('Period') }}</small>
                            <p class="mb-0 fw-bold">{{ $period->name }}</p>
                        </div>
                        @if($canViewTotalDeposited)
                        <div class="mb-2">
                            <small class="text-muted">{{ __('Total Deposited') }}</small>
                            <p class="mb-0">{{ formatCashboxCurrency($period->total_deposited) }}</p>
                        </div>
                        @endif
                        <div>
                            <small class="text-muted">{{ __('Status') }}</small>
                            <p class="mb-0">
                                @if ($period->is_frozen)
                                    <span class="badge bg-secondary">{{ __('Frozen') }}</span>
                                @else
                                    <span class="badge bg-success">{{ __('Active') }}</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- Deposit Modal --}}
    @if ($canDeposit)
        <div class="modal fade" id="depositModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Deposit Money') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="depositForm">
                        @csrf
                        <input type="hidden" name="period_id" value="{{ $period->id }}">
                        <div class="modal-body">
                            <div class="form-group mb-3">
                                <label class="form-label">{{ __('Amount') }} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="amount" class="form-control" step="0.01"
                                        min="0.01" required>
                                    <span class="input-group-text">{{ getCashboxCurrencySymbol() }}</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ __('Comment') }}</label>
                                <textarea name="comment" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-primary">{{ __('Deposit') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Distribute Modal --}}
    <div class="modal fade" id="distributeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Distribute Money') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="distributeForm">
                    @csrf
                    <input type="hidden" name="period_id" value="{{ $period->id }}">
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Distribution Type') }} <span class="text-danger">*</span></label>
                            <select name="distribution_type" id="distributionTypeSelect" class="form-control" required>
                                <option value="">{{ __('Select distribution type') }}</option>
                                <option value="salary">{{ __('Employee Salary') }}</option>
                                <option value="transfer">{{ __('Fund Transfer') }}</option>
                            </select>
                            <small class="text-muted" id="distributionTypeHintMain"></small>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Recipient') }} <span class="text-danger">*</span></label>
                            <input type="hidden" name="recipient" id="cashboxRecipientValue" value="">

                            {{-- Search Input --}}
                            <div class="recipient-search-box mb-2">
                                <i class="ti ti-search recipient-search-icon"></i>
                                <input type="text" id="cashboxRecipientSearch" class="form-control recipient-search-input"
                                    placeholder="{{ __('Search recipient') }}..." autocomplete="off">
                            </div>

                            {{-- Recipients List --}}
                            <div class="recipients-list" id="cashboxRecipientsList">
                                @php
                                    $cbManagers = collect($recipients)->filter(fn($r) => $r['role'] === 'manager' && !isset($r['is_self']));
                                    $cbCurators = collect($recipients)->filter(fn($r) => $r['role'] === 'curator' && !isset($r['is_self']));
                                    $cbWorkers = collect($recipients)->filter(fn($r) => $r['role'] === 'worker' && !isset($r['is_self']));
                                @endphp

                                @if($cbManagers->count() > 0)
                                    <div class="recipient-group" data-group="managers">
                                        <div class="recipient-group-title">
                                            <i class="ti ti-user"></i> {{ __('Managers') }}
                                        </div>
                                        @foreach($cbManagers as $recipient)
                                            <div class="recipient-card"
                                                data-value="{{ $recipient['type'] === 'App\\Models\\Worker' ? 'worker' : 'user' }}_{{ $recipient['id'] }}"
                                                data-name="{{ strtolower($recipient['name']) }}">
                                                <div class="recipient-avatar">{{ strtoupper(substr($recipient['name'], 0, 1)) }}</div>
                                                <div class="recipient-info">
                                                    <div class="recipient-name">{{ $recipient['name'] }}</div>
                                                    <div class="recipient-role">{{ __('Manager') }}</div>
                                                </div>
                                                <div class="recipient-check"><i class="ti ti-check"></i></div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if($cbCurators->count() > 0)
                                    <div class="recipient-group" data-group="curators">
                                        <div class="recipient-group-title">
                                            <i class="ti ti-user"></i> {{ __('Curators') }}
                                        </div>
                                        @foreach($cbCurators as $recipient)
                                            <div class="recipient-card"
                                                data-value="{{ $recipient['type'] === 'App\\Models\\Worker' ? 'worker' : 'user' }}_{{ $recipient['id'] }}"
                                                data-name="{{ strtolower($recipient['name']) }}">
                                                <div class="recipient-avatar curator">{{ strtoupper(substr($recipient['name'], 0, 1)) }}</div>
                                                <div class="recipient-info">
                                                    <div class="recipient-name">{{ $recipient['name'] }}</div>
                                                    <div class="recipient-role">{{ __('Curator') }}</div>
                                                </div>
                                                <div class="recipient-check"><i class="ti ti-check"></i></div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if($cbWorkers->count() > 0)
                                    <div class="recipient-group" data-group="workers">
                                        <div class="recipient-group-title">
                                            <i class="ti ti-user"></i> {{ __('Workers') }}
                                        </div>
                                        @foreach($cbWorkers as $recipient)
                                            <div class="recipient-card"
                                                data-value="{{ $recipient['type'] === 'App\\Models\\Worker' ? 'worker' : 'user' }}_{{ $recipient['id'] }}"
                                                data-name="{{ strtolower($recipient['name']) }}">
                                                <div class="recipient-avatar worker">{{ strtoupper(substr($recipient['name'], 0, 1)) }}</div>
                                                <div class="recipient-info">
                                                    <div class="recipient-name">{{ $recipient['name'] }}</div>
                                                    <div class="recipient-role">{{ __('Worker') }}</div>
                                                </div>
                                                <div class="recipient-check"><i class="ti ti-check"></i></div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="recipients-no-results" id="cashboxNoResults" style="display: none;">
                                    <i class="ti ti-user-off"></i>
                                    <span>{{ __('No recipients found') }}</span>
                                </div>
                            </div>

                            {{-- Selected Display --}}
                            <div class="selected-recipient" id="cashboxSelectedRecipient" style="display: none;">
                                <div class="selected-recipient-info">
                                    <span class="selected-recipient-label">{{ __('Selected') }}:</span>
                                    <span class="selected-recipient-name" id="cashboxSelectedName"></span>
                                </div>
                                <button type="button" class="selected-recipient-clear" id="cashboxClearRecipient">
                                    <i class="ti ti-x"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Amount') }} <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="amount" class="form-control" step="0.01" min="0.01"
                                    required>
                                <span class="input-group-text">{{ getCashboxCurrencySymbol() }}</span>
                            </div>
                            <small class="text-muted">{{ __('Available:') }} <span
                                    id="availableBalance">{{ formatCashboxCurrency($balance['received'] - $balance['sent']) }}</span></small>
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
                        <button type="submit" class="btn btn-success">{{ __('Distribute') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Refund Modal --}}
    <div class="modal fade" id="refundModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Refund Money') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="refundForm">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Transaction to Refund') }} <span
                                    class="text-danger">*</span></label>
                            <select name="transaction_id" id="refundTransactionId" class="form-control" required>
                                <option value="">{{ __('Select transaction') }}</option>
                                @foreach ($refundableTransactions as $transaction)
                                    <option value="{{ $transaction->id }}" data-amount="{{ $transaction->amount }}">
                                        #{{ $transaction->id }} |
                                        {{ $transaction->sender->name ?? __('Unknown') }} |
                                        {{ formatCashboxCurrency($transaction->amount) }} |
                                        {{ $transaction->created_at->format('d.m.Y H:i') }}
                                        @if ($transaction->task)
                                            | {{ Str::limit($transaction->task, 20) }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @if ($refundableTransactions->isEmpty())
                                <small class="text-muted">{{ __('No transactions to refund') }}</small>
                            @endif
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Amount') }} <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="amount" class="form-control" step="0.01" min="0.01"
                                    required>
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
                            {{ $refundableTransactions->isEmpty() ? 'disabled' : '' }}>{{ __('Refund') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Self Salary Modal --}}
    @if ($canSelfSalary)
        <div class="modal fade" id="selfSalaryModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Self Salary') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="selfSalaryForm">
                        @csrf
                        <input type="hidden" name="period_id" value="{{ $period->id }}">
                        <div class="modal-body">
                            @if ($hasSelfSalaryThisPeriod)
                                <div class="alert alert-warning">
                                    <i class="ti ti-alert-triangle me-1"></i>
                                    {{ __('You have already received salary in this period') }}
                                </div>
                            @endif
                            <div class="form-group mb-3">
                                <label class="form-label">{{ __('Amount') }} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="amount" class="form-control" step="0.01"
                                        min="0.01" required {{ $hasSelfSalaryThisPeriod ? 'disabled' : '' }}>
                                    <span class="input-group-text">{{ getCashboxCurrencySymbol() }}</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ __('Comment') }}</label>
                                <textarea name="comment" class="form-control" rows="2" {{ $hasSelfSalaryThisPeriod ? 'disabled' : '' }}></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-info"
                                {{ $hasSelfSalaryThisPeriod ? 'disabled' : '' }}>{{ __('Receive') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Transaction Detail Modal --}}
    <div class="modal fade" id="transactionDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Transaction Details') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="transactionDetailContent">
                    {{-- Content loaded dynamically --}}
                </div>
                <div class="modal-footer" id="transactionDetailFooter">
                    <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection


@push('script-page')
    <script src="{{ asset('js/cashbox-diagram.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var periodId = {{ $period->id }};
            var csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            var diagram = null;

            // Currency symbol for formatting
            var currencySymbol = '{{ getCashboxCurrencySymbol() }}';

            // Initialize diagram with translations
            var translations = {
                deposit: '{{ __('Deposit') }}',
                distribution: '{{ __('Distribution') }}',
                refund: '{{ __('Refund') }}',
                self_salary: '{{ __('Self Salary') }}',
                salary_list: '{{ __('Salary List') }}',
                pending: '{{ __('Pending') }}',
                in_progress: '{{ __('In Progress') }}',
                completed: '{{ __('Completed') }}',
                overdue: '{{ __('Overdue') }}',
                unknown: '{{ __('Unknown') }}',
                loading: '{{ __('Loading...') }}',
                error: '{{ __('Loading error') }}',
                no_transactions: '{{ __('No transactions in this period') }}',
                sender: '{{ __('Sender') }}',
                recipient: '{{ __('Recipient') }}',
                amount: '{{ __('Amount') }}',
                status: '{{ __('Status') }}',
                task: '{{ __('Task') }}',
                comment: '{{ __('Comment') }}',
                date: '{{ __('Date') }}',
                type: '{{ __('Operation Type') }}',
                take_to_work: '{{ __('Take to Work') }}',
                close: '{{ __('Close') }}'
            };

            // Initialize the diagram
            diagram = new CashboxDiagram('diagramContent', {
                translations: translations,
                currencySymbol: currencySymbol,
                onNodeClick: function(node) {
                    showTransactionDetail(node);
                }
            });

            // Load diagram data
            loadDiagram();

            function loadDiagram() {
                document.getElementById('diagramLoading').style.display = 'block';
                document.getElementById('diagramContent').style.display = 'none';
                document.getElementById('diagramEmpty').style.display = 'none';

                fetch('{{ route('cashbox.diagram', $period->id) }}', {
                        headers: {
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('diagramLoading').style.display = 'none';
                        if (data.diagram && data.diagram.nodes && data.diagram.nodes.length > 0) {
                            document.getElementById('diagramContent').style.display = 'block';
                            diagram.setData(data.diagram);
                        } else {
                            document.getElementById('diagramEmpty').style.display = 'block';
                        }
                    })
                    .catch(error => {
                        document.getElementById('diagramLoading').style.display = 'none';
                        document.getElementById('diagramContent').style.display = 'block';
                        diagram.showError('{{ __('Diagram loading error') }}');
                    });
            }

            function getTypeName(type) {
                return translations[type] || type;
            }

            function getStatusName(status) {
                return translations[status] || status;
            }

            function getStatusBadgeClass(status) {
                switch (status) {
                    case 'pending':
                        return 'bg-warning';
                    case 'in_progress':
                        return 'bg-info';
                    case 'completed':
                        return 'bg-success';
                    case 'overdue':
                        return 'bg-danger';
                    default:
                        return 'bg-secondary';
                }
            }

            function formatMoney(amount) {
                return parseFloat(amount).toLocaleString('ru-RU', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }) + ' ' + currencySymbol;
            }

            function showTransactionDetail(node) {
                var content = document.getElementById('transactionDetailContent');
                var senderName = node.sender ? node.sender.name : null;
                var recipientName = node.recipient ? node.recipient.name : translations.unknown;

                // Check if this is a salary list
                if (node.is_salary_list && node.salary_recipients) {
                    var recipientsList = node.salary_recipients.map(function(r) {
                        return `<div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span>${escapeHtml(r.name)}</span>
                            <span class="fw-bold text-success">${formatMoney(r.amount)}</span>
                        </div>`;
                    }).join('');
                    
                    content.innerHTML = `
                        <div class="mb-3">
                            <label class="text-muted small">${translations.type}</label>
                            <p class="mb-0 fw-bold">{{ __('Salary List') }} №${node.salary_list_number}</p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">{{ __('Total Amount') }}</label>
                            <p class="mb-0 fw-bold text-success">${formatMoney(node.amount)}</p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">{{ __('Number of Recipients') }}</label>
                            <p class="mb-0">${node.salary_recipients.length}</p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">{{ __('Recipients') }}</label>
                            <div class="mt-2" style="max-height: 300px; overflow-y: auto;">
                                ${recipientsList}
                            </div>
                        </div>
                    `;
                    
                    var footer = document.getElementById('transactionDetailFooter');
                    footer.innerHTML = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' + translations.close + '</button>';
                    
                    new bootstrap.Modal(document.getElementById('transactionDetailModal')).show();
                    return;
                }

                // Check if this is a deposit with multiple deposits
                if (node.type === 'deposit' && node.has_multiple_deposits && node.deposit_history) {
                    var depositsList = node.deposit_history.map(function(d) {
                        return `<div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="text-muted small">${new Date(d.date).toLocaleString('ru-RU')}</span>
                            <span class="fw-bold text-success">+${formatMoney(d.amount)}</span>
                        </div>`;
                    }).join('');
                    
                    content.innerHTML = `
                        <div class="mb-3">
                            <label class="text-muted small">${translations.type}</label>
                            <p class="mb-0 fw-bold">${getTypeName(node.type)}</p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">${translations.recipient}</label>
                            <p class="mb-0">${recipientName}</p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">{{ __('Current Amount') }}</label>
                            <p class="mb-0 fw-bold text-success" style="font-size: 1.25rem;">${formatMoney(node.amount)}</p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">{{ __('Number of Deposits') }}</label>
                            <p class="mb-0">${node.deposit_count}</p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">{{ __('Recent Deposits') }}</label>
                            <div class="mt-2" style="max-height: 200px; overflow-y: auto;">
                                ${depositsList}
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small">${translations.status}</label>
                            <p class="mb-0"><span class="badge ${getStatusBadgeClass(node.status)}">${node.status_label || getStatusName(node.status)}</span></p>
                        </div>
                    `;
                    
                    var footer = document.getElementById('transactionDetailFooter');
                    footer.innerHTML = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' + translations.close + '</button>';
                    
                    new bootstrap.Modal(document.getElementById('transactionDetailModal')).show();
                    return;
                }

                content.innerHTML = `
            <div class="mb-3">
                <label class="text-muted small">${translations.type}</label>
                <p class="mb-0 fw-bold">${getTypeName(node.type)}</p>
            </div>
            ${senderName ? `
                                                <div class="mb-3">
                                                    <label class="text-muted small">${translations.sender}</label>
                                                    <p class="mb-0">${senderName}</p>
                                                </div>` : ''}
            <div class="mb-3">
                <label class="text-muted small">${translations.recipient}</label>
                <p class="mb-0">${recipientName}</p>
            </div>
            <div class="mb-3">
                <label class="text-muted small">${translations.amount}</label>
                <p class="mb-0 fw-bold text-success">${formatMoney(node.amount)}</p>
            </div>
            ${(node.carryover_received && node.carryover_received > 0) ? `
                                                <div class="mb-3">
                                                    <label class="text-muted small">{{ __('Original Transfer Amount') }}</label>
                                                    <p class="mb-0 text-muted">${formatMoney(node.amount - node.carryover_received)}</p>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="text-muted small">{{ __('Received from Previous Transfer') }}</label>
                                                    <p class="mb-0 text-success">+${formatMoney(node.carryover_received)}</p>
                                                </div>` : ''}
            ${(node.original_amount && node.original_amount !== node.amount && !node.carryover_received) ? `
                                                <div class="mb-3">
                                                    <label class="text-muted small">{{ __('Originally Deposited Amount') }}</label>
                                                    <p class="mb-0 text-muted">${formatMoney(node.original_amount)}</p>
                                                </div>` : ''}
            <div class="mb-3">
                <label class="text-muted small">${translations.status}</label>
                <p class="mb-0"><span class="badge ${getStatusBadgeClass(node.status)}">${node.status_label || getStatusName(node.status)}</span></p>
            </div>
            ${node.task ? `
                                                <div class="mb-3">
                                                    <label class="text-muted small">${translations.task}</label>
                                                    <p class="mb-0">${escapeHtml(node.task)}</p>
                                                </div>` : ''}
            ${node.comment ? `
                                                <div class="mb-3">
                                                    <label class="text-muted small">${translations.comment}</label>
                                                    <p class="mb-0">${escapeHtml(node.comment)}</p>
                                                </div>` : ''}
            ${node.created_at ? `
                                                <div class="mb-3">
                                                    <label class="text-muted small">${translations.date}</label>
                                                    <p class="mb-0">${new Date(node.created_at).toLocaleString('ru-RU')}</p>
                                                </div>` : ''}
        `;

                var footer = document.getElementById('transactionDetailFooter');
                footer.innerHTML = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' +
                    translations.close + '</button>';

                // Add "Take to work" button if pending and user can update
                if (node.status === 'pending') {
                    footer.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${translations.close}</button>
                <button type="button" class="btn btn-info" onclick="updateTransactionStatus(${node.id}, 'in_progress')">
                    <i class="ti ti-player-play me-1"></i>${translations.take_to_work}
                </button>
            `;
                }

                // Store selected node for refund
                window.selectedTransactionNode = node;

                new bootstrap.Modal(document.getElementById('transactionDetailModal')).show();
            }

            function escapeHtml(text) {
                var div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            window.updateTransactionStatus = function(transactionId, status) {
                fetch('{{ url('cashbox/transaction') }}/' + transactionId + '/status', {
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            status: status
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            show_toastr('success', data.message);
                            bootstrap.Modal.getInstance(document.getElementById('transactionDetailModal'))
                                .hide();
                            loadDiagram();
                            updateBalance();
                        } else {
                            show_toastr('error', data.error);
                        }
                    });
            };

            function updateBalance() {
                fetch('{{ route('cashbox.balance', $period->id) }}', {
                        headers: {
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.balance) {
                            document.getElementById('balanceReceived').textContent = formatMoney(data.balance
                                .received);
                            document.getElementById('balanceSent').textContent = formatMoney(data.balance.sent);
                            document.getElementById('balanceRemaining').textContent = formatMoney(data.balance
                                .available);
                            if (document.getElementById('availableBalance')) {
                                document.getElementById('availableBalance').textContent = formatMoney(data
                                    .balance.available);
                            }
                            // Animate balance update
                            var balanceEls = document.querySelectorAll('.budget-card h4');
                            balanceEls.forEach(function(el) {
                                el.classList.add('updated');
                                setTimeout(function() {
                                    el.classList.remove('updated');
                                }, 500);
                            });
                        }
                    });
            }

            // Expose functions globally for diagram refresh
            window.refreshCashboxDiagram = loadDiagram;
            window.updateCashboxBalance = updateBalance;

            // Status filter functionality
            var statusFilterBtns = document.querySelectorAll('#statusFilter .status-filter-btn');
            statusFilterBtns.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    // Update active state
                    statusFilterBtns.forEach(function(b) {
                        b.classList.remove('active');
                    });
                    this.classList.add('active');

                    // Apply filter
                    var status = this.getAttribute('data-status');
                    if (diagram) {
                        diagram.filterByStatus(status);
                    }
                });
            });

            // Balance update animation
            function animateBalanceUpdate() {
                var balanceEls = document.querySelectorAll('.budget-card h4');
                balanceEls.forEach(function(el) {
                    el.classList.add('updated');
                    setTimeout(function() {
                        el.classList.remove('updated');
                    }, 500);
                });
            }

            // Form submissions
            function handleFormSubmit(formId, url, successCallback) {
                var form = document.getElementById(formId);
                if (!form) return;

                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    var submitBtn = form.querySelector('button[type="submit"]');
                    var originalText = submitBtn.innerHTML;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML =
                        '<span class="spinner-border spinner-border-sm me-1"></span>{{ __('Loading...') }}';

                    var formData = new FormData(form);

                    // Parse recipient field for distribute form
                    if (formId === 'distributeForm') {
                        var recipientVal = formData.get('recipient');
                        if (recipientVal) {
                            var parts = recipientVal.split('_');
                            formData.set('recipient_type', parts[0]);
                            formData.set('recipient_id', parts[1]);
                            formData.delete('recipient');
                        }
                    }

                    fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                show_toastr('success', data.message);
                                var modal = bootstrap.Modal.getInstance(form.closest('.modal'));
                                if (modal) modal.hide();
                                form.reset();
                                loadDiagram();
                                updateBalance();
                                if (successCallback) successCallback(data);
                            } else {
                                show_toastr('error', data.error);
                            }
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        })
                        .catch(error => {
                            show_toastr('error', '{{ __('An error occurred') }}');
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        });
                });
            }

            handleFormSubmit('depositForm', '{{ route('cashbox.deposit') }}', function() {
                // Reload page after deposit to show action buttons
                window.location.reload();
            });
            handleFormSubmit('distributeForm', '{{ route('cashbox.distribute') }}');
            handleFormSubmit('refundForm', '{{ route('cashbox.refund') }}');
            handleFormSubmit('selfSalaryForm', '{{ route('cashbox.self-salary') }}', function() {
                // Disable self-salary button after success
                var btn = document.querySelector('[data-bs-target="#selfSalaryModal"]');
                if (btn) btn.style.display = 'none';
            });

            // Handle refund modal - pre-select transaction if one was selected on diagram
            var refundModal = document.getElementById('refundModal');
            var refundTransactionSelect = document.getElementById('refundTransactionId');

            if (refundModal) {
                refundModal.addEventListener('show.bs.modal', function() {
                    if (window.selectedTransactionNode && refundTransactionSelect) {
                        // Try to select the transaction in dropdown
                        var option = refundTransactionSelect.querySelector('option[value="' + window
                            .selectedTransactionNode.id + '"]');
                        if (option) {
                            refundTransactionSelect.value = window.selectedTransactionNode.id;
                            // Trigger change to fill amount
                            refundTransactionSelect.dispatchEvent(new Event('change'));
                        }
                    }
                });
            }

            // Auto-fill amount when transaction is selected
            if (refundTransactionSelect) {
                refundTransactionSelect.addEventListener('change', function() {
                    var selectedOption = this.options[this.selectedIndex];
                    var amount = selectedOption.getAttribute('data-amount');
                    if (amount) {
                        var amountInput = document.querySelector('#refundForm input[name="amount"]');
                        if (amountInput) {
                            amountInput.value = amount;
                        }
                    }
                });
            }

            // Distribution type hint and recipient filtering
            var distributionTypeSelect = document.getElementById('distributionTypeSelect');
            var distributionTypeHint = document.getElementById('distributionTypeHintMain');

            function filterCashboxRecipients(distributionType) {
                var managersGroup = document.querySelector('#cashboxRecipientsList [data-group="managers"]');
                var curatorsGroup = document.querySelector('#cashboxRecipientsList [data-group="curators"]');
                var workersGroup = document.querySelector('#cashboxRecipientsList [data-group="workers"]');

                if (!distributionType) {
                    if (managersGroup) managersGroup.classList.remove('d-none');
                    if (curatorsGroup) curatorsGroup.classList.remove('d-none');
                    if (workersGroup) workersGroup.classList.add('d-none');
                } else if (distributionType === 'salary') {
                    if (managersGroup) managersGroup.classList.add('d-none');
                    if (curatorsGroup) curatorsGroup.classList.remove('d-none');
                    if (workersGroup) workersGroup.classList.remove('d-none');
                } else if (distributionType === 'transfer') {
                    if (managersGroup) managersGroup.classList.remove('d-none');
                    if (curatorsGroup) curatorsGroup.classList.remove('d-none');
                    if (workersGroup) workersGroup.classList.add('d-none');
                }

                // Clear selection
                var cards = document.querySelectorAll('#cashboxRecipientsList .recipient-card');
                cards.forEach(function(c) { c.classList.remove('selected'); });
                document.getElementById('cashboxRecipientValue').value = '';
                document.getElementById('cashboxSelectedRecipient').style.display = 'none';
            }

            if (distributionTypeSelect) {
                distributionTypeSelect.addEventListener('change', function() {
                    var value = this.value;
                    if (distributionTypeHint) {
                        if (value === 'salary') {
                            distributionTypeHint.textContent = '{{ __('Final salary payment. Transaction will be completed immediately.') }}';
                        } else if (value === 'transfer') {
                            distributionTypeHint.textContent = '{{ __('Money transfer for further distribution to other employees.') }}';
                        } else {
                            distributionTypeHint.textContent = '';
                        }
                    }
                    filterCashboxRecipients(value);
                });
                filterCashboxRecipients(distributionTypeSelect.value);
            }

            // Recipient search and selection
            var cbSearch = document.getElementById('cashboxRecipientSearch');
            var cbCards = document.querySelectorAll('#cashboxRecipientsList .recipient-card');
            var cbValue = document.getElementById('cashboxRecipientValue');
            var cbSelected = document.getElementById('cashboxSelectedRecipient');
            var cbSelectedName = document.getElementById('cashboxSelectedName');
            var cbClear = document.getElementById('cashboxClearRecipient');

            if (cbSearch) {
                cbSearch.addEventListener('input', function() {
                    var query = this.value.toLowerCase().trim();
                    cbCards.forEach(function(card) {
                        var name = card.dataset.name || '';
                        card.style.display = (query.length < 2 || name.includes(query)) ? '' : 'none';
                    });
                });
            }

            cbCards.forEach(function(card) {
                card.addEventListener('click', function() {
                    cbCards.forEach(function(c) { c.classList.remove('selected'); });
                    this.classList.add('selected');
                    cbValue.value = this.dataset.value;
                    cbSelectedName.textContent = this.querySelector('.recipient-name').textContent;
                    cbSelected.style.display = 'flex';
                });
            });

            if (cbClear) {
                cbClear.addEventListener('click', function() {
                    cbCards.forEach(function(c) { c.classList.remove('selected'); });
                    cbValue.value = '';
                    cbSelected.style.display = 'none';
                });
            }
        });
    </script>
@endpush

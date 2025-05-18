@extends('layouts.app')

@section('styles')
<style>
    /* Print-specific styles */
    @media print {
        /* Hide browser's default header and footer */
        @page {
            margin-top: 0.5cm;
            margin-bottom: 0.5cm;
            margin-left: 0.5cm;
            margin-right: 0.5cm;
            size: auto;
        }
        
        /* Hide browser header/footer content */
        html {
            -webkit-print-color-adjust: exact !important;
        }
        
        body * { visibility: hidden; }
        .printable-section, .printable-section * { visibility: visible; }
        .printable-section {
            position: absolute; left: 0; top: 0; width: 100%; padding: 15px;
        }
        .no-print, .stats-card, .chart-container { display: none !important; }
        
        /* Enhanced table styling */
        .table { width: 100% !important; border-collapse: collapse !important; font-size: 10pt !important; }
        .table th, .table td { border: 1px solid #ddd !important; padding: 5px !important; }
        .print-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10pt;
        }
        .print-table th {
            background-color: #f2f2f2 !important;
            color: #333;
            font-weight: bold;
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .print-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .print-table tr:nth-child(even) {
            background-color: #f9f9f9 !important;
        }
        
        /* Card styling */
        .card { border: none !important; box-shadow: none !important; margin: 0 !important; }
        .card-header, .card-body { padding: 0 !important; }
        
        /* Colors and badges */
        .badge { background-color: transparent !important; color: #000 !important; font-weight: normal !important; padding: 0 !important; }
        .text-success { color: #28a745 !important; }
        .text-danger { color: #dc3545 !important; }
        
        /* Headers */
        .print-header { text-align: center; margin-bottom: 25px; }
        .company-name { 
            font-size: 24px; 
            font-weight: 700; 
            margin-bottom: 5px; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
        }
        .report-title { 
            font-size: 20px; 
            font-weight: 600; 
            margin-bottom: 5px; 
        }
        .report-period { 
            font-size: 16px; 
            margin-bottom: 15px; 
        }
        .section-title {
            font-size: 16px;
            font-weight: 600;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #ddd;
        }
        
        /* Footer styling */
        .print-footer {
            margin-top: 30px; 
            page-break-inside: avoid;
            border-top: 1px solid #ddd; 
            padding-top: 10px; 
            font-size: 9pt;
        }
        
        /* Link styling */
        a { text-decoration: none !important; color: #000 !important; }
        
        /* Summary display */
        .inventory-summary, .transaction-summary {
            margin-bottom: 20px;
        }
        .summary-item {
            margin-bottom: 10px;
        }
        .d-flex {
            display: flex;
        }
        .justify-content-between {
            justify-content: space-between;
        }
        .mb-4 {
            margin-bottom: 15px;
        }
    }
    
    /* Regular screen styling */
    .loading-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(255,255,255,0.8); z-index: 9999; display: flex; align-items: center; justify-content: center; flex-direction: column; }
    .spinner { width: 50px; height: 50px; border: 4px solid var(--primary-color); border-radius: 50%; border-top: 4px solid #f3f3f3; animation: spin 1s linear infinite; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .btn-group-report { margin-bottom: 15px; }
    .btn-report { border-radius: 20px; padding: 8px 16px; margin-right: 5px; font-weight: 600; transition: all 0.2s; }
    .btn-report:hover { transform: translateY(-2px); }
    .btn-report.active { box-shadow: 0 0 0 2px white, 0 0 0 4px var(--primary-color); }
    .per-page-selector { display: inline-flex; align-items: center; }
    .per-page-selector .btn { border-radius: 0; padding: 0.25rem 0.5rem; }
    .per-page-selector .btn:first-child { border-top-left-radius: 0.25rem; border-bottom-left-radius: 0.25rem; }
    .per-page-selector .btn:last-child { border-top-right-radius: 0.25rem; border-bottom-right-radius: 0.25rem; }
    .printable-section { display: none; }
</style>
@endsection

@section('content')
<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay no-print" style="display: none;">
    <div class="spinner mb-3"></div>
    <h5>Generating Report...</h5>
</div>

<div class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <h1 class="display-6 fw-bold text-primary">
                <i class="bi bi-box-seam me-2"></i>Inventory Report
            </h1>
            <p class="text-muted">
                <i class="bi bi-calendar3"></i> 
                {{ $startDate->format('M d, Y') }} to {{ $endDate->format('M d, Y') }}
            </p>
        </div>
        <div>
            <div class="btn-group mb-2">
                <button onclick="window.print()" class="btn btn-outline-dark">
                    <i class="bi bi-printer me-1"></i> Print
                </button>
                <button type="button" class="btn btn-outline-dark dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="visually-hidden">Export options</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="{{ route('reports.inventory.export', array_merge(request()->query(), ['format' => 'excel'])) }}"
                           onclick="showLoading()">
                            <i class="bi bi-file-earmark-excel me-2"></i>Export to Excel
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('reports.inventory.export', array_merge(request()->query(), ['format' => 'csv'])) }}"
                           onclick="showLoading()">
                            <i class="bi bi-file-earmark-text me-2"></i>Export to CSV
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('reports.inventory.export', array_merge(request()->query(), ['format' => 'pdf'])) }}"
                           onclick="showLoading()">
                            <i class="bi bi-file-earmark-pdf me-2"></i>Export to PDF
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm mb-4 no-print">
        <div class="card-body">
            <div class="row">
                <div class="col-lg-8">
                    <h5 class="card-title mb-3">Report Period</h5>
                    <div class="btn-group-report mb-3">
                        <a href="{{ route('reports.inventory', ['period' => 'daily', 'item_type' => request('item_type', 'all')]) }}" 
                           class="btn btn-report {{ $reportPeriod == 'daily' ? 'btn-primary active' : 'btn-outline-primary' }}">
                            <i class="bi bi-calendar-day me-1"></i> Daily
                        </a>
                        <a href="{{ route('reports.inventory', ['period' => 'weekly', 'item_type' => request('item_type', 'all')]) }}" 
                           class="btn btn-report {{ $reportPeriod == 'weekly' ? 'btn-primary active' : 'btn-outline-primary' }}">
                            <i class="bi bi-calendar-week me-1"></i> Weekly
                        </a>
                        <a href="{{ route('reports.inventory', ['period' => 'monthly', 'item_type' => request('item_type', 'all')]) }}" 
                           class="btn btn-report {{ $reportPeriod == 'monthly' ? 'btn-primary active' : 'btn-outline-primary' }}">
                            <i class="bi bi-calendar-month me-1"></i> Monthly
                        </a>
                        <a href="{{ route('reports.inventory', ['period' => 'custom', 'item_type' => request('item_type', 'all')]) }}" 
                           class="btn btn-report {{ $reportPeriod == 'custom' ? 'btn-primary active' : 'btn-outline-primary' }}">
                            <i class="bi bi-calendar-range me-1"></i> Custom Range
                        </a>
                    </div>
                    <form id="reportForm" action="{{ route('reports.inventory') }}" method="GET" class="row g-3 align-items-end">
                        <input type="hidden" name="period" value="custom">
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="{{ request('start_date', $startDate->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="{{ request('end_date', $endDate->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100" onclick="showLoading()">
                                <i class="bi bi-search me-1"></i> Generate Report
                            </button>
                        </div>
                    </form>
                </div>
                <div class="col-lg-4">
                    <h5 class="card-title mb-3">Filter Options</h5>
                    <div class="mb-3">
                        <label for="item_type" class="form-label">Item Type</label>
                        <select id="itemTypeSelect" name="item_type" class="form-select" onchange="updateItemType(this.value)">
                            <option value="all" {{ request('item_type', 'all') == 'all' ? 'selected' : '' }}>All Items</option>
                            @foreach($currentInventory as $item)
                            <option value="{{ $item->type }}" {{ request('item_type') == $item->type ? 'selected' : '' }}>
                                {{ ucfirst($item->type) }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Current Inventory Levels Table - Screen version -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">Current Inventory Levels</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Item Type</th>
                            <th>Current Quantity</th>
                            <th>Incoming (Period)</th>
                            <th>Outgoing (Period)</th>
                            <th>Last Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($currentInventory as $item)
                        <tr>
                            <td>{{ ucfirst($item->type) }}</td>
                            <td class="fw-semibold">{{ $item->quantity }}</td>
                            <td class="text-success">+{{ $itemStats[$item->type]['incoming'] }}</td>
                            <td class="text-danger">-{{ $itemStats[$item->type]['outgoing'] }}</td>
                            <td>{{ $item->updated_at->format('M d, Y h:i A') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-3">No inventory items found</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td>Totals</td>
                            <td>{{ $currentInventory->sum('quantity') }}</td>
                            <td class="text-success">+{{ $totalIncoming }}</td>
                            <td class="text-danger">-{{ $totalOutgoing }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Inventory Transactions Table - Screen version -->
    <div class="card shadow-sm no-print">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Inventory Transaction History</h5>
            <div>
                <span class="text-muted">{{ $inventoryLogs->total() }} transactions</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Date & Time</th>
                            <th>Item</th>
                            <th>Change</th>
                            <th>User</th>
                            <th>Order #</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($inventoryLogs as $log)
                        <tr>
                            <td>{{ $log->id }}</td>
                            <td>{{ $log->created_at->format('M d, Y h:i A') }}</td>
                            <td>{{ ucfirst($log->inventoryItem->type) }}</td>
                            <td class="{{ $log->quantity_change > 0 ? 'text-success fw-semibold' : 'text-danger fw-semibold' }}">
                                {{ $log->quantity_change > 0 ? '+' : '' }}{{ $log->quantity_change }}
                            </td>
                            <td>{{ $log->user->name ?? 'System' }}</td>
                            <td>
                                @if($log->order_id)
                                <a href="{{ route('orders.show', $log->order_id) }}" class="text-decoration-none">
                                    #{{ $log->order_id }}
                                </a>
                                @else
                                -
                                @endif
                            </td>
                            <td>{{ $log->notes ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-3">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="bi bi-box-seam text-muted mb-2" style="font-size: 2rem;"></i>
                                    <p class="text-muted mb-0">No inventory transactions found for the selected period</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($inventoryLogs->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center no-print">
            <div class="per-page-selector">
                <span class="me-2">Show:</span>
                <div class="btn-group btn-group-sm" role="group">
                    <a href="{{ route('reports.inventory', array_merge(request()->except('per_page', 'page'), ['per_page' => 10])) }}" 
                       class="btn {{ $perPage == 10 ? 'btn-primary' : 'btn-outline-secondary' }}">10</a>
                    <a href="{{ route('reports.inventory', array_merge(request()->except('per_page', 'page'), ['per_page' => 20])) }}" 
                       class="btn {{ $perPage == 20 ? 'btn-primary' : 'btn-outline-secondary' }}">20</a>
                    <a href="{{ route('reports.inventory', array_merge(request()->except('per_page', 'page'), ['per_page' => 50])) }}" 
                       class="btn {{ $perPage == 50 ? 'btn-primary' : 'btn-outline-secondary' }}">50</a>
                    <a href="{{ route('reports.inventory', array_merge(request()->except('per_page', 'page'), ['per_page' => 100])) }}" 
                       class="btn {{ $perPage == 100 ? 'btn-primary' : 'btn-outline-secondary' }}">100</a>
                </div>
            </div>
            <div>
                {{ $inventoryLogs->withQueryString()->links() }}
            </div>
        </div>
        @endif
    </div>

    <!-- Printable Section -->
    <div class="printable-section">
        <div class="print-header">
            <div class="company-name">MI-GAIL WATER</div>
            <div class="report-title">INVENTORY REPORT</div>
            <div class="report-period">{{ $startDate->format('M d, Y') }} to {{ $endDate->format('M d, Y') }}</div>
        </div>
    
        <!-- Current Inventory Summary - Display as a nice grid layout -->
        <div class="inventory-summary mb-4">
            <h4 class="section-title">Current Inventory Levels</h4>
            <table class="print-table">
                <thead>
                    <tr>
                        <th>Item Type</th>
                        <th>Current Quantity</th>
                        <th>Incoming</th>
                        <th>Outgoing</th>
                        <th>Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($currentInventory as $item)
                    <tr>
                        <td><strong>{{ ucfirst($item->type) }}</strong></td>
                        <td>{{ $item->quantity }}</td>
                        <td class="text-success">+{{ $itemStats[$item->type]['incoming'] }}</td>
                        <td class="text-danger">-{{ $itemStats[$item->type]['outgoing'] }}</td>
                        <td>{{ $item->updated_at->format('M d, Y h:i A') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td><strong>Totals</strong></td>
                        <td><strong>{{ $currentInventory->sum('quantity') }}</strong></td>
                        <td class="text-success"><strong>+{{ $totalIncoming }}</strong></td>
                        <td class="text-danger"><strong>-{{ $totalOutgoing }}</strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <!-- Transaction Summary -->
        <div class="transaction-summary mb-4">
            <h4 class="section-title">Transaction Summary</h4>
            <div class="d-flex justify-content-between" style="margin: 0 1rem;">
                <div class="summary-item">
                    <strong>Total Transactions:</strong> {{ $totalTransactions }}
                </div>
                <div class="summary-item">
                    <strong>Total Incoming:</strong> +{{ $totalIncoming }}
                </div>
                <div class="summary-item">
                    <strong>Total Outgoing:</strong> -{{ $totalOutgoing }}
                </div>
                <div class="summary-item">
                    <strong>Net Change:</strong> {{ $totalIncoming - $totalOutgoing }}
                </div>
            </div>
        </div>
        
        <!-- Inventory Transaction History -->
        <h4 class="section-title">Inventory Transaction History</h4>
        <table class="print-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date & Time</th>
                    <th>Item</th>
                    <th>Change</th>
                    <th>User</th>
                    <th>Order #</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($inventoryLogs as $log)
                <tr>
                    <td>{{ $log->id }}</td>
                    <td>{{ $log->created_at->format('M d, Y h:i A') }}</td>
                    <td><strong>{{ ucfirst($log->inventoryItem->type) }}</strong></td>
                    <td class="{{ $log->quantity_change > 0 ? 'text-success' : 'text-danger' }}">{{ $log->quantity_change > 0 ? '+' : '' }}{{ $log->quantity_change }}</td>
                    <td>{{ $log->user->name ?? 'System' }}</td>
                    <td>{{ $log->order_id ? "#{$log->order_id}" : '-' }}</td>
                    <td>{{ $log->notes ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="print-footer mt-4">
            <div class="row">
                <div class="col-6">
                    <p class="mb-0"><strong>Generated by:</strong> {{ Auth::user()->name ?? 'DuckworthL' }}</p>
                </div>
                <div class="col-6 text-end">
                    <p class="mb-0"><strong>Date Generated:</strong> {{ now()->format('Y-m-d H:i:s') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    window.updateItemType = function(value) {
        const url = new URL(window.location);
        url.searchParams.set('item_type', value);
        showLoading();
        window.location = url.toString();
    };
    window.showLoading = function() {
        document.getElementById('loadingOverlay').style.display = 'flex';
    };
    document.getElementById('reportForm').addEventListener('submit', function() {
        showLoading();
    });
    window.addEventListener('beforeprint', function() {
        document.querySelector('.printable-section').style.display = 'block';
    });
    window.addEventListener('afterprint', function() {
        document.querySelector('.printable-section').style.display = 'none';
    });
});
</script>
@endsection
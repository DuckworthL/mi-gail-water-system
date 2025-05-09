@extends('layouts.app')

@section('styles')
<style>
    /* Print-specific styles */
    @media print {
        /* Hide everything by default */
        body * {
            visibility: hidden;
        }
        
        /* Show only the printable section */
        .printable-section, .printable-section * {
            visibility: visible;
        }
        
        /* Position the printable section at the top of the page */
        .printable-section {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            padding: 15px;
        }
        
        /* Hide all non-printable elements */
        .no-print, .stats-card {
            display: none !important;
        }
        
        /* Format the table properly */
        .table {
            width: 100% !important;
            border-collapse: collapse !important;
            font-size: 10pt !important;
        }
        
        .table th, .table td {
            border: 1px solid #ddd !important;
            padding: 5px !important;
        }
        
        /* Remove styling that wastes ink */
        .card {
            border: none !important;
            box-shadow: none !important;
            margin: 0 !important;
        }
        
        .card-header, .card-body {
            padding: 0 !important;
        }
        
        /* Replace badge styling with plain text to save ink */
        .badge {
            background-color: transparent !important;
            color: #000 !important;
            font-weight: normal !important;
            padding: 0 !important;
        }
        
        /* Format header/footer for print */
        .print-header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .print-footer {
            margin-top: 30px;
            page-break-inside: avoid;
        }
        
        /* Hide links in print */
        a {
            text-decoration: none !important;
            color: #000 !important;
        }
    }
    
    /* Loading indicator */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(255, 255, 255, 0.8);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
    }
    
    .spinner {
        width: 50px;
        height: 50px;
        border: 4px solid var(--primary-color);
        border-radius: 50%;
        border-top: 4px solid #f3f3f3;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Enhanced buttons */
    .btn-group-report {
        margin-bottom: 15px;
    }
    
    .btn-report {
        border-radius: 20px;
        padding: 8px 16px;
        margin-right: 5px;
        font-weight: 600;
        transition: all 0.2s;
    }
    
    .btn-report:hover {
        transform: translateY(-2px);
    }
    
    .btn-report.active {
        box-shadow: 0 0 0 2px white, 0 0 0 4px var(--primary-color);
    }
    
    /* Enhanced records per page selector */
    .per-page-selector {
        display: inline-flex;
        align-items: center;
    }
    
    .per-page-selector .btn {
        border-radius: 0;
        padding: 0.25rem 0.5rem;
    }
    
    .per-page-selector .btn:first-child {
        border-top-left-radius: 0.25rem;
        border-bottom-left-radius: 0.25rem;
    }
    
    .per-page-selector .btn:last-child {
        border-top-right-radius: 0.25rem;
        border-bottom-right-radius: 0.25rem;
    }
</style>
@endsection

@section('content')
<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay no-print" style="display: none;">
    <div class="spinner mb-3"></div>
    <h5>Generating Report...</h5>
</div>

<div class="container py-4">
    <!-- Screen-only header -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <h1 class="display-6 fw-bold text-primary">
                <i class="bi bi-truck me-2"></i>Delivery Report
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
                        <a class="dropdown-item" href="{{ route('reports.delivery.export', array_merge(request()->query(), ['format' => 'excel'])) }}"
                           onclick="showLoading()">
                            <i class="bi bi-file-earmark-excel me-2"></i>Export to Excel
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('reports.delivery.export', array_merge(request()->query(), ['format' => 'csv'])) }}"
                           onclick="showLoading()">
                            <i class="bi bi-file-earmark-text me-2"></i>Export to CSV
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('reports.delivery.export', array_merge(request()->query(), ['format' => 'pdf'])) }}"
                           onclick="showLoading()">
                            <i class="bi bi-file-earmark-pdf me-2"></i>Export to PDF
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Report Type Buttons -->
    <div class="card shadow-sm mb-4 no-print">
        <div class="card-body">
            <div class="row">
                <div class="col-lg-8">
                    <h5 class="card-title mb-3">Report Period</h5>
                    <div class="btn-group-report mb-3">
                        <a href="{{ route('reports.delivery', ['period' => 'daily', 'status' => request('status', 'all')]) }}" 
                           class="btn btn-report {{ $reportPeriod == 'daily' ? 'btn-primary active' : 'btn-outline-primary' }}">
                            <i class="bi bi-calendar-day me-1"></i> Today
                        </a>
                        <a href="{{ route('reports.delivery', ['period' => 'weekly', 'status' => request('status', 'all')]) }}" 
                           class="btn btn-report {{ $reportPeriod == 'weekly' ? 'btn-primary active' : 'btn-outline-primary' }}">
                            <i class="bi bi-calendar-week me-1"></i> This Week
                        </a>
                        <a href="{{ route('reports.delivery', ['period' => 'monthly', 'status' => request('status', 'all')]) }}" 
                           class="btn btn-report {{ $reportPeriod == 'monthly' ? 'btn-primary active' : 'btn-outline-primary' }}">
                            <i class="bi bi-calendar-month me-1"></i> This Month
                        </a>
                        <a href="{{ route('reports.delivery', ['period' => 'custom', 'status' => request('status', 'all')]) }}" 
                           class="btn btn-report {{ $reportPeriod == 'custom' ? 'btn-primary active' : 'btn-outline-primary' }}">
                            <i class="bi bi-calendar-range me-1"></i> Custom Range
                        </a>
                    </div>
                    
                    <form id="reportForm" action="{{ route('reports.delivery') }}" method="GET" class="row g-3 align-items-end">
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
                        <label for="status" class="form-label">Delivery Status</label>
                        <select id="statusSelect" name="status" class="form-select" onchange="updateStatus(this.value)">
                            <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Deliveries</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="pending" {{ request('status', 'pending') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="granularity" class="form-label">Report Granularity</label>
                        <select id="granularitySelect" name="granularity" class="form-select" onchange="updateGranularity(this.value)">
                            <option value="daily" {{ $granularity == 'daily' ? 'selected' : '' }}>Daily Breakdown</option>
                            <option value="weekly" {{ $granularity == 'weekly' ? 'selected' : '' }}>Weekly Summary</option>
                            <option value="monthly" {{ $granularity == 'monthly' ? 'selected' : '' }}>Monthly Summary</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="row mb-4 no-print">
        <div class="col-md-3">
            <div class="card shadow-sm stats-card bg-white">
                <div class="stats-icon text-primary">
                    <i class="bi bi-truck"></i>
                </div>
                <h6 class="text-muted mb-2">Total Deliveries</h6>
                <h3 class="mb-0">{{ $totalDeliveries }}</h3>
                <div class="mt-2 text-muted">
                    <small>₱{{ number_format($totalDeliveryAmount, 2) }} revenue</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm stats-card bg-white">
                <div class="stats-icon text-success">
                    <i class="bi bi-check-circle"></i>
                </div>
                <h6 class="text-muted mb-2">Completed</h6>
                <h3 class="mb-0">{{ $completedDeliveries }}</h3>
                <div class="mt-2 text-muted">
                    <small>{{ $completedDeliveries > 0 ? number_format(($completedDeliveries / $totalDeliveries) * 100, 1) : 0 }}% completion rate</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm stats-card bg-white">
                <div class="stats-icon text-warning">
                    <i class="bi bi-clock-history"></i>
                </div>
                <h6 class="text-muted mb-2">Pending</h6>
                <h3 class="mb-0">{{ $pendingDeliveries }}</h3>
                <div class="mt-2 text-muted">
                    <small>{{ $pendingDeliveries > 0 ? number_format(($pendingDeliveries / $totalDeliveries) * 100, 1) : 0 }}% of total</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm stats-card bg-white">
                <div class="stats-icon text-info">
                    <i class="bi bi-droplet"></i>
                </div>
                <h6 class="text-muted mb-2">Water Delivered</h6>
                <h3 class="mb-0">{{ $totalQuantity }}</h3>
                <div class="mt-2 text-muted">
                    <small>containers</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delivery Personnel Performance -->
    <div class="card shadow-sm mb-4 no-print">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Delivery Personnel Performance</h5>
        </div>
        <div class="card-body">
            <div class="row">
                @forelse($personnelStats as $personnel)
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar-circle bg-primary me-3">
                                    {{ substr($personnel->name, 0, 1) }}
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ $personnel->name }}</h6>
                                    <small class="text-muted">Delivery Personnel</small>
                                </div>
                            </div>
                            
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="fs-4 fw-bold">{{ $personnel->total_deliveries }}</div>
                                    <small class="text-muted">Deliveries</small>
                                </div>
                                <div class="col-4">
                                    <div class="fs-4 fw-bold">{{ $personnel->completed_deliveries }}</div>
                                    <small class="text-muted">Completed</small>
                                </div>
                                <div class="col-4">
                                    <div class="fs-4 fw-bold">{{ $personnel->total_quantity }}</div>
                                    <small class="text-muted">Containers</small>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <div class="d-flex justify-content-between mb-1 small">
                                    <span>Completion Rate</span>
                                    <span>{{ $personnel->total_deliveries > 0 ? number_format(($personnel->completed_deliveries / $personnel->total_deliveries) * 100, 1) : 0 }}%</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                        style="width: {{ $personnel->total_deliveries > 0 ? ($personnel->completed_deliveries / $personnel->total_deliveries) * 100 : 0 }}%" 
                                        aria-valuenow="{{ $personnel->total_deliveries > 0 ? ($personnel->completed_deliveries / $personnel->total_deliveries) * 100 : 0 }}" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i> No delivery personnel data available for the selected period.
                    </div>
                </div>
                @endforelse
            </div>
        </div>
    </div>
    
    <!-- Inside the printable-section div -->
<div class="printable-section">
    <!-- Print Header (no changes) -->
    ...
    
    <!-- Delivery List - Making it responsive -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">Delivery Details</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Delivery Date</th>
                            <th>Personnel</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($deliveries as $delivery)
                        <tr>
                            <td>#{{ $delivery->id }}</td>
                            <td>{{ $delivery->customer->name }}</td>
                            <td>
                                @if($delivery->delivery_date)
                                    {{ Carbon\Carbon::parse($delivery->delivery_date)->format('M d, Y') }}
                                @else
                                    Pending
                                @endif
                            </td>
                            <td>{{ $delivery->deliveryPerson->name ?? 'Not Assigned' }}</td>
                            <td>{{ $delivery->quantity }}</td>
                            <td>{{ ucfirst($delivery->order_status) }}</td>
                            <td>{{ ucfirst($delivery->payment_status) }}</td>
                            <td class="text-end">₱{{ number_format($delivery->total_amount, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">No deliveries found for the selected period</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td colspan="4">Total: {{ $totalDeliveries }} deliveries</td>
                            <td>{{ $totalQuantity }} items</td>
                            <td colspan="2"></td>
                            <td class="text-end">₱{{ number_format($totalDeliveryAmount, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
        @if($deliveries->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
            <div class="per-page-selector">
                <span class="me-2">Show:</span>
                <div class="btn-group btn-group-sm" role="group">
                    <a href="{{ route('reports.delivery', array_merge(request()->except('per_page', 'page'), ['per_page' => 10])) }}" 
                       class="btn {{ $perPage == 10 ? 'btn-primary' : 'btn-outline-secondary' }}">10</a>
                    <a href="{{ route('reports.delivery', array_merge(request()->except('per_page', 'page'), ['per_page' => 20])) }}" 
                       class="btn {{ $perPage == 20 ? 'btn-primary' : 'btn-outline-secondary' }}">20</a>
                    <a href="{{ route('reports.delivery', array_merge(request()->except('per_page', 'page'), ['per_page' => 50])) }}" 
                       class="btn {{ $perPage == 50 ? 'btn-primary' : 'btn-outline-secondary' }}">50</a>
                    <a href="{{ route('reports.delivery', array_merge(request()->except('per_page', 'page'), ['per_page' => 100])) }}" 
                       class="btn {{ $perPage == 100 ? 'btn-primary' : 'btn-outline-secondary' }}">100</a>
                </div>
            </div>
            <div>
                {{ $deliveries->withQueryString()->links() }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to update status parameter
    window.updateStatus = function(value) {
        const url = new URL(window.location);
        url.searchParams.set('status', value);
        showLoading();
        window.location = url.toString();
    };
    
    // Function to update granularity parameter
    window.updateGranularity = function(value) {
        const url = new URL(window.location);
        url.searchParams.set('granularity', value);
        showLoading();
        window.location = url.toString();
    };
    
    // Show loading indicator
    window.showLoading = function() {
        document.getElementById('loadingOverlay').style.display = 'flex';
    };
    
    // Add loading indicator to the form submit
    document.getElementById('reportForm').addEventListener('submit', function() {
        showLoading();
    });
});
</script>
@endsection
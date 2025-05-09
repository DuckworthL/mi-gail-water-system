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
        .no-print, .stats-card, .chart-container {
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
    
    /* Chart enhancements */
    .chart-container {
        position: relative;
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
                <i class="bi bi-graph-up me-2"></i>Sales Report
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
                        <a class="dropdown-item" href="{{ route('reports.sales.export', array_merge(request()->query(), ['format' => 'excel'])) }}" 
                           onclick="showLoading()">
                            <i class="bi bi-file-earmark-excel me-2"></i>Export to Excel
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('reports.sales.export', array_merge(request()->query(), ['format' => 'csv'])) }}"
                           onclick="showLoading()">
                            <i class="bi bi-file-earmark-text me-2"></i>Export to CSV
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('reports.sales.export', array_merge(request()->query(), ['format' => 'pdf'])) }}"
                           onclick="showLoading()">
                            <i class="bi bi-file-earmark-pdf me-2"></i>Export to PDF
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Report filters and controls - no-print -->
    <div class="card shadow-sm mb-4 no-print">
        <div class="card-body">
            <div class="row">
                <div class="col-lg-8">
                    <h5 class="card-title mb-3">Report Period</h5>
                    <div class="btn-group-report mb-3">
                        <a href="{{ route('reports.sales', ['period' => 'daily', 'granularity' => request('granularity', 'daily')]) }}" 
                           class="btn btn-report {{ $reportPeriod == 'daily' ? 'btn-primary active' : 'btn-outline-primary' }}">
                            <i class="bi bi-calendar-day me-1"></i> Today
                        </a>
                        <a href="{{ route('reports.sales', ['period' => 'weekly', 'granularity' => request('granularity', 'daily')]) }}" 
                           class="btn btn-report {{ $reportPeriod == 'weekly' ? 'btn-primary active' : 'btn-outline-primary' }}">
                            <i class="bi bi-calendar-week me-1"></i> This Week
                        </a>
                        <a href="{{ route('reports.sales', ['period' => 'monthly', 'granularity' => request('granularity', 'daily')]) }}" 
                           class="btn btn-report {{ $reportPeriod == 'monthly' ? 'btn-primary active' : 'btn-outline-primary' }}">
                            <i class="bi bi-calendar-month me-1"></i> This Month
                        </a>
                        <a href="{{ route('reports.sales', ['period' => 'custom', 'granularity' => request('granularity', 'daily')]) }}" 
                           class="btn btn-report {{ $reportPeriod == 'custom' ? 'btn-primary active' : 'btn-outline-primary' }}">
                            <i class="bi bi-calendar-range me-1"></i> Custom Range
                        </a>
                    </div>
                    
                    <form id="reportForm" action="{{ route('reports.sales') }}" method="GET" class="row g-3 align-items-end">
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
                        <label for="filter" class="form-label">Filter By</label>
                        <select id="filterSelect" name="filter" class="form-select" onchange="updateFilter(this.value)">
                            <option value="all" {{ request('filter') == 'all' ? 'selected' : '' }}>All Orders</option>
                            <option value="paid" {{ request('filter') == 'paid' ? 'selected' : '' }}>Paid Orders</option>
                            <option value="unpaid" {{ request('filter') == 'unpaid' ? 'selected' : '' }}>Unpaid Orders</option>
                            <option value="delivery" {{ request('filter') == 'delivery' ? 'selected' : '' }}>Delivery Orders</option>
                            <option value="pickup" {{ request('filter') == 'pickup' ? 'selected' : '' }}>Pick-up Orders</option>
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
    
    <!-- Summary Stats - no-print -->
    <div class="mb-4 no-print">
        <div class="row g-4">
            <div class="col-md-3">
                <div class="card shadow-sm stats-card bg-white">
                    <div class="stats-icon text-primary">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <h6 class="text-muted mb-2">Total Sales</h6>
                    <h3 class="mb-0">₱{{ number_format($totalSales, 2) }}</h3>
                    <div class="mt-2 text-muted">
                        <small>{{ $totalOrders }} orders</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm stats-card bg-white">
                    <div class="stats-icon text-success">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <h6 class="text-muted mb-2">Paid Orders</h6>
                    <h3 class="mb-0">₱{{ number_format($paidSales, 2) }}</h3>
                    <div class="mt-2 text-muted">
                        <small>{{ $paidOrders }} orders</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm stats-card bg-white">
                    <div class="stats-icon text-warning">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <h6 class="text-muted mb-2">Unpaid Orders</h6>
                    <h3 class="mb-0">₱{{ number_format($unpaidSales, 2) }}</h3>
                    <div class="mt-2 text-muted">
                        <small>{{ $unpaidOrders }} orders</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm stats-card bg-white">
                    <div class="stats-icon text-info">
                        <i class="bi bi-droplet"></i>
                    </div>
                    <h6 class="text-muted mb-2">Water Sold</h6>
                    <h3 class="mb-0">{{ $totalQuantity }}</h3>
                    <div class="mt-2 text-muted">
                        <small>containers</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sales Chart - no-print -->
    <div class="card shadow-sm mb-4 no-print">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">Sales Trends</h5>
        </div>
        <div class="card-body">
            <div class="chart-container" style="position: relative; height:400px;">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Inside the printable-section div -->
<div class="printable-section">
    <!-- Print Header (no changes) -->
    ...

    <!-- Sales Table - This is what will print - Making it responsive -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">Sales Details</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Quantity</th>
                            <th>Type</th>
                            <th>Payment</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                        <tr>
                            <td>#{{ $order->id }}</td>
                            <td>{{ $order->created_at->format('M d, Y') }}</td>
                            <td>{{ $order->customer->name }}</td>
                            <td>{{ $order->quantity }}</td>
                            <td>{{ $order->is_delivery ? 'Delivery' : 'Pick-up' }}</td>
                            <td>{{ ucfirst($order->payment_status) }}</td>
                            <td class="text-end">₱{{ number_format($order->total_amount, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">No sales data found for the selected period</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td colspan="3">Total: {{ $totalOrders }} orders</td>
                            <td>{{ $totalQuantity }} units</td>
                            <td colspan="2"></td>
                            <td class="text-end">₱{{ number_format($totalSales, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('salesChart').getContext('2d');
    
    // Chart data from backend
    const salesData = @json($chartData);
    
    // Configure datasets with enhanced styling and tooltips
    const datasets = [
        {
            label: 'Sales (₱)',
            data: salesData.sales,
            borderColor: '#0891b2',
            backgroundColor: 'rgba(8, 145, 178, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.3,
            pointRadius: 4,
            pointHoverRadius: 6,
            pointBackgroundColor: '#0891b2',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointHitRadius: 10
        },
        {
            label: 'Quantity',
            data: salesData.quantities,
            borderColor: '#f59e0b',
            borderWidth: 2,
            borderDash: [5, 5],
            fill: false,
            tension: 0.3,
            yAxisID: 'y1',
            pointRadius: 4,
            pointHoverRadius: 6,
            pointBackgroundColor: '#f59e0b',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointHitRadius: 10
        }
    ];
    
    // Add trend lines if available
    if (salesData.salesTrend) {
        datasets.push({
            label: 'Sales Trend',
            data: salesData.salesTrend,
            borderColor: 'rgba(8, 145, 178, 0.6)',
            backgroundColor: 'transparent',
            borderWidth: 3,
            fill: false,
            tension: 0.4,
            borderDash: [],
            pointRadius: 0,
            pointHoverRadius: 0
        });
    }
    
    if (salesData.quantityTrend) {
        datasets.push({
            label: 'Quantity Trend',
            data: salesData.quantityTrend,
            borderColor: 'rgba(245, 158, 11, 0.6)',
            backgroundColor: 'transparent',
            borderWidth: 3,
            fill: false,
            tension: 0.4,
            borderDash: [],
            pointRadius: 0,
            pointHoverRadius: 0,
            yAxisID: 'y1'
        });
    }
    
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: salesData.labels,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Sales Amount (₱)',
                        font: {
                            weight: 'bold'
                        }
                    },
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        },
                        precision: 0
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Quantity',
                        font: {
                            weight: 'bold'
                        }
                    },
                    grid: {
                        drawOnChartArea: false,
                        color: 'rgba(245, 158, 11, 0.1)'
                    },
                    ticks: {
                        precision: 0
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: '{{ $granularity == "daily" ? "Date" : ($granularity == "weekly" ? "Week" : "Month") }}',
                        font: {
                            weight: 'bold'
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 10
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                    titleColor: '#333',
                    bodyColor: '#666',
                    borderColor: '#ddd',
                    borderWidth: 1,
                    padding: 10,
                    boxPadding: 5,
                    cornerRadius: 4,
                    displayColors: true,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.dataset.label === 'Sales (₱)' || context.dataset.label === 'Sales Trend') {
                                label += '₱' + context.parsed.y.toLocaleString(undefined, {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                });
                            } else {
                                label += context.parsed.y;
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
    
    // Function to update filter parameter
    window.updateFilter = function(value) {
        const url = new URL(window.location);
        url.searchParams.set('filter', value);
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
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
        
        /* Format header/footer for print */
        .print-header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .print-footer {
            margin-top: 30px;
            page-break-inside: avoid;
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
    
    .delete-btn {
        color: #dc3545 !important;
        font-weight: 600;
    }
    
    .delete-btn i {
        margin-right: 5px;
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
                <i class="bi bi-people me-2"></i>Customer Report
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
                        <a class="dropdown-item" href="{{ route('reports.customer.export', array_merge(request()->query(), ['format' => 'excel'])) }}"
                           onclick="showLoading()">
                            <i class="bi bi-file-earmark-excel me-2"></i>Export to Excel
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('reports.customer.export', array_merge(request()->query(), ['format' => 'csv'])) }}"
                           onclick="showLoading()">
                            <i class="bi bi-file-earmark-text me-2"></i>Export to CSV
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('reports.customer.export', array_merge(request()->query(), ['format' => 'pdf'])) }}"
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
                        <a href="{{ route('reports.customer', ['period' => 'daily', 'filter' => request('filter', 'all')]) }}" 
                           class="btn btn-report {{ $reportPeriod == 'daily' ? 'btn-primary active' : 'btn-outline-primary' }}">
                            <i class="bi bi-calendar-day me-1"></i> Today
                        </a>
                        <a href="{{ route('reports.customer', ['period' => 'weekly', 'filter' => request('filter', 'all')]) }}" 
                           class="btn btn-report {{ $reportPeriod == 'weekly' ? 'btn-primary active' : 'btn-outline-primary' }}">
                            <i class="bi bi-calendar-week me-1"></i> This Week
                        </a>
                        <a href="{{ route('reports.customer', ['period' => 'monthly', 'filter' => request('filter', 'all')]) }}" 
                           class="btn btn-report {{ $reportPeriod == 'monthly' ? 'btn-primary active' : 'btn-outline-primary' }}">
                            <i class="bi bi-calendar-month me-1"></i> This Month
                        </a>
                        <a href="{{ route('reports.customer', ['period' => 'custom', 'filter' => request('filter', 'all')]) }}" 
                           class="btn btn-report {{ $reportPeriod == 'custom' ? 'btn-primary active' : 'btn-outline-primary' }}">
                            <i class="bi bi-calendar-range me-1"></i> Custom Range
                        </a>
                    </div>
                    
                    <form id="reportForm" action="{{ route('reports.customer') }}" method="GET" class="row g-3 align-items-end">
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
                        <label for="filter" class="form-label">Customer Type</label>
                        <select id="filterSelect" name="filter" class="form-select" onchange="updateFilter(this.value)">
                            <option value="all" {{ request('filter') == 'all' ? 'selected' : '' }}>All Customers</option>
                            <option value="regular" {{ request('filter') == 'regular' ? 'selected' : '' }}>Regular Customers</option>
                            <option value="non-regular" {{ request('filter') == 'non-regular' ? 'selected' : '' }}>Non-Regular Customers</option>
                            <option value="top" {{ request('filter', 'top') == 'top' ? 'selected' : '' }}>Top Customers</option>
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
                    <i class="bi bi-people"></i>
                </div>
                <h6 class="text-muted mb-2">Total Customers</h6>
                <h3 class="mb-0">{{ $totalCustomers }}</h3>
                <div class="mt-2 text-muted">
                    <small>{{ $regularCustomers }} regular customers</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm stats-card bg-white">
                <div class="stats-icon text-success">
                    <i class="bi bi-cart-check"></i>
                </div>
                <h6 class="text-muted mb-2">Total Orders</h6>
                <h3 class="mb-0">{{ $totalOrders }}</h3>
                <div class="mt-2 text-muted">
                    <small>₱{{ number_format($totalSales, 2) }} revenue</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm stats-card bg-white">
                <div class="stats-icon text-warning">
                    <i class="bi bi-graph-up"></i>
                </div>
                <h6 class="text-muted mb-2">Average Order Value</h6>
                <h3 class="mb-0">₱{{ $avgOrderValue ? number_format($avgOrderValue, 2) : '0.00' }}</h3>
                <div class="mt-2 text-muted">
                    <small>per order</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm stats-card bg-white">
                <div class="stats-icon text-info">
                    <i class="bi bi-repeat"></i>
                </div>
                <h6 class="text-muted mb-2">Repeat Customer Rate</h6>
                <h3 class="mb-0">{{ $totalCustomers > 0 ? number_format(($repeatCustomers / $totalCustomers) * 100, 1) : '0.0' }}%</h3>
                <div class="mt-2 text-muted">
                    <small>{{ $repeatCustomers }} repeat customers</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Top Customers Chart -->
    <div class="card shadow-sm mb-4 no-print">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">Top Customers by Revenue</h5>
        </div>
        <div class="card-body">
            <canvas id="topCustomersChart" height="350"></canvas>
        </div>
    </div>
    
    <!-- Inside the printable-section div -->
<div class="printable-section">
    <!-- Print Header (no changes) -->
    ...
    
    <!-- Customer Table - Making it responsive -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">Customer Details</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th>Avg. Order</th>
                            <th>Last Order</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                        <tr>
                            <td>{{ $customer->name }}</td>
                            <td>{{ $customer->is_regular ? 'Regular' : 'One-time' }}</td>
                            <td>{{ $customer->orders_count }}</td>
                            <td>₱{{ number_format($customer->total_spent, 2) }}</td>
                            <td>₱{{ number_format($customer->orders_count > 0 ? $customer->total_spent / $customer->orders_count : 0, 2) }}</td>
                            <td>{{ $customer->last_order ? $customer->last_order->format('M d, Y') : 'N/A' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-3">No customers found for the selected criteria</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td colspan="2">Total: {{ $totalCustomers }} customers</td>
                            <td>{{ $totalOrders }}</td>
                            <td>₱{{ number_format($totalSales, 2) }}</td>
                            <td>₱{{ $avgOrderValue ? number_format($avgOrderValue, 2) : '0.00' }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Print Footer (no changes) -->
    ...
</div>

<!-- Customer Table (screen only) - Making it responsive -->
<div class="card shadow-sm no-print">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Customer Details</h5>
        <div>
            <span class="text-muted">
                {{ $startDate->format('M d, Y') }} to {{ $endDate->format('M d, Y') }}
            </span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Orders</th>
                        <th>Total Spent</th>
                        <th>Avg. Order</th>
                        <th>Last Order</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle bg-primary me-2">
                                    {{ substr($customer->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $customer->name }}</div>
                                    <small class="text-muted">{{ $customer->phone }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $customer->is_regular ? 'bg-success' : 'bg-secondary' }} rounded-pill">
                                {{ $customer->is_regular ? 'Regular' : 'One-time' }}
                            </span>
                        </td>
                        <td>{{ $customer->orders_count }}</td>
                        <td class="fw-semibold">₱{{ number_format($customer->total_spent, 2) }}</td>
                        <td>₱{{ number_format($customer->orders_count > 0 ? $customer->total_spent / $customer->orders_count : 0, 2) }}</td>
                        <td>{{ $customer->last_order ? $customer->last_order->format('M d, Y') : 'N/A' }}</td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('customers.show', $customer->id) }}">
                                            <i class="bi bi-eye me-2"></i>View Profile
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('customers.edit', $customer->id) }}">
                                            <i class="bi bi-pencil me-2"></i>Edit Customer
                                        </a>
                                    </li>
                                    @if($customer->orders_count == 0)
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('customers.destroy', $customer->id) }}" method="POST" class="delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item delete-btn" onclick="return confirm('Are you sure you want to delete this customer? This action cannot be undone.')">
                                                <i class="bi bi-trash text-danger me-2"></i><span class="text-danger">Delete</span>
                                            </button>
                                        </form>
                                    </li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-3">No customers found for the selected criteria</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Pagination (no changes) -->
    ...
</div>
        @if($customers->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
            <div class="per-page-selector">
                <span class="me-2">Show:</span>
                <div class="btn-group btn-group-sm" role="group">
                    <a href="{{ route('reports.customer', array_merge(request()->except('per_page', 'page'), ['per_page' => 10])) }}" 
                       class="btn {{ $perPage == 10 ? 'btn-primary' : 'btn-outline-secondary' }}">10</a>
                    <a href="{{ route('reports.customer', array_merge(request()->except('per_page', 'page'), ['per_page' => 20])) }}" 
                       class="btn {{ $perPage == 20 ? 'btn-primary' : 'btn-outline-secondary' }}">20</a>
                    <a href="{{ route('reports.customer', array_merge(request()->except('per_page', 'page'), ['per_page' => 50])) }}" 
                       class="btn {{ $perPage == 50 ? 'btn-primary' : 'btn-outline-secondary' }}">50</a>
                    <a href="{{ route('reports.customer', array_merge(request()->except('per_page', 'page'), ['per_page' => 100])) }}" 
                       class="btn {{ $perPage == 100 ? 'btn-primary' : 'btn-outline-secondary' }}">100</a>
                </div>
            </div>
            <div>
                {{ $customers->withQueryString()->links() }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('topCustomersChart');
    
    // Chart data
    const customerData = @json($chartData);
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: customerData.names,
            datasets: [
                {
                    label: 'Revenue (₱)',
                    data: customerData.revenues,
                    backgroundColor: 'rgba(8, 145, 178, 0.7)',
                    borderColor: 'rgba(8, 145, 178, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                },
                {
                    label: 'Orders',
                    data: customerData.orders,
                    backgroundColor: 'rgba(245, 158, 11, 0.7)',
                    borderColor: 'rgba(245, 158, 11, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Customers',
                        font: {
                            weight: 'bold'
                        }
                    },
                    grid: {
                        display: false
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Revenue (₱)',
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
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Number of Orders',
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
                            if (context.dataset.label === 'Revenue (₱)') {
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
@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="display-5 fw-bold text-primary">
            <i class="bi bi-truck me-2"></i>Deliveries
        </h1>
    </div>
    
    <!-- Filter Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link {{ !request('status') || request('status') == 'pending' ? 'active' : '' }}" href="{{ route('deliveries.index', ['status' => 'pending']) }}">
                Pending <span class="badge bg-warning text-dark ms-1">{{ $pendingCount }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('status') == 'completed' ? 'active' : '' }}" href="{{ route('deliveries.index', ['status' => 'completed']) }}">
                Completed
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('status') == 'all' ? 'active' : '' }}" href="{{ route('deliveries.index', ['status' => 'all']) }}">
                All Deliveries
            </a>
        </li>
    </ul>
    
    <!-- Delivery Cards View -->
    <div class="row">
        @forelse($deliveries as $delivery)
        <div class="col-md-6 col-xl-4 mb-4">
            <div class="card shadow-sm h-100 {{ $delivery->order_status == 'pending' ? 'border-warning' : '' }}">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        Order #{{ $delivery->id }}
                    </h5>
                    <span class="badge {{ $delivery->order_status == 'pending' ? 'bg-warning text-dark' : 'bg-success' }}">
                        {{ ucfirst($delivery->order_status) }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-start mb-3">
                        <div class="avatar-circle bg-primary me-3" style="flex-shrink: 0;">
                            {{ substr($delivery->customer->name, 0, 1) }}
                        </div>
                        <div>
                            <h6 class="mb-0">{{ $delivery->customer->name }}</h6>
                            <small class="text-muted">{{ $delivery->customer->phone ?? 'No phone' }}</small>
                            <p class="mb-0 text-truncate" style="max-width: 200px;" title="{{ $delivery->customer->address }}">
                                {{ $delivery->customer->address ?? 'No address' }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <small class="text-muted d-block">Quantity</small>
                            <strong>{{ $delivery->quantity }} gallon(s)</strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Total</small>
                            <strong class="text-primary">₱{{ number_format($delivery->total_amount, 2) }}</strong>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <small class="text-muted d-block">Payment Status</small>
                            <span class="badge {{ $delivery->payment_status == 'paid' ? 'bg-success' : 'bg-warning text-dark' }}">
                                {{ ucfirst($delivery->payment_status) }}
                            </span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Date Ordered</small>
                            {{ $delivery->created_at->format('M d, Y') }}
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        @if(auth()->user()->isDelivery())
                            @if($delivery->order_status == 'pending')
                                <button class="btn btn-primary w-100 complete-btn" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#completeModal" 
                                    data-order-id="{{ $delivery->id }}"
                                    data-payment-status="{{ $delivery->payment_status }}">
                                    <i class="bi bi-check2-circle me-1"></i> Mark as Delivered
                                </button>
                            @else
                                <a href="{{ route('deliveries.show', $delivery->id) }}" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-eye me-1"></i> View Details
                                </a>
                            @endif
                        @else
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="text-muted">Delivery Person:</small>
                                    <span class="ms-1">{{ $delivery->deliveryPerson->name ?? 'Not Assigned' }}</span>
                                </div>
                                <a href="{{ route('deliveries.show', $delivery->id) }}" class="btn btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i> Details
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
                @if($delivery->order_status == 'completed' && isset($delivery->delivery_date))
                <div class="card-footer bg-white">
                    <small class="text-muted">Delivered on: {{ \Carbon\Carbon::parse($delivery->delivery_date)->format('M d, Y h:i A') }}</small>
                </div>
                @endif
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="alert alert-info">
                <div class="d-flex align-items-center">
                    <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                    <div>
                        <h5 class="mb-1">No deliveries found</h5>
                        <p class="mb-0">There are currently no {{ request('status', 'pending') }} deliveries in the system.</p>
                    </div>
                </div>
            </div>
        </div>
        @endforelse
    </div>
    
    @if($deliveries->hasPages())
    <div class="mt-4">
        {{ $deliveries->withQueryString()->links() }}
    </div>
    @endif
</div>

<!-- Complete Delivery Modal -->
<div class="modal fade" id="completeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Complete Delivery</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="completeDeliveryForm" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <p>Are you sure you want to mark this delivery as completed?</p>
                    
                    <div id="paymentSection" style="display:none;">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            This order is marked as unpaid. Did you receive payment during delivery?
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="payment_received" name="payment_received" value="1">
                            <label class="form-check-label" for="payment_received">Yes, payment was received</label>
                        </div>
                        
                        <div id="paymentMethodSection" style="display:none;">
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="method_cash" value="cash" checked>
                                    <label class="form-check-label" for="method_cash">
                                        Cash
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="method_gcash" value="gcash">
                                    <label class="form-check-label" for="method_gcash">
                                        GCash
                                    </label>
                                </div>
                            </div>
                            
                            <div id="gcashReferenceSection" style="display:none;">
                                <div class="mb-3">
                                    <label for="payment_reference" class="form-label">GCash Reference Number</label>
                                    <input type="text" class="form-control" id="payment_reference" name="payment_reference">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Complete Delivery</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Modal data setup
        const completeButtons = document.querySelectorAll('.complete-btn');
        const completeForm = document.getElementById('completeDeliveryForm');
        const paymentSection = document.getElementById('paymentSection');
        const paymentReceivedCheck = document.getElementById('payment_received');
        const paymentMethodSection = document.getElementById('paymentMethodSection');
        const methodGCash = document.getElementById('method_gcash');
        const gcashReferenceSection = document.getElementById('gcashReferenceSection');
        
        completeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.getAttribute('data-order-id');
                const paymentStatus = this.getAttribute('data-payment-status');
                
                // Set form action
                completeForm.action = `/deliveries/${orderId}/complete`;
                
                // Show/hide payment section based on current payment status
                if (paymentStatus === 'unpaid') {
                    paymentSection.style.display = 'block';
                } else {
                    paymentSection.style.display = 'none';
                }
                
                // Reset form
                paymentReceivedCheck.checked = false;
                paymentMethodSection.style.display = 'none';
                document.getElementById('method_cash').checked = true;
                gcashReferenceSection.style.display = 'none';
                document.getElementById('payment_reference').value = '';
            });
        });
        
        // Toggle payment method section visibility
        paymentReceivedCheck.addEventListener('change', function() {
            paymentMethodSection.style.display = this.checked ? 'block' : 'none';
        });
        
        // Toggle GCash reference section visibility
        methodGCash.addEventListener('change', function() {
            gcashReferenceSection.style.display = this.checked ? 'block' : 'none';
        });
        
        document.getElementById('method_cash').addEventListener('change', function() {
            gcashReferenceSection.style.display = 'none';
        });
    });
</script>
@endsection
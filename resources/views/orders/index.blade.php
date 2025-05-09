@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="display-5 fw-bold text-primary">
            <i class="bi bi-clipboard-check me-2"></i>Orders
        </h1>
        <div>
            <a href="{{ route('orders.walkin') }}" class="btn btn-success me-2">
                <i class="bi bi-person-plus me-1"></i> Walk-in Sale
            </a>
            <a href="{{ route('orders.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> New Order
            </a>
        </div>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Customer</th>
                            <th>Quantity</th>
                            <th class="text-center">Delivery</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                        <tr>
                            <td>{{ $order->id }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle bg-light me-2">
                                        {{ substr($order->customer->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="fw-semibold">{{ $order->customer->name }}</div>
                                        <small class="text-muted">{{ $order->customer->phone }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $order->quantity }}</td>
                            <td class="text-center">
                                @if($order->is_delivery)
                                    <span class="badge bg-info rounded-pill">
                                        <i class="bi bi-truck me-1"></i>Yes
                                    </span>
                                @else
                                    <span class="badge bg-secondary rounded-pill">No</span>
                                @endif
                            </td>
                            <td>₱{{ number_format($order->total_amount, 2) }}</td>
                            <td>
                                @if($order->payment_status == 'paid')
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle me-1"></i>Paid
                                    </span>
                                @else
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-clock-history me-1"></i>Unpaid
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($order->order_status == 'completed')
                                    <span class="badge bg-success">Completed</span>
                                @elseif($order->order_status == 'pending')
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @else
                                    <span class="badge bg-danger">Cancelled</span>
                                @endif
                            </td>
                            <td>
                                <div>{{ $order->created_at->format('M d, Y') }}</div>
                                <small class="text-muted">{{ $order->created_at->format('h:i A') }}</small>
                            </td>
                            <td>
                                <div class="custom-dropdown">
                                    <button class="btn btn-sm btn-outline-secondary custom-dropdown-toggle" type="button">
                                        Actions
                                    </button>
                                    <div class="custom-dropdown-menu">
                                        <a class="custom-dropdown-item" href="{{ route('orders.show', $order->id) }}">
                                            <i class="bi bi-eye me-2"></i>View Details
                                        </a>
                                        @if($order->order_status == 'pending')
                                            <a class="custom-dropdown-item" href="{{ route('orders.edit', $order->id) }}">
                                                <i class="bi bi-pencil me-2"></i>Edit Order
                                            </a>
                                            @if(!$order->is_delivery && $order->payment_status == 'unpaid')
                                                <a class="custom-dropdown-item text-success" href="{{ route('orders.pay', $order->id) }}">
                                                    <i class="bi bi-cash me-2"></i>Record Payment
                                                </a>
                                            @endif
                                            <div class="custom-dropdown-divider"></div>
                                            <form action="{{ route('orders.cancel', $order->id) }}" method="POST" class="delete-form">
                                                @csrf
                                                @method('PUT')
                                                <button type="button" class="custom-dropdown-item text-danger delete-btn">
                                                    <i class="bi bi-x-circle me-2"></i>Cancel Order
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="bi bi-inbox text-muted mb-2" style="font-size: 3rem;"></i>
                                    <p class="text-muted mb-2">No orders found</p>
                                    <a href="{{ route('orders.create') }}" class="btn btn-sm btn-primary">Create First Order</a>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($orders->hasPages())
            <div class="px-4 py-3 border-top">
                {{ $orders->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
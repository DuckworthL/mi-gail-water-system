@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="display-5 fw-bold text-primary">
            <i class="bi bi-people me-2"></i>Customers
        </h1>
        <a href="{{ route('customers.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i> Add Customer
        </a>
    </div>
    
    <!-- Search and Filter -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('customers.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Name, phone, address...">
                </div>
                <div class="col-md-3">
                    <label for="filter" class="form-label">Customer Type</label>
                    <select name="filter" id="filter" class="form-select">
                        <option value="">All Customers</option>
                        <option value="regular" {{ request('filter') == 'regular' ? 'selected' : '' }}>Regular Customers</option>
                        <option value="non-regular" {{ request('filter') == 'non-regular' ? 'selected' : '' }}>Non-Regular Customers</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="sort" class="form-label">Sort By</label>
                    <select name="sort" id="sort" class="form-select">
                        <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Newest First</option>
                        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest First</option>
                        <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Name (A-Z)</option>
                        <option value="orders" {{ request('sort') == 'orders' ? 'selected' : '' }}>Most Orders</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Address</th>
                            <th class="text-center">Status</th>
                            <th>Orders</th>
                            <th class="text-center">Created</th>
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
                                    <div class="fw-semibold">{{ $customer->name }}</div>
                                </div>
                            </td>
                            <td>{{ $customer->phone }}</td>
                            <td>
                                <div class="text-truncate" style="max-width: 200px;" title="{{ $customer->address }}">
                                    {{ $customer->address }}
                                </div>
                            </td>
                            <td class="text-center">
                                @if($customer->is_regular)
                                    <span class="badge bg-success rounded-pill">Regular</span>
                                @else
                                    <span class="badge bg-secondary rounded-pill">One-time</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('customers.show', $customer->id) }}" class="text-decoration-none">
                                    {{ $customer->orders->count() }} orders
                                </a>
                            </td>
                            <td class="text-center">{{ $customer->created_at->format('M d, Y') }}</td>
                            <td>
                                <div class="custom-dropdown">
                                    <button class="btn btn-sm btn-outline-secondary custom-dropdown-toggle" type="button">
                                        Actions
                                    </button>
                                    <div class="custom-dropdown-menu">
                                        <a class="custom-dropdown-item" href="{{ route('customers.show', $customer->id) }}">
                                            <i class="bi bi-eye me-2"></i>View Profile
                                        </a>
                                        <a class="custom-dropdown-item" href="{{ route('customers.edit', $customer->id) }}">
                                            <i class="bi bi-pencil me-2"></i>Edit
                                        </a>
                                        @if($customer->orders_count == 0 || $customer->orders->count() == 0)
                                        <div class="custom-dropdown-divider"></div>
                                        <form action="{{ route('customers.destroy', $customer->id) }}" method="POST" class="delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="custom-dropdown-item text-danger delete-btn">
                                                <i class="bi bi-trash me-2"></i>Delete
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="bi bi-people text-muted mb-2" style="font-size: 3rem;"></i>
                                    <p class="text-muted mb-2">No customers found</p>
                                    <a href="{{ route('customers.create') }}" class="btn btn-sm btn-primary">Add First Customer</a>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($customers->hasPages())
            <div class="px-4 py-3 border-top">
                {{ $customers->withQueryString()->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
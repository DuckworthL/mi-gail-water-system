<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Mi-Gail Water') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">
    
    <style>
        /* Live Clock Styles */
        .navbar-clock {
            font-size: 0.85rem;
            white-space: nowrap;
            margin-right: 15px;
            line-height: 1;
            padding: 4px 10px;
            border-radius: 12px;
            background-color: rgba(255, 255, 255, 0.15);
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            color: white;
        }
        
        /* Make Reports nav item match Customers */
        .navbar-nav .nav-item.dropdown {
            display: flex;
            align-items: center;
        }
        
        .navbar-nav .nav-item.dropdown .nav-link {
            padding: 0.5rem 1rem;
            height: 100%;
            display: flex;
            align-items: center;
        }
        
        /* Consistent pagination styles */
        .pagination {
            margin-bottom: 0;
        }

        .page-item .page-link {
            color: #0d6efd;
            border-radius: 4px;
            margin: 0 2px;
        }

        .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

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
        
        @media (max-width: 991px) {
            .navbar-clock {
                margin-right: 10px;
                padding: 3px 8px;
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 767px) {
            .navbar-clock {
                display: none;
            }
        }
    </style>

    @yield('styles')
</head>
<body>
    <div id="app">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-md navbar-custom navbar-dark sticky-top">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="{{ url('/dashboard') }}">
                    <div class="water-drop me-2"></div>
                    <span>Mi-Gail Water</span>
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto">
                        @auth
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                                    <i class="bi bi-speedometer2 me-1"></i> Dashboard
                                </a>
                            </li>
                            
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}" href="{{ route('orders.index') }}">
                                    <i class="bi bi-receipt me-1"></i> Sales
                                </a>
                            </li>
                            
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('deliveries.*') ? 'active' : '' }}" href="{{ route('deliveries.index') }}">
                                    <i class="bi bi-truck me-1"></i> Deliveries
                                </a>
                            </li>
                            
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}" href="{{ route('inventory.index') }}">
                                    <i class="bi bi-box-seam me-1"></i> Inventory
                                </a>
                            </li>
                            
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}">
                                    <i class="bi bi-people me-1"></i> Customers
                                </a>
                            </li>
                            
                            @if(auth()->user()->isOwner() || auth()->user()->isDelivery() || auth()->user()->isHelper())
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-bar-chart me-1"></i> Reports
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="{{ route('reports.sales') }}">Sales Report</a></li>
                                    <li><a class="dropdown-item" href="{{ route('reports.delivery') }}">Delivery Report</a></li>
                                    <li><a class="dropdown-item" href="{{ route('reports.customer') }}">Customer Report</a></li>
                                    <li><a class="dropdown-item" href="{{ route('reports.inventory') }}">Inventory Report</a></li>
                                </ul>
                            </li>
                            @endif
                        @endauth
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto d-flex align-items-center">
                        <!-- Live Clock - Manila Time (Compact Version) -->
                        <li class="navbar-clock d-flex align-items-center">
                            <i class="bi bi-clock me-1"></i>
                            <span id="live-clock"></span>
                        </li>
                        
                        <!-- Authentication Links -->
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    <div class="avatar-circle bg-white text-primary me-2">
                                        {{ substr(Auth::user()->name, 0, 1) }}
                                    </div>
                                    <span>{{ Auth::user()->name }}</span>
                                </a>

                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="#">
                                        <i class="bi bi-person me-2"></i> My Profile
                                    </a>
                                    
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        <i class="bi bi-box-arrow-right me-2"></i> {{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Flash Messages -->
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <div class="container">
                <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <div class="container">
                <i class="bi bi-exclamation-triangle me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
        @endif

        <!-- Main Content -->
        <main>
            @yield('content')
        </main>
        
        <!-- Footer -->
        <footer class="py-3 mt-4 border-top bg-white">
            <div class="container text-center">
                <span class="text-muted">© {{ date('Y') }} Mi-Gail Water Refilling Station</span>
            </div>
        </footer>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap dropdowns
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            dropdownElementList.map(function (dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });
            
            // Handle custom dropdowns for tables
            const customDropdownToggles = document.querySelectorAll('.custom-dropdown-toggle');
            
            customDropdownToggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    
                    // Close all other open dropdowns first
                    document.querySelectorAll('.custom-dropdown-menu.show').forEach(menu => {
                        if (menu !== this.nextElementSibling) {
                            menu.classList.remove('show');
                        }
                    });
                    
                    // Toggle current dropdown
                    const menu = this.nextElementSibling;
                    menu.classList.toggle('show');
                });
            });
            
            // Close dropdowns when clicking elsewhere
            document.addEventListener('click', function(e) {
                if (!e.target.matches('.custom-dropdown-toggle') && !e.target.closest('.custom-dropdown-menu')) {
                    document.querySelectorAll('.custom-dropdown-menu.show').forEach(menu => {
                        menu.classList.remove('show');
                    });
                }
            });
            
            // Handle delete confirmations
            const deleteForms = document.querySelectorAll('.delete-form');
            if (deleteForms) {
                deleteForms.forEach(form => {
                    const deleteBtn = form.querySelector('.delete-btn');
                    if (deleteBtn) {
                        deleteBtn.addEventListener('click', function(e) {
                            e.preventDefault();
                            if (confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                                form.submit();
                            }
                        });
                    }
                });
            }
            
            // Live clock functionality - Manila Time (UTC+8) - Compact Version
            function updateClock() {
                const options = { 
                    timeZone: 'Asia/Manila',
                    hour: '2-digit', 
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: true
                };
                
                const now = new Date();
                const clockElement = document.getElementById('live-clock');
                
                if (clockElement) {
                    clockElement.textContent = now.toLocaleTimeString('en-PH', options);
                }
            }
            
            // Initialize and start clock if element exists
            if (document.getElementById('live-clock')) {
                updateClock();
                setInterval(updateClock, 1000);
            }
        });
    </script>
    
    @yield('scripts')
</body>
</html>
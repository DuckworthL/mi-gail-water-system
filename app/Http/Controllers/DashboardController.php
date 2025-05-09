<?php
// app/Http/Controllers/DashboardController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Customer;
use App\Models\InventoryItem;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    /**
     * Show the dashboard.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Check user role and redirect if not authorized
        if (auth()->user()->isDelivery()) {
            return redirect()->route('deliveries.index');
        } elseif (auth()->user()->isHelper()) {
            return redirect()->route('orders.create');
        }

        // Get sales data for today
        $today = Carbon::today();
        $todaySales = Order::whereDate('created_at', $today)->sum('total_amount');
        $todayOrders = Order::whereDate('created_at', $today)->count();
        
        // Get sales data for current week
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();
        $weeklySales = Order::whereBetween('created_at', [$startOfWeek, $endOfWeek])->sum('total_amount');
        
        // Get sales data for current month
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $monthlySales = Order::whereBetween('created_at', [$startOfMonth, $endOfMonth])->sum('total_amount');
        
        // Pending deliveries
        $pendingDeliveries = Order::where('is_delivery', true)
            ->where('order_status', 'pending')
            ->count();
        
        // Low stock items
        $lowStockItems = InventoryItem::whereRaw('quantity <= threshold')->get();
        $lowStockCount = $lowStockItems->count();
        
        // Recent orders with filtering
        $recentOrdersQuery = Order::with('customer')->orderBy('created_at', 'desc');
        
        // Apply period filter
        $orderPeriod = $request->input('order_period', 'today');
        if ($orderPeriod == 'today') {
            $recentOrdersQuery->whereDate('created_at', Carbon::today());
        } elseif ($orderPeriod == 'week') {
            $recentOrdersQuery->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        } elseif ($orderPeriod == 'month') {
            $recentOrdersQuery->whereMonth('created_at', Carbon::now()->month)
                          ->whereYear('created_at', Carbon::now()->year);
        }
        
        // Apply customer search if provided
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $recentOrdersQuery->whereHas('customer', function($query) use ($searchTerm) {
                $query->where('name', 'like', '%' . $searchTerm . '%');
            });
        }
        
        // Get per page value or default to 10
        $perPage = $request->input('per_page', 10);
        // Validate to ensure perPage is one of the allowed values
        $perPage = in_array($perPage, [10, 25, 50]) ? $perPage : 10;
        
        // Get the filtered recent orders with pagination
        $recentOrders = $recentOrdersQuery->paginate($perPage)->withQueryString();
        
        // Get total customers count
        $totalCustomers = Customer::count();
        
        return view('dashboard', compact(
            'todaySales', 
            'todayOrders', 
            'weeklySales', 
            'monthlySales', 
            'pendingDeliveries', 
            'lowStockItems',
            'lowStockCount',
            'totalCustomers',
            'recentOrders',
            'orderPeriod',
            'perPage'
        ));
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Customer;
use App\Models\User;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        // Only owner/admin can access reports
        $this->middleware('role:owner,admin');
    }
    
    /**
     * Display the sales report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function salesReport(Request $request)
    {
        // Get report period and set default to 'custom'
        $reportPeriod = $request->period ?? 'custom';
        $granularity = $request->granularity ?? 'daily';
        $perPage = in_array($request->per_page, [10, 20, 50, 100]) ? $request->per_page : 20;
        
        // Get date range
        if ($reportPeriod === 'daily') {
            $startDate = Carbon::today();
            $endDate = Carbon::today()->endOfDay();
        } elseif ($reportPeriod === 'weekly') {
            $startDate = Carbon::now()->startOfWeek();
            $endDate = Carbon::now()->endOfWeek()->endOfDay();
        } elseif ($reportPeriod === 'monthly') {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth()->endOfDay();
        } else {
            // Custom date range
            $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
            $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfDay();
        }
        
        // Base query
        $query = Order::whereBetween('created_at', [$startDate, $endDate]);
        $baseQuery = clone $query;
        
        // Apply filter
        if ($request->has('filter')) {
            if ($request->filter === 'paid') {
                $query->where('payment_status', 'paid');
            } elseif ($request->filter === 'unpaid') {
                $query->where('payment_status', 'unpaid');
            } elseif ($request->filter === 'delivery') {
                $query->where('is_delivery', true);
            } elseif ($request->filter === 'pickup') {
                $query->where('is_delivery', false);
            }
        }
        
        // Calculate totals
        $totalSales = $baseQuery->sum('total_amount');
        $totalQuantity = $baseQuery->sum('quantity');
        $totalOrders = $baseQuery->count();
        
        $paidOrders = $baseQuery->where('payment_status', 'paid')->count();
        $paidSales = $baseQuery->where('payment_status', 'paid')->sum('total_amount');
        
        $unpaidOrders = $baseQuery->where('payment_status', 'unpaid')->count();
        $unpaidSales = $baseQuery->where('payment_status', 'unpaid')->sum('total_amount');
        
        $deliveryOrders = $baseQuery->where('is_delivery', true)->count();
        $pickupOrders = $baseQuery->where('is_delivery', false)->count();
        
        // Get orders with customer info
        $orders = $query->with('customer')->orderByDesc('created_at')->paginate($perPage);
        
        // Prepare chart data based on granularity
        if ($granularity === 'daily') {
            $salesByPeriod = DB::table('orders')
                ->selectRaw('DATE(created_at) as period, SUM(total_amount) as period_sales, SUM(quantity) as period_quantity')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('period')
                ->orderBy('period')
                ->get();
        } elseif ($granularity === 'weekly') {
            $salesByPeriod = DB::table('orders')
                ->selectRaw('YEARWEEK(created_at, 1) as year_week, MIN(DATE(created_at)) as period, SUM(total_amount) as period_sales, SUM(quantity) as period_quantity')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('year_week')
                ->orderBy('year_week')
                ->get();
        } elseif ($granularity === 'monthly') {
            $salesByPeriod = DB::table('orders')
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as year_month, DATE_FORMAT(created_at, "%b %Y") as period, SUM(total_amount) as period_sales, SUM(quantity) as period_quantity')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('year_month', 'period')
                ->orderBy('year_month')
                ->get();
        }
        
        $chartData = [
            'labels' => $salesByPeriod->pluck('period')->toArray(),
            'sales' => $salesByPeriod->pluck('period_sales')->toArray(),
            'quantities' => $salesByPeriod->pluck('period_quantity')->toArray(),
        ];
        
        return view('reports.sales', compact(
            'orders', 
            'totalSales', 
            'totalQuantity', 
            'totalOrders',
            'paidOrders', 
            'paidSales', 
            'unpaidOrders', 
            'unpaidSales',
            'deliveryOrders',
            'pickupOrders', 
            'chartData',
            'startDate',
            'endDate',
            'reportPeriod',
            'granularity',
            'perPage'
        ));
    }

    /**
     * Display the delivery report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function deliveryReport(Request $request)
{
    // Get report period and set default to 'custom'
    $reportPeriod = $request->period ?? 'custom';
    $granularity = $request->granularity ?? 'daily';
    $perPage = in_array($request->per_page, [10, 20, 50, 100]) ? $request->per_page : 20;
    $filterStatus = $request->status ?? 'all'; // Add this line to define the variable
    
    // Get date range
    if ($reportPeriod === 'daily') {
        $startDate = Carbon::today();
        $endDate = Carbon::today()->endOfDay();
    } elseif ($reportPeriod === 'weekly') {
        $startDate = Carbon::now()->startOfWeek();
        $endDate = Carbon::now()->endOfWeek()->endOfDay();
    } elseif ($reportPeriod === 'monthly') {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth()->endOfDay();
    } else {
        // Custom date range
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfDay();
    }
    
    // Base query for deliveries
    $query = Order::where('is_delivery', true)
        ->whereBetween('created_at', [$startDate, $endDate]);
    $baseQuery = clone $query;
    
    // Apply status filter
    if ($request->has('status') && $request->status !== 'all') {
        $query->where('order_status', $request->status);
    }
    
    // Get totals
    $totalDeliveries = $baseQuery->count();
    $totalDeliveryAmount = $baseQuery->sum('total_amount');
    $completedDeliveries = $baseQuery->where('order_status', 'completed')->count();
    $pendingDeliveries = $baseQuery->where('order_status', 'pending')->count();
    $totalQuantity = $baseQuery->sum('quantity');
    
    // Get all delivery orders
    $deliveries = $query->with(['customer', 'deliveryPerson'])
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);
    
    // Get delivery personnel performance stats
    $personnelStats = DB::table('orders')
        ->select('users.id', 'users.name')
        ->selectRaw('COUNT(orders.id) as total_deliveries')
        ->selectRaw('SUM(CASE WHEN orders.order_status = "completed" THEN 1 ELSE 0 END) as completed_deliveries')
        ->selectRaw('SUM(orders.quantity) as total_quantity')
        ->join('users', 'orders.delivery_user_id', '=', 'users.id')
        ->where('orders.is_delivery', true)
        ->whereBetween('orders.created_at', [$startDate, $endDate])
        ->groupBy('users.id', 'users.name')
        ->having('total_deliveries', '>', 0)
        ->orderByDesc('total_deliveries')
        ->get();
    
    // Get drivers for filtering
    $drivers = User::whereIn('role', ['delivery', 'helper'])->get();
    $filterDriver = $request->driver ?? 'all'; // Add this line for driver filtering
    
    return view('reports.delivery', compact(
        'deliveries',
        'totalDeliveries',
        'totalDeliveryAmount',
        'completedDeliveries',
        'pendingDeliveries',
        'totalQuantity',
        'personnelStats',
        'startDate',
        'endDate',
        'reportPeriod',
        'granularity',
        'perPage',
        'filterStatus', // Add this variable to the compact array
        'drivers', // Add this for driver filtering
        'filterDriver' // Add this for driver filtering
    ));
}

    /**
     * Display the customer report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function customerReport(Request $request)
    {
        // Get report period and set default to 'custom'
        $reportPeriod = $request->period ?? 'custom';
        $perPage = in_array($request->per_page, [10, 20, 50, 100]) ? $request->per_page : 20;
        
        // Get date range
        if ($reportPeriod === 'daily') {
            $startDate = Carbon::today();
            $endDate = Carbon::today()->endOfDay();
        } elseif ($reportPeriod === 'weekly') {
            $startDate = Carbon::now()->startOfWeek();
            $endDate = Carbon::now()->endOfWeek()->endOfDay();
        } elseif ($reportPeriod === 'monthly') {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth()->endOfDay();
        } else {
            // Custom date range
            $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->subMonths(3);
            $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfDay();
        }
        
        // Base query
        $query = Customer::withCount(['orders' => function($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            }])
            ->withSum(['orders' => function($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            }], 'total_amount')
            ->with(['orders' => function($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate])
                  ->latest()
                  ->limit(1);
            }]);
        
        // Apply filter
        if ($request->has('filter') && $request->filter !== 'all') {
            if ($request->filter === 'regular') {
                $query->where('is_regular', true);
            } elseif ($request->filter === 'non-regular') {
                $query->where('is_regular', false);
            } elseif ($request->filter === 'top') {
                $query->has('orders', '>=', 1)
                      ->orderByDesc('orders_sum_total_amount');
            }
        } else {
            $query->orderByDesc('orders_sum_total_amount');
        }
        
        $customers = $query->paginate($perPage);
        
        // Format customer data
        foreach ($customers as $customer) {
            $customer->total_spent = $customer->orders_sum_total_amount ?? 0;
            $customer->last_order = $customer->orders->isNotEmpty() ? $customer->orders->first()->created_at : null;
            unset($customer->orders);
        }
        
        // Calculate totals
        $totalCustomers = Customer::count();
        $regularCustomers = Customer::where('is_regular', true)->count();
        
        $ordersInPeriod = Order::whereBetween('created_at', [$startDate, $endDate]);
        $totalOrders = $ordersInPeriod->count();
        $totalSales = $ordersInPeriod->sum('total_amount');
        $avgOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;
        
        // Count customers with more than one order (repeat customers)
        $repeatCustomers = DB::table('orders')
            ->select('customer_id')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('customer_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();
        
        // Prepare chart data for top customers
        $topCustomersData = DB::table('orders')
            ->join('customers', 'orders.customer_id', '=', 'customers.id')
            ->selectRaw('customers.name, SUM(orders.total_amount) as total_revenue, COUNT(orders.id) as order_count')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->groupBy('customers.name')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();
            
        $chartData = [
            'names' => $topCustomersData->pluck('name')->toArray(),
            'revenues' => $topCustomersData->pluck('total_revenue')->toArray(),
            'orders' => $topCustomersData->pluck('order_count')->toArray(),
        ];
        
        return view('reports.customer', compact(
            'customers',
            'totalCustomers',
            'regularCustomers',
            'totalOrders',
            'totalSales',
            'avgOrderValue',
            'repeatCustomers',
            'chartData',
            'startDate',
            'endDate',
            'reportPeriod',
            'perPage'
        ));
    }

    /**
     * Display the inventory report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function inventoryReport(Request $request)
    {
        // Get report period and set default to 'custom'
        $reportPeriod = $request->period ?? 'custom';
        $perPage = in_array($request->per_page, [10, 20, 50, 100]) ? $request->per_page : 20;
        
        // Get date range
        if ($reportPeriod === 'daily') {
            $startDate = Carbon::today();
            $endDate = Carbon::today()->endOfDay();
        } elseif ($reportPeriod === 'weekly') {
            $startDate = Carbon::now()->startOfWeek();
            $endDate = Carbon::now()->endOfWeek()->endOfDay();
        } elseif ($reportPeriod === 'monthly') {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth()->endOfDay();
        } else {
            // Custom date range
            $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->subMonth();
            $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfDay();
        }
        
        // Get current inventory levels
        $currentInventory = InventoryItem::all();
        
        // Get inventory logs for the period
        $inventoryLogsQuery = InventoryTransaction::whereBetween('created_at', [$startDate, $endDate]);
        
        // Apply item type filter if provided
        if ($request->has('item_type') && $request->item_type !== 'all') {
            $inventoryLogsQuery->whereHas('inventoryItem', function($q) use ($request) {
                $q->where('type', $request->item_type);
            });
        }
        
        $inventoryLogs = $inventoryLogsQuery->with(['inventoryItem', 'user', 'order'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
        
        // Calculate inventory statistics by item type
        $itemStats = [];
        foreach ($currentInventory as $item) {
            $itemStats[$item->type] = [
                'current' => $item->quantity,
                'incoming' => InventoryTransaction::whereBetween('created_at', [$startDate, $endDate])
                    ->where('inventory_item_id', $item->id)
                    ->where('quantity_change', '>', 0)
                    ->sum('quantity_change'),
                'outgoing' => InventoryTransaction::whereBetween('created_at', [$startDate, $endDate])
                    ->where('inventory_item_id', $item->id)
                    ->where('quantity_change', '<', 0)
                    ->sum(DB::raw('ABS(quantity_change)')),
                'last_updated' => $item->updated_at
            ];
        }
        
        // Calculate total transactions
        $totalTransactions = InventoryTransaction::whereBetween('created_at', [$startDate, $endDate])->count();
        $totalIncoming = InventoryTransaction::whereBetween('created_at', [$startDate, $endDate])
            ->where('quantity_change', '>', 0)
            ->sum('quantity_change');
        $totalOutgoing = InventoryTransaction::whereBetween('created_at', [$startDate, $endDate])
            ->where('quantity_change', '<', 0)
            ->sum(DB::raw('ABS(quantity_change)'));
        
        return view('reports.inventory', compact(
            'currentInventory',
            'inventoryLogs',
            'totalTransactions',
            'totalIncoming',
            'totalOutgoing',
            'itemStats',
            'startDate',
            'endDate',
            'reportPeriod',
            'perPage'
        ));
    }

    /**
     * Export sales report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportSalesReport(Request $request)
    {
        // Export logic to be implemented
        // This is a placeholder for the export functionality
        
        return redirect()->back()
            ->with('info', 'Export functionality will be implemented soon.');
    }

    /**
     * Export delivery report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportDeliveryReport(Request $request)
    {
        // Export logic to be implemented
        // This is a placeholder for the export functionality
        
        return redirect()->back()
            ->with('info', 'Export functionality will be implemented soon.');
    }

    /**
     * Export customer report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportCustomerReport(Request $request)
    {
        // Export logic to be implemented
        // This is a placeholder for the export functionality
        
        return redirect()->back()
            ->with('info', 'Export functionality will be implemented soon.');
    }

    /**
     * Export inventory report.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportInventoryReport(Request $request)
    {
        // Export logic to be implemented
        // This is a placeholder for the export functionality
        
        return redirect()->back()
            ->with('info', 'Export functionality will be implemented soon.');
    }
}
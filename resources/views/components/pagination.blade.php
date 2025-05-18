<div class="card-footer bg-white d-flex justify-content-between align-items-center">
    <div class="per-page-selector">
        <span class="me-2">Show:</span>
        <div class="btn-group btn-group-sm" role="group">
            <a href="{{ request()->fullUrlWithQuery(['per_page' => 10, 'page' => 1]) }}" 
               class="btn {{ request('per_page', 20) == 10 ? 'btn-primary' : 'btn-outline-secondary' }}">10</a>
            <a href="{{ request()->fullUrlWithQuery(['per_page' => 20, 'page' => 1]) }}" 
               class="btn {{ request('per_page', 20) == 20 ? 'btn-primary' : 'btn-outline-secondary' }}">20</a>
            <a href="{{ request()->fullUrlWithQuery(['per_page' => 50, 'page' => 1]) }}" 
               class="btn {{ request('per_page', 20) == 50 ? 'btn-primary' : 'btn-outline-secondary' }}">50</a>
            <a href="{{ request()->fullUrlWithQuery(['per_page' => 100, 'page' => 1]) }}" 
               class="btn {{ request('per_page', 20) == 100 ? 'btn-primary' : 'btn-outline-secondary' }}">100</a>
        </div>
    </div>
    <div>
        {{ $paginator->withQueryString()->links() }}
    </div>
</div>
@if (session('success'))
    <div class=" alert alert-success  alert-dismissible fade show " role="alert">
        <div class="alert-content">
            <p>{{ session('success') }}</p>
            <button type="button" class="btn-close text-capitalize" data-bs-dismiss="alert" aria-label="Close">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="svg replaced-svg">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
    </div>
@endif
@if (session('fail'))
    <div class=" alert alert-danger  alert-dismissible fade show " role="alert">
        <div class="alert-content">
            <p>{{ session('fail') }}</p>
            <button type="button" class="btn-close text-capitalize" data-bs-dismiss="alert" aria-label="Close">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="svg replaced-svg">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
    </div>
@endif

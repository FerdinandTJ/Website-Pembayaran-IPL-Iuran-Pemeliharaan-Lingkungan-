<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Dashboard</title>
    <style>
        .scrollable-table {
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/warga/dashboard') }}">Home</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/warga/dashboard') }}">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/warga/bills') }}">Bills</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/warga/invoice/history') }}">History</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/warga/profile') }}">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/logout') }}">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row mb-4">
            <!-- Top Left: Greeting and Unpaid Invoice -->
            <div class="col-md-6">
                <h3>Welcome, {{ $user->name }}!</h3>
                <p class="text-muted">Here's a summary of your dashboard.</p>

                @if($oldestUnpaidInvoice)
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Unpaid Bill</h5>
                            <p>Invoice ID: {{ $oldestUnpaidInvoice->id }}</p>
                            <p>Amount: Rp {{ number_format($oldestUnpaidInvoice->total_amount, 0, ',', '.') }}</p>
                            {{-- <p>Due Date: {{ $oldestUnpaidInvoice->created_at->format('d F Y') }}</p> --}}
                            <p>Due Date: {{ \Carbon\Carbon::parse($oldestUnpaidInvoice->created_at)->addMonth()->day(10)->format('d F Y') }}</p>
                            <a href="{{ url('/warga/invoice/' . $oldestUnpaidInvoice->id) }}" class="btn btn-primary">Pay Now</a>
                        </div>
                    </div>
                @else
                    <p class="alert alert-info">No unpaid bills at the moment.</p>
                @endif
            </div>

            <!-- Top Right: Cashflow Summary -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Cashflow Summary</h5>
                        <p>Housing: {{ $user->housing_name }}</p>
                        <p>Total Income: Rp {{ number_format($totalIncome, 0, ',', '.') }}</p>
                        <p>Total Expense: Rp {{ number_format($totalExpense, 0, ',', '.') }}</p>
                        <p>Total Amount: Rp {{ number_format($totalAmount, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Section: Cashflow History -->
        <div class="row">
            <div class="col-12">
                <h4>Cashflow History</h4>
                @if($cashflows->isEmpty())
                    <p class="alert alert-info">No cashflow records available.</p>
                @else
                    <div class="scrollable-table">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Cashflow Name</th>
                                    <th>Type</th>
                                    <th>Total Cashflow</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cashflows as $cashflow)
                                    <tr>
                                        <td>{{ $cashflow->name }}</td>
                                        {{-- <td>{{ ucfirst($cashflow->type) }}</td> --}}
                                        <td>
                                            <span class="{{ $cashflow->type === 'expense' ? 'text-danger' : 'text-success' }}">
                                                {{ ucfirst($cashflow->type) }}
                                            </span>
                                        </td>
                                        <td>Rp {{ number_format($cashflow->total_cashflow, 0, ',', '.') }}</td>
                                        <td>{{ $cashflow->created_at->format('d F Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

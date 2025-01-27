<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Pengurus Dashboard</title>
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
            <a class="navbar-brand" href="{{ url('/pengurus/dashboard') }}">Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/pengurus/dashboard') }}">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/pengurus/register') }}">Add Member</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/pengurus/invoice') }}">Create Invoice</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/pengurus/invoice/verification') }}">Verify Invoice</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/pengurus/cashflow') }}">Cashflow</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/pengurus/members') }}">List Member</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/pengurus/profile') }}">Profile</a>
                    </li>
                    <li>
                        <a class="nav-link" href="{{ url('/logout') }}">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row mb-4">
            <!-- Top Left: Greeting, Total Amount and Unpaid Invoices -->
            <div class="col-md-6">
                <h3>Welcome, {{ $user->name }}!</h3>
                <p class="text-muted">Here's a summary of your dashboard.</p>

                <!-- Total Amount Summary -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Total Amount</h5>
                        <p>Total Income: Rp {{ number_format($totalIncome, 0, ',', '.') }}</p>
                        <p>Total Expense: Rp {{ number_format($totalExpense, 0, ',', '.') }}</p>
                        <p>Net Total Amount: Rp {{ number_format($totalAmount, 0, ',', '.') }}</p>
                        <p>Number of Unpaid Invoices: {{ $unpaidCount }}</p>
                        <p>Number of Paid Invoices: {{ $paidCount }}</p>
                    </div>
                </div>
            </div>

            <!-- Top Right: 5 Oldest Invoices to Verify -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Invoices to Verify (Oldest First)</h5>
                        @if($oldestInvoices->isEmpty())
                            <p class="alert alert-info">No invoices need verification at the moment.</p>
                        @else
                            <ul>
                                @foreach($oldestInvoices as $invoice)
                                    <li>
                                        <strong>Invoice ID:</strong> {{ $invoice->id }} |
                                        <strong>Amount:</strong> Rp {{ number_format($invoice->total_amount, 0, ',', '.') }} |
                                        <a href="{{ url('/pengurus/invoice/verification') }}" class="btn btn-warning btn-sm">Verify</a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Section: Cashflow History -->
        <div class="row">
            <div class="col-12">
                <h4>Cashflow History</h4>
                @if($cashflowsHistory->isEmpty())
                    <p class="alert alert-info">No cashflow records available.</p>
                @else
                    <div class="scrollable-table">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Cashflow Name</th>
                                    <th>Type</th>
                                    <th>Total Cashflow</th>
                                    <th>Total Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cashflowsHistory as $cashflow)
                                    <tr>
                                        <td>{{ $cashflow->name }}</td>
                                        <td>
                                            <span class="{{ $cashflow->type === 'expense' ? 'text-danger' : 'text-success' }}">
                                                {{ ucfirst($cashflow->type) }}
                                            </span>
                                        </td>
                                        <td>Rp {{ number_format($cashflow->total_cashflow, 0, ',', '.') }}</td>
                                        <td>Rp {{ number_format($cashflow->calculated_total, 0, ',', '.') }}</td>  <!-- Use calculated_total -->
                                        <td>
                                            {{-- <a href="{{ url('/pengurus/cashflow/edit/' . $cashflow->id) }}" class="btn btn-primary btn-sm">Edit</a> --}}
                                            <form action="{{ url('/pengurus/cashflow/delete/' . $cashflow->id) }}" method="POST" style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
                                        </td>
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

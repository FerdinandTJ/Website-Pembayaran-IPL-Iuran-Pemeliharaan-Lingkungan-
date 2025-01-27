<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Unpaid Bills</title>
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
        <h1>Unpaid Bills</h1>

        @if($invoices->isEmpty())
            <p class="alert alert-info">You have no unpaid bills at the moment.</p>
        @else
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Invoice ID</th>
                        <th>Account Number</th>
                        <th>Total Amount</th>
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $invoice)
                        <tr>
                            <td>Tagihan #{{ $invoice->id }}</td>
                            <td>{{ $invoice->account_number }}</td>
                            <td>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                            <td>{{ $invoice->created_at->format('F Y') }}</td>
                            <td>
                                <a href="{{ url('/warga/invoice/' . $invoice->id) }}" class="btn btn-primary btn-sm">Pay</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</body>
</html>

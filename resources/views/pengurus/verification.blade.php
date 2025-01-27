<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Verify Invoices</title>
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
        <h1>Invoices Waiting for Verification</h1>

        <!-- Display success or error messages -->
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <!-- Table for invoices -->
        @if($invoices->isEmpty())
            <p class="alert alert-info">No invoices waiting for verification.</p>
        @else
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Invoice ID</th>
                        <th>Account Number</th>
                        <th>Total Amount</th>
                        <th>Proof of Payment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $invoice)
                        <tr>
                            <td>{{ $invoice->id }}</td>
                            <td>{{ $invoice->account_number }}</td>
                            <td>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                            <td>
                                @if($invoice->payment_proof)
                                    <!-- Button to trigger modal -->
                                    <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#proofModal-{{ $invoice->id }}">
                                        View Proof
                                    </button>

                                    <!-- Modal for viewing proof -->
                                    <div class="modal fade" id="proofModal-{{ $invoice->id }}" tabindex="-1" aria-labelledby="proofModalLabel-{{ $invoice->id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="proofModalLabel-{{ $invoice->id }}">Proof of Payment - Invoice #{{ $invoice->id }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body text-center">
                                                    <img src="{{ asset('storage/' . $invoice->payment_proof) }}" alt="Proof of Payment" class="img-fluid">
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">No proof uploaded</span>
                                @endif
                            </td>
                            <td>
                                <!-- Verify button -->
                                <form action="{{ url('/pengurus/invoice/verify', $invoice->id) }}" method="POST" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm">Verify</button>
                                </form>

                                <!-- Unverify button -->
                                <form action="{{ url('/pengurus/invoice/unverify', $invoice->id) }}" method="POST" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-sm">Unverify</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

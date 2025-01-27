<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>History Transactions</title>
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
        <h1>History Transactions</h1>

        @if($invoices->isEmpty())
            <p class="alert alert-info">You have no transaction bills at the moment.</p>
        @else
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Invoice ID</th>
                        <th>Account Number</th>
                        <th>Total Amount</th>
                        <th>Created At</th>
                        <th>Paid At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $invoice)
                        <tr>
                            <td>Tagihan #{{ $invoice->id }}</td>
                            <td>{{ $invoice->account_number }}</td>
                            <td>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                            <td>{{ $invoice->created_at->format('F Y') }}</td>
                            <td>{{ $invoice->updated_at->format('d F Y') }}</td>
                            <td>
                                <!-- Button to trigger modal -->
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#proofModal-{{ $invoice->id }}">
                                    View Proof
                                </button>
                            </td>
                        </tr>

                        <!-- Modal for viewing proof -->
                        <div class="modal fade" id="proofModal-{{ $invoice->id }}" tabindex="-1" aria-labelledby="proofModalLabel-{{ $invoice->id }}" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="proofModalLabel-{{ $invoice->id }}">Proof of Payment - Tagihan #{{ $invoice->id }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body text-center">
                                        @if($invoice->payment_proof)
                                            <img src="{{ asset('storage/' . $invoice->payment_proof) }}" alt="Proof of Payment" class="img-fluid">
                                        @else
                                            <p class="alert alert-warning">No proof of payment available for this invoice.</p>
                                        @endif
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

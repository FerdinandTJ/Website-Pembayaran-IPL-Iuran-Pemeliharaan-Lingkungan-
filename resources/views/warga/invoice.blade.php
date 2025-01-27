<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Invoice Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        .receipt-container {
            max-width: 600px;
            background: #fff;
            border: 1px solid #ddd;
            padding: 20px;
            margin: 50px auto;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .receipt-header h1 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        .receipt-header p {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .paid-banner {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .table {
            margin-top: 10px;
        }
        .total-row {
            font-weight: bold;
        }
        .text-end {
            text-align: right;
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
    
    <div class="receipt-container">
        <!-- Show Banner if Invoice is Paid -->
        @if($invoice->is_paid && $invoice->is_verified)
            <div class="paid-banner">
                This invoice has been paid.
            </div>
        @elseif($invoice->is_paid && !$invoice->is_verified)
            <div class="paid-banner" style="background-color: #f8d7da; color: #721c24; border-color: #f5c6cb;">
                This invoice has been paid but not yet verified.
            </div>
        @endif

        <!-- Header Section -->
        <div class="receipt-header">
            <h1>Invoice Receipt</h1>
            <p>Invoice ID: {{ $invoice->id }}</p>
        </div>

        <!-- Invoice Details -->
        <div class="mb-4">
            <p><strong>Account Number:</strong> {{ $invoice->account_number }}</p>
            <p><strong>Receiver Name:</strong> {{ $invoice->receiver_name }}</p>
            <p><strong>Created At:</strong> {{ $invoice->created_at->format('d F Y') }}</p>
        </div>

        <!-- Invoice Components -->
        <h5>Invoice Breakdown</h5>
        @if($components->isEmpty())
            <p class="alert alert-info">No components found for this invoice.</p>
        @else
            <table class="table table-borderless">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Bill Name</th>
                        <th class="text-end">Amount (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($components as $index => $component)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $component->name }}</td>
                            <td class="text-end">{{ number_format($component->amount, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="2" class="text-end">Total Amount</td>
                        <td class="text-end">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        @endif

        <!-- Payment Button -->
        <div class="text-center mt-4">
            <!-- Show disabled button if already paid -->
            @if($invoice->is_paid)
                <button type="button" class="btn btn-success" disabled>
                    Paid
                </button>
            @else
                <!-- Button to trigger the modal -->
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal">
                    Pay
                </button>
            @endif
        </div>
    </div>

    <!-- Modal for uploading proof of transfer -->
    @if(!$invoice->is_paid)
        <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ url('/warga/invoice/' . $invoice->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="paymentModalLabel">Upload Proof of Transfer</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="proof" class="form-label">Proof of Transfer</label>
                                <input type="file" class="form-control" id="proof" name="proof" accept="image/*" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Submit Payment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
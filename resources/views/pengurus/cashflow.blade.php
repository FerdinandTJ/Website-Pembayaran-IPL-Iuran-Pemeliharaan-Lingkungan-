<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Cashflow Management</title>
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
        <h1>Cashflow Management</h1>

        <!-- Success and Error Messages -->
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <!-- Add Cashflow Button -->
        <div class="text-end mb-3">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCashflowModal">Add Cashflow</button>
        </div>

        <!-- Cashflow Table -->
        @if($cashflows->isEmpty())
        <p class="alert alert-info">No cashflow records found.</p>
        @else
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
                @foreach($cashflows as $cashflow)
                    <tr>
                        <td>{{ $cashflow->name }}</td>
                        <td>{{ ucfirst($cashflow->type) }}</td>
                        <td>Rp {{ number_format($cashflow->total_cashflow, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($cashflow->calculated_total, 0, ',', '.') }}</td>
                        <td>
                            <!-- Edit Button -->
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editCashflowModal-{{ $cashflow->id }}">Edit</button>

                            <!-- Delete Button -->
                            <form action="{{ url('/pengurus/cashflow/delete', $cashflow->id) }}" method="POST" style="display:inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this cashflow?');">Delete</button>
                            </form>
                        </td>
                    </tr>

                    <!-- Edit Cashflow Modal -->
                    <div class="modal fade" id="editCashflowModal-{{ $cashflow->id }}" tabindex="-1" aria-labelledby="editCashflowLabel-{{ $cashflow->id }}" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="{{ url('/pengurus/cashflow/update', $cashflow->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editCashflowLabel-{{ $cashflow->id }}">Edit Cashflow</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label for="name-{{ $cashflow->id }}" class="form-label">Cashflow Name</label>
                                            <input type="text" class="form-control" id="name-{{ $cashflow->id }}" name="name" value="{{ $cashflow->name }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="type-{{ $cashflow->id }}" class="form-label">Type</label>
                                            <select class="form-control" id="type-{{ $cashflow->id }}" name="type" required>
                                                <option value="income" {{ $cashflow->type == 'income' ? 'selected' : '' }}>Income</option>
                                                <option value="expense" {{ $cashflow->type == 'expense' ? 'selected' : '' }}>Expense</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="total_cashflow-{{ $cashflow->id }}" class="form-label">Total Cashflow</label>
                                            <input type="number" class="form-control" id="total_cashflow-{{ $cashflow->id }}" name="total_cashflow" value="{{ $cashflow->total_cashflow }}" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <!-- Add Cashflow Modal -->
    <div class="modal fade" id="addCashflowModal" tabindex="-1" aria-labelledby="addCashflowLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ url('/pengurus/cashflow/add') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addCashflowLabel">Add Cashflow</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Cashflow Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="type" class="form-label">Type</label>
                            <select class="form-control" id="type" name="type" required>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="total_cashflow" class="form-label">Total Cashflow</label>
                            <input type="number" class="form-control" id="total_cashflow" name="total_cashflow" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Cashflow</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

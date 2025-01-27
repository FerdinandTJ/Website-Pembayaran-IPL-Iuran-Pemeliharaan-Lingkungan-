<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet" 
          integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">
    <title>Members</title>
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
        <h1 class="mb-4">Members of {{ Auth::user()->housing_name }}</h1>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Phone Number</th>
                    <th>House Address</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($members as $key => $member)
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td>{{ $member->name }}</td>
                        <td>{{ $member->username }}</td>
                        <td>{{ $member->email }}</td>
                        <td>{{ $member->phone_number }}</td>
                        <td>{{ $member->house_address }}</td>
                        <td>
                            <!-- Edit Button -->
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editMemberModal{{ $member->id }}">
                                Edit
                            </button>

                            <!-- Delete Button -->
                            <form action="{{ url('/pengurus/members/delete/' . $member->id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE') <!-- Laravel will treat this as a DELETE request -->
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editMemberModal{{ $member->id }}" tabindex="-1" aria-labelledby="editMemberModalLabel{{ $member->id }}" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editMemberModalLabel{{ $member->id }}">Edit Member: {{ $member->name }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form action="{{ url('/pengurus/members/update', $member->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')

                                        <div class="mb-3">
                                            <label for="name" class="form-label">Name</label>
                                            <input type="text" class="form-control" id="name" name="name" value="{{ $member->name }}" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="username" class="form-label">Username</label>
                                            <input type="text" class="form-control" id="username" name="username" value="{{ $member->username }}" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" value="{{ $member->email }}" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="phone_number" class="form-label">Phone Number</label>
                                            <input type="text" class="form-control" id="phone_number" name="phone_number" value="{{ $member->phone_number }}" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="house_address" class="form-label">House Address</label>
                                            <input type="text" class="form-control" id="house_address" name="house_address" value="{{ $member->house_address }}" required>
                                        </div>

                                        <button type="submit" class="btn btn-primary">Update Member</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                @empty
                    <tr>
                        <td colspan="7" class="text-center">No members found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Include Bootstrap JS (for modal functionality) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

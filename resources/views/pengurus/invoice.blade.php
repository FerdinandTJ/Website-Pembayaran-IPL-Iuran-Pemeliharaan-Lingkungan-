<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet" 
          integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">
    <title>Create Invoice</title>
    <style>
        .modal { display: none; }
        .modal.show { display: block; background-color: rgba(0, 0, 0, 0.5); }
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
        <h1 class="mb-4">Create Invoice</h1>

        <!-- Invoice Form -->
        <form id="invoice-form">
            @csrf
            <!-- Account Number -->
            <div class="mb-3">
                <label for="account_number" class="form-label">Account Number</label>
                <input type="text" name="account_number" id="account_number" class="form-control" placeholder="Enter account number" required>
            </div>

            <!-- Added Components -->
            <div class="mb-3">
                <h4>Invoice Components</h4>
                <ul id="components-list" class="list-group">
                    <!-- Components will be appended here -->
                </ul>
            </div>

            <!-- Add Component Button -->
            <div class="mb-3">
                <button type="button" class="btn btn-primary" id="add-component-btn">Add Component</button>
            </div>

            <!-- Send Broadcast Button -->
            <div class="d-grid">
                <button type="button" class="btn btn-success" id="send-broadcast-btn">Send Broadcast</button>
            </div>
        </form>
    </div>

    <!-- Modal for Adding Components -->
    <div id="add-component-modal" class="modal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Component</h5>
                    <button type="button" class="btn-close" id="close-modal-btn"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="component-name" class="form-label">Bill Name</label>
                        <input type="text" id="component-name" class="form-control" placeholder="Enter bill name" required>
                    </div>
                    <div class="mb-3">
                        <label for="component-amount" class="form-label">Amount (Rp)</label>
                        <input type="number" id="component-amount" class="form-control" placeholder="Enter amount in Rupiah" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="close-modal-btn-2">Cancel</button>
                    <button type="button" class="btn btn-primary" id="add-component-submit-btn">Add</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // References to DOM elements
        const addComponentBtn = document.getElementById('add-component-btn');
        const addComponentModal = document.getElementById('add-component-modal');
        const closeModalBtns = document.querySelectorAll('#close-modal-btn, #close-modal-btn-2');
        const addComponentSubmitBtn = document.getElementById('add-component-submit-btn');
        const componentsList = document.getElementById('components-list');
        const sendBroadcastBtn = document.getElementById('send-broadcast-btn');

        // Event listeners
        addComponentBtn.addEventListener('click', () => {
            addComponentModal.classList.add('show');
        });

        closeModalBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                addComponentModal.classList.remove('show');
            });
        });

        addComponentSubmitBtn.addEventListener('click', () => {
            const nameInput = document.getElementById('component-name');
            const amountInput = document.getElementById('component-amount');

            const billName = nameInput.value.trim();
            const amount = amountInput.value.trim();

            if (billName && amount) {
                const listItem = document.createElement('li');
                listItem.className = 'list-group-item d-flex justify-content-between align-items-center';
                listItem.innerHTML = `
                    <div>
                        <strong>${billName}</strong> - Rp ${Number(amount).toLocaleString('id-ID')}
                    </div>
                    <button type="button" class="btn btn-danger btn-sm delete-component-btn">Delete</button>
                `;

                // Add delete functionality to the button
                listItem.querySelector('.delete-component-btn').addEventListener('click', () => {
                    listItem.remove();
                });

                componentsList.appendChild(listItem);

                // Clear input fields and close the modal
                nameInput.value = '';
                amountInput.value = '';
                addComponentModal.classList.remove('show');
            } else {
                alert('Please fill in all fields.');
            }
        });

        sendBroadcastBtn.addEventListener('click', () => {
            const accountNumber = document.getElementById('account_number').value.trim();
            const components = [];

            componentsList.querySelectorAll('.list-group-item').forEach(item => {
                const component = item.querySelector('strong').textContent.trim();
                const amountText = item.querySelector('div').textContent.split(' - Rp ')[1].replaceAll('.', '');
                const amount = parseInt(amountText, 10);
                components.push({ name: component, amount });
            });

            if (accountNumber && components.length > 0) {
                // Send data to the server (placeholder for now)
                console.log('Sending Broadcast:', { accountNumber, components });

                // Example POST request to your backend
                fetch('/pengurus/broadcast', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value },
                    body: JSON.stringify({ account_number: accountNumber, components })
                })
                .then(response => response.json())
                .then(data => {
                    alert('Broadcast sent successfully!');
                    // Clear the form
                    document.getElementById('invoice-form').reset();
                    componentsList.innerHTML = '';
                })
                .catch(error => {
                    console.error('Error sending broadcast:', error);
                    alert('Failed to send broadcast.');
                });
            } else {
                alert('Please fill out the account number and add at least one component.');
            }
        });
    </script>
</body>
</html>

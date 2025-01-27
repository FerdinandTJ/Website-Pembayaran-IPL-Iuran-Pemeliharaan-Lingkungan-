<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceComponent;
use App\Models\User;
use App\Models\Cashflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PengurusController extends Controller
{
    function dashboard()
{
    $user = Auth::user();

    // Fetch the sum of income and expense for the total amount
    $cashflows = Cashflow::where('housing_name', $user->housing_name)->get();
    $totalIncome = $cashflows->where('type', 'income')->sum('total_cashflow');
    $totalExpense = $cashflows->where('type', 'expense')->sum('total_cashflow');
    $totalAmount = $totalIncome - $totalExpense;

    $unpaidInvoices = Invoice::whereHas('receiver', function($query) use ($user) {
        $query->where('housing_address', $user->housing_address);
    })
    ->where('is_paid', false)
    ->get();
    
    // Paid invoices
    $paidInvoices = Invoice::whereHas('receiver', function($query) use ($user) {
        $query->where('housing_address', $user->housing_address);
    })
    ->where('is_paid', true)
    ->get();
    
    // Count of unpaid and paid invoices
    $unpaidCount = $unpaidInvoices->count();
    $paidCount = $paidInvoices->count();

    // Fetch the 5 oldest invoices that need verification
    $oldestInvoices = Invoice::where('is_paid', true)
        ->where('is_verified', false)
        ->orderBy('created_at', 'asc')
        ->take(5)
        ->get();

    // Fetch all cashflows, sorted by date (desc) for the history
    $cashflowsHistory = Cashflow::where('housing_name', $user->housing_name)
        ->orderBy('created_at', 'desc')
        ->get();

    // Calculate the total amount dynamically (in reverse order for correct accumulation)
    $total_amount = 0;
    $reversedCashflows = $cashflowsHistory->reverse(); // Start with the earliest record

    foreach ($reversedCashflows as $cashflow) {
        if ($cashflow->type === 'income') {
            $total_amount += $cashflow->total_cashflow;
        } else {
            $total_amount -= $cashflow->total_cashflow;
        }

        // Append the calculated total amount to each cashflow for the view
        $cashflow->calculated_total = $total_amount;
    }

    // Reverse back to original order (most recent first)
    $cashflowsHistory = $reversedCashflows->reverse();

    // Pass data to the view
    return view('pengurus.dashboard', [
        'user' => $user,
        'totalIncome' => $totalIncome,
        'totalExpense' => $totalExpense,
        'totalAmount' => $totalAmount,
        'unpaidCount' => $unpaidCount,
        'paidCount' => $paidCount,
        'oldestInvoices' => $oldestInvoices,
        'cashflowsHistory' => $cashflowsHistory,
    ]);
}

    function register()
    {
        return view('pengurus.register');
    }

    // Handle the registration
    function addMember(Request $request) {
        // Validate the form inputs
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone_number' => 'required|string|max:15',
            'house_address' => 'required|string|max:255',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'warga',
            'housing_address' => Auth::user()->housing_address,
            'housing_name' => Auth::user()->housing_name,
            'phone_number' => $request->phone_number,
            'house_address' => $request->house_address,
        ]);

        return redirect('/pengurus/members')->with('success', 'Member added successfully!');
    }

    function update_member(Request $request, $id)
    {
        // Validate the form inputs
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $id,
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'phone_number' => 'required|string|max:15',
            'house_address' => 'required|string|max:255',
        ]);

        // Find the member and update
        $member = User::findOrFail($id);
        $member->update([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'house_address' => $request->house_address,
        ]);

        return redirect()->back()->with('success', 'Member updated successfully!');
    }

    function delete_member($id)
    {
        // Find the member and delete
        $member = User::findOrFail($id);
        $member->delete();

        return redirect('/pengurus/members')->with('success', 'Member added successfully!');
    }

    function view_member() {
        // Get all users where role == warga and housing_name matches the current pengurus
        $members = User::where('role', 'warga')
            ->where('housing_name', Auth::user()->housing_name)
            ->get();

        // Return the view with the members
        return view('pengurus.member', ['members' => $members]);
    }

    function create_invoice() {
        return view('pengurus.invoice');
    }

    function send_invoice(Request $request) {
        $request->validate([
            'account_number' => 'required|string|max:255',
            'components' => 'required|array',
            'components.*.name' => 'required|string|max:255',
            'components.*.amount' => 'required|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Fetch all users with role 'warga' and the current pengurus' housing_name
            $pengurus = Auth::user();
            $wargaUsers = User::where('role', 'warga')
                ->where('housing_name', $pengurus->housing_name)
                ->get();

            foreach ($wargaUsers as $warga) {
                // Calculate the total amount from the components
                $totalAmount = array_reduce($request->components, function ($carry, $component) {
                    return $carry + $component['amount'];
                }, 0);

                // Create the invoice for each warga
                $invoice = Invoice::create([
                    'account_number' => $request->account_number,
                    'total_amount' => $totalAmount,
                    'receiver_name' => $warga->name,
                    'is_paid' => false,
                ]);

                // Add invoice components
                foreach ($request->components as $component) {
                    InvoiceComponent::create([
                        'invoice_id' => $invoice->id,
                        'name' => $component['name'],
                        'amount' => $component['amount'],
                    ]);
                }
            }

            DB::commit();

            return response()->json(['message' => 'Invoices sent successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    function verify_invoice()
    {
        // Fetch all invoices that are paid but not verified
        $invoices = Invoice::where('is_paid', true)
            ->where('is_verified', false)
            ->get();

        return view('pengurus.verification', compact('invoices'));
    }

    function process_verification($id)
    {
        $invoice = Invoice::findOrFail($id);

        // Check if the invoice is already verified
        if ($invoice->is_verified) {
            return redirect()->back()->with('error', 'Invoice is already verified.');
        }

        $invoice->update(['is_verified' => true]);

        // add to cashflow
        $cashflow = Cashflow::create([
            'name' => 'Tagihan #' . $invoice->id . ' ' . $invoice->receiver_name,
            'housing_name' => Auth::user()->housing_name,
            'type' => 'income',
            'total_cashflow' => $invoice->total_amount,
        ]);

        return redirect()->back()->with('success', 'Invoice verified successfully.');
    }

    function process_unverification($id)
    {
        // Find the invoice by ID or fail if not found
        $invoice = Invoice::findOrFail($id);

        // Update the invoice to set it as unverified and unpaid
        $invoice->update([
            'is_verified' => false,
            'is_paid' => false,
            'payment_proof' => null,
        ]);

        // Redirect back with a success message
        return redirect()->back()->with('success', 'Invoice unverified successfully.');
    }

    function view_cashflow()
    {
        // Fetch all cashflows that belong to the current pengurus and order by most recent
        $cashflows = Cashflow::where('housing_name', Auth::user()->housing_name)
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate the total amount dynamically (in reverse order for correct accumulation)
        $total_amount = 0;
        $reversedCashflows = $cashflows->reverse(); // Start with the earliest record
        foreach ($reversedCashflows as $cashflow) {
            if ($cashflow->type === 'income') {
                $total_amount += $cashflow->total_cashflow;
            } else {
                $total_amount -= $cashflow->total_cashflow;
            }

            // Append the calculated total amount to each cashflow for the view
            $cashflow->calculated_total = $total_amount;
        }

        // Reverse back to original order (most recent first)
        $cashflows = $reversedCashflows->reverse();

        return view('pengurus.cashflow', compact('cashflows'));
    }

    function add_cashflow(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'total_cashflow' => 'required|numeric|min:0',
        ]);

        // Create a new cashflow record
        Cashflow::create([
            'name' => $request->name,
            'housing_name' => Auth::user()->housing_name,
            'type' => $request->type,
            'total_cashflow' => $request->total_cashflow,
        ]);

        return redirect()->back()->with('success', 'Cashflow added successfully.');
    }

    function update_cashflow(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'total_cashflow' => 'required|numeric|min:0',
        ]);

        // Find the cashflow entry and update it
        $cashflow = Cashflow::findOrFail($id);
        $cashflow->update([
            'name' => $request->name,
            'housing_name' => Auth::user()->housing_name,
            'type' => $request->type,
            'total_cashflow' => $request->total_cashflow,
        ]);

        return redirect()->back()->with('success', 'Cashflow updated successfully.');
    }

    function delete_cashflow($id)
    {
        // Find the cashflow entry and delete it
        $cashflow = Cashflow::findOrFail($id);
        $cashflow->delete();

        return redirect()->back()->with('success', 'Cashflow deleted successfully.');
    }
}

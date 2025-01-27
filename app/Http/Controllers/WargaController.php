<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\User;
use App\Models\Cashflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class WargaController extends Controller
{
    function dashboard()
    {
        $user = Auth::user();

        // Fetch the oldest unpaid bill for the user
        $oldestUnpaidInvoice = Invoice::where('receiver_name', $user->name)
            ->where('is_paid', false)
            ->orderBy('created_at', 'asc')
            ->first();

        // Fetch cashflow summary for the user's housing
        $cashflows = Cashflow::where('housing_name', $user->housing_name)->get();

        $totalIncome = $cashflows->where('type', 'income')->sum('total_cashflow');
        $totalExpense = $cashflows->where('type', 'expense')->sum('total_cashflow');
        $totalAmount = $totalIncome - $totalExpense;

        // Pass data to the view
        return view('warga.dashboard', [
            'user' => $user,
            'oldestUnpaidInvoice' => $oldestUnpaidInvoice,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'totalAmount' => $totalAmount,
            'cashflows' => $cashflows->sortByDesc('created_at') // Sort cashflow history by most recent
        ]);
    }
    
    function viewBills()
    {
        // Get the logged-in user
        $user = Auth::user();

        // Fetch only unpaid invoices where receiver_name matches the user's name
        $invoices = Invoice::where('receiver_name', $user->name)
            ->where('is_paid', false)
            ->get();

        return view('warga.bill', compact('invoices'));
    }

    function viewInvoice($id)
    {
        // Get the logged-in user
        $user = Auth::user();

        // Fetch the specific invoice
        $invoice = Invoice::where('id', $id)
            ->where('receiver_name', $user->name)
            ->firstOrFail();

        // Fetch the components associated with this invoice
        $components = $invoice->components()->get();

        return view('warga.invoice', [
            'invoice' => $invoice,
            'components' => $components,
        ]);
    }

    public function payInvoice(Request $request, $id)
    {
        // Validate the uploaded file
        $request->validate([
            'proof' => 'required|image|mimes:jpeg,png,jpg|max:2048', // Max size: 2MB
        ]);

        $invoice = Invoice::where('id', $id)
            ->where('receiver_name', Auth::user()->name)
            ->where('is_paid', false)
            ->firstOrFail();

        // Save the uploaded file
        if ($request->hasFile('proof')) {
            $fileName = time() . '_' . $request->file('proof')->getClientOriginalName();
            $filePath = $request->file('proof')->storeAs('proofs', $fileName, 'public');

            // Mark the invoice as paid
            $invoice->update([
                'is_paid' => true,
                'payment_proof' => $filePath, // Assuming you added this column in the `invoices` table
            ]);
        }

        return redirect()->back();
    }

    function invoiceHistory()
    {
        // Get the logged-in user
        $user = Auth::user();

        // Fetch all invoices where receiver_name matches the user's name
        $invoices = Invoice::where('receiver_name', $user->name)
            ->where('is_paid', true)
            ->where('is_verified', true)
            ->get();

        return view('warga.history', compact('invoices'));
    }
}

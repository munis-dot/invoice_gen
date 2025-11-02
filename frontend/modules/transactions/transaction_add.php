<?php
// UI: Transaction Add Form
require_once __DIR__ . '/../../components/header.php';
?>
<div class="container">
    <h2>Add Transaction</h2>
    <form id="transactionAddForm">
        <div class="form-group">
            <label for="customerId">Customer ID</label>
            <input type="number" name="customerId" id="customerId" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="date">Date</label>
            <input type="date" name="date" id="date" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="amount">Amount</label>
            <input type="number" step="0.01" name="amount" id="amount" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="paymentMethod">Payment Method</label>
            <select name="paymentMethod" id="paymentMethod" class="form-control" required>
                <option value="cash">Cash</option>
                <option value="card">Card</option>
                <option value="bank">Bank Transfer</option>
            </select>
        </div>
        <div class="form-group">
            <label for="invoiceNumber">Invoice Number</label>
            <input type="text" name="invoiceNumber" id="invoiceNumber" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="discount">Has Discount</label>
            <select name="discount" id="discount" class="form-control" required>
                <option value="true">Yes</option>
                <option value="false">No</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Add Transaction</button>
    </form>
    <h3>Bulk Upload (CSV / Excel)</h3>
    <form id="uploadForm" enctype="multipart/form-data">
        <div class="form-group">
            <input type="file" name="file" accept=".csv, .xlsx" required>
        </div>
        <button type="submit">Upload</button>
    </form>
    <div id="addTransactionResult"></div>
</div>

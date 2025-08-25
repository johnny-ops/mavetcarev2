<?php
// Test file to demonstrate the POS system functionality
// This file shows how the POS system works with sample data

session_name('admin_session');
session_start();

// Simulate admin session for testing
$_SESSION['user_id'] = 1;
$_SESSION['user_type'] = 'admin';
$_SESSION['user_name'] = 'Admin User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System Test - MavetCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #8BC34A; margin-bottom: 10px; }
        .feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .feature-card { background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #8BC34A; }
        .feature-card h3 { color: #333; margin-bottom: 15px; }
        .feature-card ul { margin: 0; padding-left: 20px; }
        .feature-card li { margin-bottom: 8px; color: #666; }
        .demo-section { background: #e8f5e8; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .demo-section h3 { color: #2e7d32; margin-bottom: 15px; }
        .btn { display: inline-block; padding: 12px 24px; background: #8BC34A; color: white; text-decoration: none; border-radius: 6px; margin: 10px 10px 10px 0; transition: background 0.3s; }
        .btn:hover { background: #7CB342; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        .code-block { background: #f8f9fa; padding: 15px; border-radius: 6px; border: 1px solid #e9ecef; margin: 15px 0; font-family: monospace; font-size: 14px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-cash-register"></i> MavetCare POS System</h1>
            <p>Point of Sale System for Veterinary Clinic Management</p>
        </div>

        <div class="demo-section">
            <h3><i class="fas fa-info-circle"></i> What is the POS System?</h3>
            <p>The POS (Point of Sale) system is a comprehensive sales management tool that allows staff to:</p>
            <ul>
                <li>Quickly add products and services to customer orders</li>
                <li>Calculate totals with automatic tax computation</li>
                <li>Generate professional receipts for customers</li>
                <li>Track sales and inventory in real-time</li>
                <li>Manage customer information and pet details</li>
            </ul>
        </div>

        <div class="feature-grid">
            <div class="feature-card">
                <h3><i class="fas fa-shopping-cart"></i> Product Management</h3>
                <ul>
                    <li>Select from available inventory items</li>
                    <li>Set quantities for each product</li>
                    <li>Real-time price calculation</li>
                    <li>Stock level checking</li>
                    <li>Category-based organization</li>
                </ul>
            </div>

            <div class="feature-card">
                <h3><i class="fas fa-stethoscope"></i> Service Management</h3>
                <ul>
                    <li>Add veterinary services to orders</li>
                    <li>Include consultation fees</li>
                    <li>Bundle services with products</li>
                    <li>Service category organization</li>
                    <li>Flexible pricing structure</li>
                </ul>
            </div>

            <div class="feature-card">
                <h3><i class="fas fa-user"></i> Customer Management</h3>
                <ul>
                    <li>Link sales to existing patients</li>
                    <li>Add new customer information</li>
                    <li>Track pet details and history</li>
                    <li>Customer loyalty tracking</li>
                    <li>Contact information management</li>
                </ul>
            </div>

            <div class="feature-card">
                <h3><i class="fas fa-receipt"></i> Receipt Generation</h3>
                <ul>
                    <li>Professional receipt formatting</li>
                    <li>Print-ready output</li>
                    <li>Email receipt option</li>
                    <li>Detailed item breakdown</li>
                    <li>Tax calculation display</li>
                </ul>
            </div>
        </div>

        <div class="demo-section">
            <h3><i class="fas fa-play-circle"></i> How to Use the POS System</h3>
            <ol>
                <li><strong>Open POS System:</strong> Click the "POS System" button on the sales page</li>
                <li><strong>Customer Information:</strong> Select existing customer or enter new customer details</li>
                <li><strong>Add Products:</strong> Select products from inventory and set quantities</li>
                <li><strong>Add Services:</strong> Select veterinary services and set quantities</li>
                <li><strong>Review Cart:</strong> Check items, quantities, and prices in the cart</li>
                <li><strong>Payment:</strong> Choose payment method and receipt options</li>
                <li><strong>Complete Sale:</strong> Process the transaction and generate receipt</li>
            </ol>
        </div>

        <div class="demo-section">
            <h3><i class="fas fa-code"></i> Technical Features</h3>
            <div class="code-block">
                <strong>Key Technologies Used:</strong><br>
                • PHP 8+ for backend processing<br>
                • MySQL database for data storage<br>
                • JavaScript for real-time calculations<br>
                • HTML5/CSS3 for responsive UI<br>
                • Font Awesome for icons<br>
                • Bootstrap-like responsive grid system
            </div>
        </div>

        <div class="demo-section">
            <h3><i class="fas fa-database"></i> Database Integration</h3>
            <p>The POS system integrates with:</p>
            <ul>
                <li><strong>Inventory Table:</strong> Product information, prices, and stock levels</li>
                <li><strong>Services Table:</strong> Veterinary services and pricing</li>
                <li><strong>Patients Table:</strong> Customer and pet information</li>
                <li><strong>Sales Table:</strong> Transaction records and history</li>
            </ul>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="admin/sales.php" class="btn">
                <i class="fas fa-cash-register"></i> Access POS System
            </a>
            <a href="admin/adminDashboard.php" class="btn btn-secondary">
                <i class="fas fa-tachometer-alt"></i> Back to Dashboard
            </a>
        </div>

        <div style="margin-top: 30px; padding: 20px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px;">
            <h4><i class="fas fa-lightbulb"></i> Pro Tips:</h4>
            <ul>
                <li>Use the POS system for quick transactions during busy periods</li>
                <li>Always verify customer information before processing sales</li>
                <li>Check stock levels before adding products to cart</li>
                <li>Generate receipts for all transactions for record keeping</li>
                <li>Use the clear cart function to start fresh for new customers</li>
            </ul>
        </div>
    </div>
</body>
</html>


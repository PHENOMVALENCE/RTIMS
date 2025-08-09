<?php
require_once '../auth/auth.php';
require_once '../config/database.php';

$auth = new Auth();
$auth->requireAuth('user');

$database = new Database();
$conn = $database->getConnection();

$success = '';
$error = '';

// Get incident ID from URL
$incident_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$incident_id) {
    header("Location: dashboard.php");
    exit();
}

// Get incident details
$incident_query = "SELECT i.*, o.description as offence_description, o.amount_tzs, o.keyword,
                          off.name as officer_name, off.badge_number
                   FROM incidents i
                   JOIN offences o ON i.offence_id = o.id
                   JOIN officers off ON i.officer_id = off.id
                   WHERE i.id = ? AND i.user_id = ? AND i.status = 'Pending'";

$incident_stmt = $conn->prepare($incident_query);
$incident_stmt->execute([$incident_id, $_SESSION['user_id']]);
$incident = $incident_stmt->fetch(PDO::FETCH_ASSOC);

if (!$incident) {
    header("Location: dashboard.php");
    exit();
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $payment_method = $_POST['payment_method'];
    $phone_number = isset($_POST['phone_number']) ? $_POST['phone_number'] : '';
    $reference_number = isset($_POST['reference_number']) ? $_POST['reference_number'] : '';
    
    try {
        // Simulate payment processing
        $payment_reference = 'PAY-' . date('YmdHis') . '-' . rand(1000, 9999);
        
        // In a real system, you would integrate with actual payment gateways here
        // For demo purposes, we'll simulate successful payment
        
        if ($_POST['action'] == 'simulate_payment') {
            // Update incident status to paid
            $update_query = "UPDATE incidents SET status = 'Paid' WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->execute([$incident_id]);
            
            // Log payment (in a real system, you'd have a payments table)
            $success = "Payment successful! Reference: " . $payment_reference . ". Your fine has been marked as paid.";
        } else {
            // Real payment processing would happen here
            $success = "Payment request initiated. You will receive an SMS/USSD prompt to complete the payment.";
        }
        
    } catch(PDOException $e) {
        $error = 'Payment processing error: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Fine - RTIMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .payment-method {
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            padding: 20px;
            margin: 10px 0;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .payment-method:hover {
            border-color: #667eea;
            background: #f8f9fa;
        }
        
        .payment-method.selected {
            border-color: #667eea;
            background: #e3f2fd;
        }
        
        .payment-method input[type="radio"] {
            margin-right: 10px;
        }
        
        .payment-details {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .payment-details.active {
            display: block;
        }
        
        .amount-display {
            font-size: 2rem;
            font-weight: bold;
            color: #dc3545;
            text-align: center;
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard">
            <div class="dashboard-header">
                <div>
                    <h2>💳 Pay Traffic Fine</h2>
                    <p>Control Number: <?php echo htmlspecialchars($incident['control_number']); ?></p>
                </div>
                <div class="dashboard-nav">
                    <a href="dashboard.php">Dashboard</a>
                    <a href="view_incident.php?id=<?php echo $incident_id; ?>">← Back to Incident</a>
                    <a href="../auth/logout.php">Logout</a>
                </div>
            </div>

            <div class="dashboard-content">
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="dashboard.php" class="btn btn-primary">Return to Dashboard</a>
                    </div>
                <?php else: ?>

                <div class="form-grid">
                    <!-- Payment Summary -->
                    <div>
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                            <h3>📋 Payment Summary</h3>
                            
                            <div style="display: grid; gap: 10px; margin-top: 15px;">
                                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e1e5e9;">
                                    <span><strong>Offence:</strong></span>
                                    <span><?php echo htmlspecialchars($incident['offence_description']); ?></span>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e1e5e9;">
                                    <span><strong>Control Number:</strong></span>
                                    <span style="font-family: monospace;"><?php echo htmlspecialchars($incident['control_number']); ?></span>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e1e5e9;">
                                    <span><strong>Date of Incident:</strong></span>
                                    <span><?php echo date('M j, Y', strtotime($incident['created_at'])); ?></span>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e1e5e9;">
                                    <span><strong>Recording Officer:</strong></span>
                                    <span><?php echo htmlspecialchars($incident['officer_name']); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Amount to Pay -->
                        <div class="amount-display">
                            Amount to Pay: <?php echo formatCurrency($incident['amount_tzs']); ?>
                        </div>
                    </div>

                    <!-- Payment Methods -->
                    <div>
                        <h3>💰 Select Payment Method</h3>
                        
                        <form method="POST" id="paymentForm">
                            <!-- M-Pesa -->
                            <div class="payment-method" onclick="selectPaymentMethod('mpesa')">
                                <label>
                                    <input type="radio" name="payment_method" value="mpesa" id="mpesa">
                                    <strong>📱 M-Pesa</strong><br>
                                    <small>Pay using your M-Pesa mobile money account</small>
                                </label>
                                
                                <div class="payment-details" id="mpesa-details">
                                    <div class="form-group">
                                        <label for="mpesa_phone">M-Pesa Phone Number:</label>
                                        <input type="tel" name="phone_number" id="mpesa_phone" 
                                               placeholder="255XXXXXXXXX" pattern="255[0-9]{9}">
                                        <small>Enter your M-Pesa registered phone number</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Tigo Pesa -->
                            <div class="payment-method" onclick="selectPaymentMethod('tigo')">
                                <label>
                                    <input type="radio" name="payment_method" value="tigo" id="tigo">
                                    <strong>📱 Tigo Pesa</strong><br>
                                    <small>Pay using your Tigo Pesa mobile money account</small>
                                </label>
                                
                                <div class="payment-details" id="tigo-details">
                                    <div class="form-group">
                                        <label for="tigo_phone">Tigo Pesa Phone Number:</label>
                                        <input type="tel" name="phone_number" id="tigo_phone" 
                                               placeholder="255XXXXXXXXX" pattern="255[0-9]{9}">
                                        <small>Enter your Tigo Pesa registered phone number</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Airtel Money -->
                            <div class="payment-method" onclick="selectPaymentMethod('airtel')">
                                <label>
                                    <input type="radio" name="payment_method" value="airtel" id="airtel">
                                    <strong>📱 Airtel Money</strong><br>
                                    <small>Pay using your Airtel Money account</small>
                                </label>
                                
                                <div class="payment-details" id="airtel-details">
                                    <div class="form-group">
                                        <label for="airtel_phone">Airtel Money Phone Number:</label>
                                        <input type="tel" name="phone_number" id="airtel_phone" 
                                               placeholder="255XXXXXXXXX" pattern="255[0-9]{9}">
                                        <small>Enter your Airtel Money registered phone number</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Bank Transfer -->
                            <div class="payment-method" onclick="selectPaymentMethod('bank')">
                                <label>
                                    <input type="radio" name="payment_method" value="bank" id="bank">
                                    <strong>🏦 Bank Transfer</strong><br>
                                    <small>Pay via bank transfer or online banking</small>
                                </label>
                                
                                <div class="payment-details" id="bank-details">
                                    <div style="background: #e3f2fd; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                                        <h4>Bank Details:</h4>
                                        <p><strong>Bank:</strong> Tanzania Government Revenue Account<br>
                                        <strong>Account Number:</strong> 1234567890<br>
                                        <strong>Reference:</strong> <?php echo htmlspecialchars($incident['control_number']); ?></p>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="bank_reference">Transaction Reference Number:</label>
                                        <input type="text" name="reference_number" id="bank_reference" 
                                               placeholder="Enter bank transaction reference">
                                        <small>Enter the reference number from your bank transfer</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Buttons -->
                            <div style="margin-top: 30px; display: flex; gap: 15px; justify-content: center;">
                                <button type="submit" name="action" value="process_payment" class="btn btn-success" id="payButton" disabled>
                                    💳 Process Payment
                                </button>
                                <button type="submit" name="action" value="simulate_payment" class="btn btn-primary">
                                    🧪 Simulate Payment (Demo)
                                </button>
                                <a href="view_incident.php?id=<?php echo $incident_id; ?>" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Payment Instructions -->
                <div class="alert alert-info" style="margin-top: 30px;">
                    <h4>📢 Payment Instructions</h4>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li><strong>Mobile Money:</strong> You will receive an SMS/USSD prompt to authorize the payment</li>
                        <li><strong>Bank Transfer:</strong> Use the control number as your payment reference</li>
                        <li><strong>Processing Time:</strong> Payments are usually processed within 5-10 minutes</li>
                        <li><strong>Receipt:</strong> You will receive a payment confirmation via SMS</li>
                        <li><strong>Support:</strong> For payment issues, contact +255-XXX-XXXX</li>
                    </ul>
                </div>

                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function selectPaymentMethod(method) {
            // Clear all selections
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            
            document.querySelectorAll('.payment-details').forEach(el => {
                el.classList.remove('active');
            });
            
            // Select current method
            document.getElementById(method).checked = true;
            document.querySelector(`#${method}`).closest('.payment-method').classList.add('selected');
            document.getElementById(`${method}-details`).classList.add('active');
            
            // Enable pay button
            document.getElementById('payButton').disabled = false;
        }
        
        // Form validation
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
            
            if (!selectedMethod) {
                e.preventDefault();
                alert('Please select a payment method');
                return;
            }
            
            // Validate phone number for mobile money
            if (['mpesa', 'tigo', 'airtel'].includes(selectedMethod.value)) {
                const phoneInput = document.querySelector(`#${selectedMethod.value}_phone`);
                if (!phoneInput.value) {
                    e.preventDefault();
                    alert('Please enter your phone number');
                    phoneInput.focus();
                    return;
                }
                
                if (!phoneInput.value.match(/^255[0-9]{9}$/)) {
                    e.preventDefault();
                    alert('Please enter a valid Tanzanian phone number (255XXXXXXXXX)');
                    phoneInput.focus();
                    return;
                }
            }
            
            // Validate reference for bank transfer
            if (selectedMethod.value === 'bank') {
                const refInput = document.getElementById('bank_reference');
                if (!refInput.value) {
                    e.preventDefault();
                    alert('Please enter the bank transaction reference number');
                    refInput.focus();
                    return;
                }
            }
            
            // Confirm payment
            const amount = '<?php echo formatCurrency($incident['amount_tzs']); ?>';
            if (!confirm(`Confirm payment of ${amount} using ${selectedMethod.value.toUpperCase()}?`)) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>

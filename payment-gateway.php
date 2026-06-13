<?php
include 'includes/header.php';

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$amount = isset($_GET['amount']) ? number_format((float)$_GET['amount'], 2) : '0.00';
$item = isset($_GET['item']) ? htmlspecialchars($_GET['item']) : 'Unknown Item';

if (!$order_id) { header('Location: index.php'); exit; }
?>

<div style="max-width:500px;margin:40px auto;padding:0 20px;">

    <!-- GATEWAY HEADER -->
    <div style="background:white;border-radius:12px;overflow:hidden;box-shadow:var(--shadow);">
        <div style="background:linear-gradient(135deg,#1a1a2e,#16213e);padding:25px;text-align:center;">
            <div style="color:white;font-size:0.85rem;margin-bottom:5px;opacity:0.7;">SECURE PAYMENT PORTAL</div>
            <div style="color:white;font-size:1.5rem;font-weight:800;">🔒 KasiBuys Pay</div>
            <div style="color:#e85d04;font-size:0.8rem;margin-top:5px;">256-bit SSL Encrypted</div>
        </div>

        <!-- ORDER SUMMARY -->
        <div style="padding:20px 25px;background:#f8f9fa;border-bottom:1px solid var(--border);">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <div style="font-size:0.85rem;color:var(--gray);">Paying for</div>
                    <div style="font-weight:600;"><?= $item ?></div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:0.85rem;color:var(--gray);">Amount</div>
                    <div style="font-size:1.5rem;font-weight:800;color:var(--primary);">R <?= $amount ?></div>
                </div>
            </div>
        </div>

        <div style="padding:25px;">

            <!-- BANK SELECTION -->
            <div style="margin-bottom:20px;">
                <label style="font-weight:600;display:block;margin-bottom:10px;">Select Your Bank</label>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;" id="bank-grid">
                    <?php
                    $banks = [
                        ['name' => 'FNB', 'color' => '#009a44', 'logo' => '🟢'],
                        ['name' => 'ABSA', 'color' => '#dc0028', 'logo' => '🔴'],
                        ['name' => 'Standard Bank', 'color' => '#0033a0', 'logo' => '🔵'],
                        ['name' => 'Nedbank', 'color' => '#007a4d', 'logo' => '🟩'],
                        ['name' => 'Capitec', 'color' => '#00aeef', 'logo' => '🩵'],
                        ['name' => 'Investec', 'color' => '#002d72', 'logo' => '🟦'],
                    ];
                    foreach ($banks as $bank):
                    ?>
                        <div class="bank-option" onclick="selectBank(this, '<?= $bank['name'] ?>')"
                             style="border:2px solid var(--border);border-radius:8px;padding:10px;text-align:center;cursor:pointer;transition:all 0.2s;">
                            <div style="font-size:1.5rem;"><?= $bank['logo'] ?></div>
                            <div style="font-size:0.75rem;font-weight:600;margin-top:4px;"><?= $bank['name'] ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- PAYMENT FORM -->
            <div id="payment-form" style="display:none;">
                <div style="background:#e8f5e9;border-radius:8px;padding:10px 15px;margin-bottom:20px;font-size:0.85rem;color:#2e7d32;">
                    <i class="fas fa-university"></i>
                    <span id="selected-bank-name"></span> — Online Banking Portal
                </div>

                <div class="form-group">
                    <label style="font-weight:600;font-size:0.9rem;">Account Number</label>
                    <input type="text" id="account-number" placeholder="e.g. 1234567890"
                           maxlength="10" style="width:100%;padding:12px;border:2px solid var(--border);border-radius:8px;outline:none;font-size:0.95rem;">
                </div>

                <div class="form-group">
                    <label style="font-weight:600;font-size:0.9rem;">PIN</label>
                    <input type="password" id="pin" placeholder="5-digit PIN"
                           maxlength="5" style="width:100%;padding:12px;border:2px solid var(--border);border-radius:8px;outline:none;font-size:0.95rem;">
                </div>

                <!-- SIMULATE RESULT -->
                <div style="margin-bottom:20px;">
                    <label style="font-weight:600;font-size:0.9rem;display:block;margin-bottom:8px;">
                        Simulate Payment Result
                    </label>
                    <div style="display:flex;gap:10px;">
                        <label style="flex:1;cursor:pointer;">
                            <input type="radio" name="sim_result" value="success" checked style="display:none;">
                            <div class="sim-option selected-sim"
                                 style="border:2px solid #28a745;border-radius:8px;padding:12px;text-align:center;background:#d4edda;">
                                <div style="font-size:1.5rem;">✅</div>
                                <div style="font-size:0.8rem;font-weight:600;color:#155724;">Payment Success</div>
                            </div>
                        </label>
                        <label style="flex:1;cursor:pointer;">
                            <input type="radio" name="sim_result" value="failed" style="display:none;">
                            <div class="sim-option"
                                 style="border:2px solid var(--border);border-radius:8px;padding:12px;text-align:center;">
                                <div style="font-size:1.5rem;">❌</div>
                                <div style="font-size:0.8rem;font-weight:600;color:var(--danger);">Payment Failed</div>
                            </div>
                        </label>
                        <label style="flex:1;cursor:pointer;">
                            <input type="radio" name="sim_result" value="cancelled" style="display:none;">
                            <div class="sim-option"
                                 style="border:2px solid var(--border);border-radius:8px;padding:12px;text-align:center;">
                                <div style="font-size:1.5rem;">🚫</div>
                                <div style="font-size:0.8rem;font-weight:600;color:var(--gray);">Cancelled</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- PAY BUTTON -->
                <button onclick="processPayment()" class="btn-primary"
                        style="width:100%;padding:16px;font-size:1rem;border-radius:8px;">
                    <i class="fas fa-lock"></i> Pay R <?= $amount ?> Securely
                </button>

                <div style="text-align:center;margin-top:15px;font-size:0.8rem;color:var(--gray);">
                    <i class="fas fa-shield-alt"></i> This is a simulated payment for demonstration purposes
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PROCESSING OVERLAY -->
<div id="processing-overlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);z-index:9999;justify-content:center;align-items:center;flex-direction:column;">
    <div style="background:white;border-radius:12px;padding:40px;text-align:center;max-width:300px;">
        <div style="font-size:3rem;margin-bottom:15px;" id="processing-icon">⏳</div>
        <h3 id="processing-text">Processing Payment...</h3>
        <p style="color:var(--gray);font-size:0.9rem;margin-top:10px;" id="processing-sub">Please wait while we confirm your payment</p>
        <div style="margin-top:20px;height:4px;background:var(--light);border-radius:2px;overflow:hidden;">
            <div id="progress-bar" style="height:100%;background:var(--primary);width:0%;transition:width 0.1s;border-radius:2px;"></div>
        </div>
    </div>
</div>

<script>
let selectedBank = '';
let selectedResult = 'success';

function selectBank(el, bankName) {
    document.querySelectorAll('.bank-option').forEach(b => {
        b.style.borderColor = 'var(--border)';
        b.style.background = 'white';
    });
    el.style.borderColor = 'var(--primary)';
    el.style.background = '#fff3ee';
    selectedBank = bankName;
    document.getElementById('selected-bank-name').textContent = bankName;
    document.getElementById('payment-form').style.display = 'block';
}

// Handle sim result selection
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input[name="sim_result"]').forEach(radio => {
        radio.addEventListener('change', function() {
            selectedResult = this.value;
            document.querySelectorAll('.sim-option').forEach(opt => {
                opt.style.borderColor = 'var(--border)';
                opt.style.background = 'white';
            });
            const selected = this.nextElementSibling;
            if (selectedResult === 'success') {
                selected.style.borderColor = '#28a745';
                selected.style.background = '#d4edda';
            } else if (selectedResult === 'failed') {
                selected.style.borderColor = 'var(--danger)';
                selected.style.background = '#f8d7da';
            } else {
                selected.style.borderColor = 'var(--gray)';
                selected.style.background = '#f5f5f5';
            }
        });
    });
});

function processPayment() {
    const account = document.getElementById('account-number').value;
    const pin = document.getElementById('pin').value;

    if (!selectedBank) {
        alert('Please select your bank first.');
        return;
    }
    if (account.length < 6) {
        alert('Please enter a valid account number.');
        return;
    }
    if (pin.length < 4) {
        alert('Please enter your PIN.');
        return;
    }

    // Show processing overlay
    const overlay = document.getElementById('processing-overlay');
    overlay.style.display = 'flex';

    // Animate progress bar
    let progress = 0;
    const bar = document.getElementById('progress-bar');
    const interval = setInterval(() => {
        progress += 2;
        bar.style.width = progress + '%';
        if (progress >= 100) clearInterval(interval);
    }, 50);

    // Simulate processing time
    setTimeout(() => {
        if (selectedResult === 'success') {
            document.getElementById('processing-icon').textContent = '✅';
            document.getElementById('processing-text').textContent = 'Payment Successful!';
            document.getElementById('processing-sub').textContent = 'Redirecting you back to KasiBuys...';
            setTimeout(() => {
                window.location.href = 'payment-return.php?order_id=<?= $order_id ?>&status=success&bank=' + encodeURIComponent(selectedBank);
            }, 1500);
        } else if (selectedResult === 'failed') {
            document.getElementById('processing-icon').textContent = '❌';
            document.getElementById('processing-text').textContent = 'Payment Failed';
            document.getElementById('processing-sub').textContent = 'Insufficient funds or incorrect details.';
            setTimeout(() => {
                window.location.href = 'payment-return.php?order_id=<?= $order_id ?>&status=failed';
            }, 1500);
        } else {
            document.getElementById('processing-icon').textContent = '🚫';
            document.getElementById('processing-text').textContent = 'Payment Cancelled';
            document.getElementById('processing-sub').textContent = 'You cancelled the payment.';
            setTimeout(() => {
                window.location.href = 'payment-return.php?order_id=<?= $order_id ?>&status=cancelled';
            }, 1500);
        }
    }, 3000);
}
</script>

<?php include 'includes/footer.php'; ?>
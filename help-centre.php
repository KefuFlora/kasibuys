<?php include 'includes/header.php'; ?>

<div style="max-width:800px;margin:40px auto;padding:0 20px;">
    <h2 style="margin-bottom:30px;">Help Centre</h2>

    <?php
    $faqs = [
        [
            'q' => 'How do I create a listing?',
            'a' => 'Click the "Sell" button in the top navigation bar. Fill in your item details, upload a photo, set your price and click "Post Listing".'
        ],
        [
            'q' => 'How do I buy an item?',
            'a' => 'Browse listings, click on an item you like, then click "Add to Cart" or "Buy Now". Follow the checkout process to complete your purchase.'
        ],
        [
            'q' => 'How does payment work?',
            'a' => 'KasiBuys uses a secure payment simulator for demonstration purposes. In production, payments are processed via PayFast, SnapScan or Ozow.'
        ],
        [
            'q' => 'How do I verify my identity?',
            'a' => 'Go to your Dashboard and click "Verify Identity". Enter your 13-digit South African ID number to get verified.'
        ],
        [
            'q' => 'How do I contact a seller?',
            'a' => 'Open a listing and click the "Message Seller" button. You can chat directly with the seller through KasiBuys messages.'
        ],
        [
            'q' => 'What do I do if I have a problem with an order?',
            'a' => 'Go to your Orders page, find the order and click "Report a Problem". Our admin team will investigate and resolve the dispute.'
        ],
        [
            'q' => 'How do I delete my account?',
            'a' => 'Go to Edit Profile and scroll to the bottom. Click "Delete My Account" and confirm with your password.'
        ],
        [
            'q' => 'Is my personal information safe?',
            'a' => 'Yes. KasiBuys uses industry-standard encryption and never shares your personal information with third parties.'
        ],
    ];
    ?>

    <?php foreach ($faqs as $i => $faq): ?>
        <div style="background:white;border-radius:12px;margin-bottom:12px;box-shadow:var(--shadow);overflow:hidden;">
            <div onclick="toggleFAQ(<?= $i ?>)"
                 style="padding:20px 25px;cursor:pointer;display:flex;justify-content:space-between;align-items:center;font-weight:600;">
                <?= htmlspecialchars($faq['q']) ?>
                <span id="arrow-<?= $i ?>" style="color:var(--primary);font-size:1.2rem;transition:transform 0.2s;">+</span>
            </div>
            <div id="faq-<?= $i ?>" style="display:none;padding:0 25px 20px;color:var(--gray);line-height:1.7;">
                <?= htmlspecialchars($faq['a']) ?>
            </div>
        </div>
    <?php endforeach; ?>

    <div style="background:white;border-radius:12px;padding:30px;box-shadow:var(--shadow);margin-top:30px;text-align:center;">
        <h3 style="margin-bottom:10px;">Still need help?</h3>
        <p style="color:var(--gray);margin-bottom:20px;">Our support team is ready to assist you.</p>
        <a href="contact.php" class="btn-primary">Contact Us</a>
    </div>
</div>

<script>
function toggleFAQ(i) {
    const content = document.getElementById('faq-' + i);
    const arrow = document.getElementById('arrow-' + i);
    if (content.style.display === 'none') {
        content.style.display = 'block';
        arrow.textContent = '−';
    } else {
        content.style.display = 'none';
        arrow.textContent = '+';
    }
}
</script>

<?php include 'includes/footer.php'; ?>
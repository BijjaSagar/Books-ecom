<?php 
include 'includes/header.php'; 

// --- PHP Form Handling Logic (will be added later) ---
$form_errors = [];
$form_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // For now, we'll just simulate a success message.
    // In a real application, you would validate data and send an email here.
    $form_success = "Thank you for your message! We will get back to you shortly.";
}
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container d-flex justify-content-between align-items-center">
        <h1>Contact Us</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/bookshelf/">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Contact Us</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container my-5">
    <div class="row g-5">
        <!-- Left Column: Map -->
        <div class="col-lg-6">
            <div class="ratio ratio-4x3">
                 <!-- Replace with your own Google Maps embed code -->
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d317718.69319292053!2d-0.10159865000000001!3d51.52864165!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47d8a00baf21de75%3A0x52963a5addd52a99!2sLondon%2C%20UK!5e0!3m2!1sen!2s!4v1672581696387!5m2!1sen!2s" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>

        <!-- Right Column: Contact Form -->
        <div class="col-lg-6">
            <h3 class="fw-bold mb-3">We Would Love To Hear From You.</h3>
            <p class="text-muted mb-4">Your email address will not be published. Required fields are marked *</p>

             <?php if ($form_success): ?>
                <div class="alert alert-success"><?php echo $form_success; ?></div>
            <?php else: ?>
                <form action="contact.php" method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name *</label>
                        <input type="text" class="form-control" name="name" id="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" id="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" name="message" id="message" rows="5"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
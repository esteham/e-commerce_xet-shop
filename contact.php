<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/config/classes/user.php';
require_once __DIR__ . '/config/mailer/PHPMailer.php';
require_once __DIR__ . '/config/mailer/SMTP.php';


$DB_con = new USER();

$errmsg = '';
$successmsg = '';

// Initialize variables to preserve form input
$name = $email = $subject = $message = '';
$errors = [];

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Validate inputs
    if (empty($name)) {
        $errors['name'] = 'Please enter your name.';
    }

    if (empty($email)) {
        $errors['email'] = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if (empty($subject)) {
        $errors['subject'] = 'Please select a subject.';
    }

    if (empty($message)) {
        $errors['message'] = 'Please enter your message.';
    } elseif (strlen($message) < 10) {
        $errors['message'] = 'Your message should be at least 10 characters long.';
    }

    // If no errors, process the form
    if (empty($errors)) {
        try {
            // Insert into database
            $stmt = $DB_con->runQuery("INSERT INTO contact_messages (name, email, subject, message, created_at) 
                                       VALUES (:name, :email, :subject, :message, NOW())");
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':subject' => $subject,
                ':message' => $message
            ]);

            // Send email notification using PHPMailer
            $mail = new PHPMailer(true);

            try {
                // SMTP configuration
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'deepseekspider@gmail.com';       // email address
                $mail->Password = 'rjva iybi zhra jodd';          // app password
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                // Sender and recipient
                $mail->setFrom($email, $name);
                $mail->addAddress('deepseekspider@gmail.com');  // email to receive the contact form submissions

                // Email content
                $mail->isHTML(false);
                $mail->Subject = "New Contact Form Submission: $subject";
                $mail->Body = "You have received a new message from your website contact form.\n\n" .
                              "Name: $name\n" .
                              "Email: $email\n" .
                              "Subject: $subject\n" .
                              "Message:\n$message";

                $mail->send();
                $successmsg = 'Thank you for your message! We will get back to you soon.';

                // Clear form fields after successful submission
                $name = $email = $subject = $message = '';
            } catch (Exception $e) {
                $errmsg = 'Email could not be sent. Mailer Error: ' . $mail->ErrorInfo;
            }

        } catch (PDOException $e) {
            $errmsg = 'There was an error submitting your message. Please try again later.';
        }
    } else {
        $errmsg = 'Please correct the errors in the form.';
    }
}
?>

<?php //include 'includes/header.php'; ?>
<!-- <script src="assets/js/contact.js"></script> -->
<link rel="stylesheet" href="assets/css/contact.css">

    <!-- Navigation -->
<?php include 'includes/header.php'; ?>

    <!-- Contact Header -->
    <header class="contact-header text-center">
        <div class="container">
            <h1 class="display-4 fw-bold">Contact Us</h1>
            <p class="lead">We'd love to hear from you! Reach out with any questions or feedback.</p>
        </div>
    </header>

    <!-- Main Content -->

        <div class="row g-4">
            <!-- Contact Info -->
            <div class="col-lg-4">
                <div class="card contact-card h-100">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-map-marker-alt contact-icon"></i>
                        <h3 class="p">Our Location</h3>
                        <p class="text-muted">123 E-Shop Street<br>Digital City, DC 10001</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card contact-card h-100">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-phone-alt contact-icon"></i>
                        <h3 class="p">Phone</h3>
                        <p class="text-muted">+1 (555) 123-4567<br>Mon-Fri, 9am-5pm EST</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card contact-card h-100">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-envelope contact-icon"></i>
                        <h3 class="p">Email</h3>
                        <p class="text-muted">support@modern-eshop.com<br>sales@modern-eshop.com</p>
                    </div>
                </div>
            </div>
        </div>

            <!-- Contact Form -->
<main class="container mb-5 pt-4 d-flex justify-content-center">
    <div class="col-lg-6">
        <div class="card contact-card">
            <div class="card-body p-4">
                <h2 class="h3 mb-4">Send us a message</h2>
                
                <?php if ($errmsg): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($errmsg); ?></div>
                <?php endif; ?>
                
                <?php if ($successmsg): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($successmsg); ?></div>
                <?php endif; ?>
                
                <form id="contactForm" action="" method="POST" novalidate>
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" 
                            id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['name']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                            id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['email']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label> &nbsp;
                        <select class="form-select <?php echo isset($errors['subject']) ? 'is-invalid' : ''; ?> shadow-sm lg rounded" 
                                id="subject" name="subject" required>
                            <option value="" selected disabled>Select a subject</option>
                            <option value="General Inquiry" <?php echo $subject === 'General Inquiry' ? 'selected' : ''; ?>>General Inquiry</option>
                            <option value="Order Support" <?php echo $subject === 'Order Support' ? 'selected' : ''; ?>>Order Support</option>
                            <option value="Returns & Refunds" <?php echo $subject === 'Returns & Refunds' ? 'selected' : ''; ?>>Returns & Refunds</option>
                            <option value="Technical Support" <?php echo $subject === 'Technical Support' ? 'selected' : ''; ?>>Technical Support</option>
                            <option value="Other" <?php echo $subject === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                        <?php if (isset($errors['subject'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['subject']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control <?php echo isset($errors['message']) ? 'is-invalid' : ''; ?>" 
                                id="message" name="message" rows="5" required><?php echo htmlspecialchars($message); ?></textarea>
                        <?php if (isset($errors['message'])): ?>
                            <div class="invalid-feedback"><?php echo htmlspecialchars($errors['message']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Send Message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

  <!-- Map and FAQ -->
    <div class="col-lg-6">
                <div class="map-container mb-4">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3022.215373510548!2d-73.987844924164!3d40.74844047138971!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c259a9b3117469%3A0xd134e199a405a163!2sEmpire%20State%20Building!5e0!3m2!1sen!2sus!4v1689876723456!5m2!1sen!2sus" 
                            width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
                
                <div class="card contact-card">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-4">Frequently Asked Questions</h2>
                        <div class="accordion" id="faqAccordion">
                            <div class="accordion-item">
                                <p class="accordion-header " id="headingOne">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                                        How can I track my order?
                                    </button>
                                </p>
                                <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        You can track your order by logging into your account and viewing your order history. You'll receive tracking information via email once your order ships.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <p class="accordion-header" id="headingTwo">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                                        What is your return policy?
                                    </button>
                                </p>
                                <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        We accept returns within 30 days of purchase. Items must be unused and in their original packaging. Please contact our support team to initiate a return.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <p class="accordion-header" id="headingThree">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                                        Do you offer international shipping?
                                    </button>
                                </p>
                                <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Yes, we ship to most countries worldwide. Shipping costs and delivery times vary depending on the destination. You'll see the options at checkout.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</main>

    <!-- Footer -->
<?php include 'includes/footer.php'; ?>
<?php
require_once __DIR__ . '/config/classes/user.php';
//-- Navigation --
include 'includes/header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - E-Shop</title>
    
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #3f37c9;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
        }
        
        .about-hero {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 5rem 0;
            margin-bottom: 3rem;
            text-align: center;
        }
        
        .about-hero h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .about-hero p {
            font-size: 1.25rem;
            max-width: 800px;
            margin: 0 auto;
            opacity: 0.9;
        }
        
        .about-section {
            padding: 4rem 0;
        }
        
        .about-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            padding: 2.5rem;
            margin-bottom: 2rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .about-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .about-card h2 {
            color: var(--primary);
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        
        .about-card p {
            color: var(--gray);
            line-height: 1.7;
            font-size: 1.1rem;
        }
        
        .stats-container {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            margin: 3rem 0;
        }
        
        .stat-item {
            text-align: center;
            padding: 1.5rem;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--gray);
            font-size: 1.1rem;
        }
        
        .team-section {
            background-color: #f8f9fa;
            padding: 4rem 0;
        }
        
        .team-title {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 2rem;
        }
        
        .team-member {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }
        
        .team-member:hover {
            transform: translateY(-10px);
        }
        
        .team-img {
            height: 250px;
            width: 100%;
            object-fit: cover;
        }
        
        .team-info {
            padding: 1.5rem;
            text-align: center;
        }
        
        .team-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        
        .team-role {
            color: var(--primary);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .about-hero h1 {
                font-size: 2.5rem;
            }
            
            .stats-container {
                flex-direction: column;
            }
            
            .stat-item {
                margin-bottom: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="about-hero">
        <div class="container">
            <h1>Redefining Online Shopping</h1>
            <p>At E-Shop, we're more than just an e-commerce platform - we're a community passionate about delivering exceptional experiences through innovative technology and customer-centric values.</p>
        </div>
    </section>

    <div class="container">
        <!-- Stats Section -->
        <div class="stats-container">
            <div class="stat-item">
                <div class="stat-number">10M+</div>
                <div class="stat-label">Happy Customers</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">500K+</div>
                <div class="stat-label">Products Available</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">24/7</div>
                <div class="stat-label">Customer Support</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">100%</div>
                <div class="stat-label">Secure Payments</div>
            </div>
        </div>

        <!-- About Content -->
        <div class="about-section">
            <div class="row">
                <div class="col-lg-6">
                    <div class="about-card">
                        <h2>Our Story</h2>
                        <p>Founded in 2015, E-Shop began as a small startup with a big vision - to transform the way people shop online. What started as a modest platform with just a few products has grown into one of the most trusted e-commerce destinations, serving millions of customers worldwide.</p>
                        <p>Our journey has been fueled by innovation, customer feedback, and an unwavering commitment to quality. Every day, we strive to push boundaries and set new standards in online retail.</p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="about-card">
                        <h2>Our Mission</h2>
                        <p>To create a seamless, personalized shopping experience that connects people with products they'll love, while maintaining the highest standards of convenience, security, and customer service.</p>
                        <p>We believe shopping should be effortless, enjoyable, and accessible to everyone, regardless of location or technical expertise.</p>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-6">
                    <div class="about-card">
                        <h2>Our Technology</h2>
                        <p>Powered by cutting-edge AI and machine learning, our platform offers personalized recommendations, smart search, and predictive analytics to make your shopping experience intuitive and efficient.</p>
                        <p>Our robust infrastructure ensures fast loading times, secure transactions, and 99.9% uptime so you can shop with confidence anytime, anywhere.</p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="about-card">
                        <h2>Sustainability</h2>
                        <p>We're committed to reducing our environmental impact through eco-friendly packaging, carbon-neutral shipping options, and partnerships with sustainable brands.</p>
                        <p>Our goal is to achieve net-zero emissions by 2030 while maintaining the convenience and speed our customers expect.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Team Section -->
    <section class="team-section">
        <div class="container">
            <div class="team-title">
                <h2>Meet Our Leadership</h2>
                <p>The passionate team driving innovation at E-Shop</p>
            </div>
            
            <div class="team-grid">
                <div class="team-member">
                    <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60" alt="CEO" class="team-img">
                    <div class="team-info">
                        <h3 class="team-name">Alex Johnson</h3>
                        <p class="team-role">Founder & CEO</p>
                    </div>
                </div>
                
                <div class="team-member">
                    <img src="https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60" alt="CTO" class="team-img">
                    <div class="team-info">
                        <h3 class="team-name">Sarah Chen</h3>
                        <p class="team-role">Chief Technology Officer</p>
                    </div>
                </div>
                
                <div class="team-member">
                    <img src="https://images.unsplash.com/photo-1562788869-4ed32648eb72?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60" alt="CMO" class="team-img">
                    <div class="team-info">
                        <h3 class="team-name">Michael Rodriguez</h3>
                        <p class="team-role">Chief Marketing Officer</p>
                    </div>
                </div>
                
                <div class="team-member">
                    <img src="https://images.unsplash.com/photo-1580489944761-15a19d654956?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60" alt="COO" class="team-img">
                    <div class="team-info">
                        <h3 class="team-name">Emily Wilson</h3>
                        <p class="team-role">Chief Operations Officer</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
</body>
</html>
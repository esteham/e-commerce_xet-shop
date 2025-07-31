<?php

if (!isset($_SESSION['userSession'])) {
    exit("Unauthorized access.");
}
// pages/dashboard.php
?>
<div class="container-fluid">
    <div class="page-header">
        <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
        <p class="text-muted">Welcome to the Admin Panel Dashboard</p>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Products</h6>
                            <h3>1,254</h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                            <i class="fas fa-box text-primary fa-2x"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-success"><i class="fas fa-caret-up"></i> 12.5%</span>
                        <span class="text-muted">since last month</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Sales</h6>
                            <h3>$12,540</h3>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded">
                            <i class="fas fa-shopping-cart text-success fa-2x"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-success"><i class="fas fa-caret-up"></i> 8.3%</span>
                        <span class="text-muted">since last month</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Customers</h6>
                            <h3>542</h3>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded">
                            <i class="fas fa-users text-info fa-2x"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-success"><i class="fas fa-caret-up"></i> 5.2%</span>
                        <span class="text-muted">since last month</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Revenue</h6>
                            <h3>$8,450</h3>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded">
                            <i class="fas fa-dollar-sign text-warning fa-2x"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-danger"><i class="fas fa-caret-down"></i> 2.4%</span>
                        <span class="text-muted">since last month</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Sales Overview</h5>
                </div>
                <div class="card-body">
                    <div style="height: 300px;">
                        <!-- Chart would go here -->
                        <div class="d-flex justify-content-center align-items-center h-100 text-muted">
                            <i class="fas fa-chart-line fa-3x"></i>
                            <p class="ms-3 mb-0">Sales chart will be displayed here</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Recent Activities</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item border-0 px-0">
                            <div class="d-flex">
                                <div class="avatar bg-primary bg-opacity-10 text-primary p-2 rounded me-3">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">New customer registered</h6>
                                    <p class="text-muted mb-0 small">John Doe - 2 min ago</p>
                                </div>
                            </div>
                        </li>
                        <li class="list-group-item border-0 px-0">
                            <div class="d-flex">
                                <div class="avatar bg-success bg-opacity-10 text-success p-2 rounded me-3">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">New order received</h6>
                                    <p class="text-muted mb-0 small">Order #1234 - 15 min ago</p>
                                </div>
                            </div>
                        </li>
                        <li class="list-group-item border-0 px-0">
                            <div class="d-flex">
                                <div class="avatar bg-info bg-opacity-10 text-info p-2 rounded me-3">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Product restocked</h6>
                                    <p class="text-muted mb-0 small">iPhone 13 - 1 hour ago</p>
                                </div>
                            </div>
                        </li>
                        <li class="list-group-item border-0 px-0">
                            <div class="d-flex">
                                <div class="avatar bg-warning bg-opacity-10 text-warning p-2 rounded me-3">
                                    <i class="fas fa-truck"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Order shipped</h6>
                                    <p class="text-muted mb-0 small">Order #1233 - 2 hours ago</p>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<div class="container py-4">
    <!-- Header Stats -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3 mb-md-0">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Payments</h5>
                    <div class="display-4 mb-2"><?php echo number_format($totalPayments); ?></div>
                    <p class="text-muted">Payments made</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Credits Purchased</h5>
                    <div class="display-4 mb-2">
                        <?php 
                        $totalCredits = 0;
                        foreach ($payments as $payment) {
                            $totalCredits += $payment['credits'];
                        }
                        echo number_format($totalCredits);
                        ?>
                    </div>
                    <p class="text-muted">Credits from purchases</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment History Table -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">Payment History</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Package</th>
                            <th>Amount</th>
                            <th>Credits</th>
                            <th>Status</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($payments) > 0): ?>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?php echo formatDate($payment['created_at']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['package_name'] ?? 'N/A'); ?></td>
                                    <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                    <td><?php echo number_format($payment['credits']); ?></td>
                                    <td>
                                        <?php if ($payment['status'] === 'completed'): ?>
                                            <span class="badge bg-success">Completed</span>
                                        <?php elseif ($payment['status'] === 'pending'): ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Failed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($payment['status'] === 'completed'): ?>
                                            <a href="index.php?page=payment-success&payment_id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-secondary" disabled>View</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">No payment history found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white">
                <nav aria-label="Payment history pagination">
                    <ul class="pagination justify-content-center mb-0">
                        <?php if ($currentPage > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="index.php?page=payment-history&p=<?php echo $currentPage - 1; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">&laquo;</span>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                                <a class="page-link" href="index.php?page=payment-history&p=<?php echo $i; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($currentPage < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="index.php?page=payment-history&p=<?php echo $currentPage + 1; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link">&raquo;</span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div> 
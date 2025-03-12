<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                    </div>
                    <h1 class="card-title mb-4">Payment Successful!</h1>
                    
                    <?php if ($payment): ?>
                        <p class="lead mb-4">Thank you for your purchase. Your account has been credited with <strong><?php echo number_format($payment['credits'], 2); ?> credits</strong>.</p>
                        
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Payment Details</h5>
                                <table class="table table-borderless">
                                    <tbody>
                                        <tr>
                                            <th scope="row">Transaction ID:</th>
                                            <td><?php echo htmlspecialchars($payment['id']); ?></td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Amount:</th>
                                            <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Credits:</th>
                                            <td><?php echo number_format($payment['credits'], 2); ?></td>
                                        </tr>
                                        <tr>
                                            <th scope="row">Date:</th>
                                            <td><?php echo date('F j, Y, g:i a', strtotime($payment['created_at'])); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="lead mb-4">Your payment has been processed successfully. Your account has been credited with the purchased credits.</p>
                    <?php endif; ?>
                    
                    <div class="alert alert-info">
                        <p class="mb-0">Your current balance: <strong><?php echo number_format($user['credits'], 2); ?> credits</strong></p>
                    </div>
                    
                    <?php if (isset($payment_method_saved) && $payment_method_saved): ?>
                    <div class="alert alert-success mt-3">
                        <p class="mb-0"><?php echo $payment_method_message; ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <a href="index.php?page=dashboard" class="btn btn-primary me-2">Go to Dashboard</a>
                        <a href="index.php?page=call" class="btn btn-outline-primary">Make a Call</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 
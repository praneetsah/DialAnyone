<div class="container py-4">
    <!-- Header Stats -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Calls</h5>
                    <div class="display-4 mb-2"><?php echo number_format($callStats['total_calls']); ?></div>
                    <p class="text-muted">Total calls made</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h5 class="card-title">Talk Time</h5>
                    <div class="display-4 mb-2"><?php echo $callStats['formatted_duration']; ?></div>
                    <p class="text-muted">Total time spent on calls</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <h5 class="card-title">Credits Used</h5>
                    <div class="display-4 mb-2"><?php echo $callStats['formatted_credits']; ?></div>
                    <p class="text-muted">Total credits spent on calls</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Call History -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Call History</h5>
                <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
            </div>
        </div>
        
        <!-- Filter Options -->
        <div class="collapse <?php echo isset($_GET['filter']) ? 'show' : ''; ?>" id="filterCollapse">
            <div class="card-body border-bottom">
                <form method="get" action="index.php">
                    <input type="hidden" name="page" value="call-history">
                    <input type="hidden" name="filter" value="1">
                    
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="destination" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="destination" name="destination" 
                                   value="<?php echo htmlspecialchars($filterDestination ?? ''); ?>" placeholder="e.g. +1234567890">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Statuses</option>
                                <option value="completed" <?php echo $filterStatus === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="failed" <?php echo $filterStatus === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                <option value="in-progress" <?php echo $filterStatus === 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">From Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="<?php echo htmlspecialchars($filterStartDate ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">To Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="<?php echo htmlspecialchars($filterEndDate ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="mt-3 d-flex justify-content-end">
                        <a href="index.php?page=call-history" class="btn btn-outline-secondary me-2">Clear</a>
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Call List -->
        <div class="card-body p-0">
            <?php if (empty($calls)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-phone-slash fa-3x text-muted mb-3"></i>
                    <p class="mb-0">No call records found</p>
                    <?php if (isset($_GET['filter'])): ?>
                        <p class="text-muted mt-2">Try adjusting your filter criteria</p>
                        <a href="index.php?page=call-history" class="btn btn-outline-primary mt-2">Clear Filters</a>
                    <?php else: ?>
                        <p class="text-muted mt-2">You haven't made any calls yet</p>
                        <a href="index.php?page=call" class="btn btn-primary mt-2">Make a Call</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date & Time</th>
                                <th>Phone Number</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th>Credits Used</th>
                                <th>Call Details</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($calls as $call): ?>
                                <tr>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($call['started_at'])); ?>
                                        <br>
                                        <small class="text-muted"><?php echo date('g:i A', strtotime($call['started_at'])); ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($call['destination_number']); ?>
                                    </td>
                                    <td>
                                        <?php echo formatDuration($call['duration']); ?>
                                    </td>
                                    <td>
                                        <?php if ($call['status'] === 'completed'): ?>
                                            <span class="badge bg-success">Completed</span>
                                        <?php elseif ($call['status'] === 'failed'): ?>
                                            <span class="badge bg-danger">Failed</span>
                                        <?php elseif ($call['status'] === 'in-progress'): ?>
                                            <span class="badge bg-warning">In Progress</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?php echo ucfirst($call['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo formatCredits($call['credits_used']); ?>
                                    </td>
                                    <td>
                                        <small>
                                            SID: <?php echo substr($call['twilio_call_sid'] ?? 'N/A', 0, 10); ?>...
                                            <?php if (isset($call['related_call_id']) && $call['related_call_id']): ?>
                                                <br>Related: #<?php echo $call['related_call_id']; ?>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="index.php?page=call-details&id=<?php echo $call['id']; ?>" class="btn btn-outline-primary">
                                                <i class="fas fa-info-circle"></i>
                                            </a>
                                            <a href="index.php?page=call&number=<?php echo urlencode($call['destination_number']); ?>" class="btn btn-outline-success">
                                                <i class="fas fa-phone"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white">
                <nav aria-label="Call history pagination">
                    <ul class="pagination mb-0 justify-content-center">
                        <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="index.php?page=call-history&p=<?php echo $currentPage - 1; ?><?php echo isset($_GET['filter']) ? '&filter=1' . (isset($_GET['destination']) ? '&destination=' . urlencode($_GET['destination']) : '') . (isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : '') . (isset($_GET['start_date']) ? '&start_date=' . urlencode($_GET['start_date']) : '') . (isset($_GET['end_date']) ? '&end_date=' . urlencode($_GET['end_date']) : '') : ''; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <?php
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $startPage + 4);
                        
                        if ($endPage - $startPage < 4) {
                            $startPage = max(1, $endPage - 4);
                        }
                        
                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                                <a class="page-link" href="index.php?page=call-history&p=<?php echo $i; ?><?php echo isset($_GET['filter']) ? '&filter=1' . (isset($_GET['destination']) ? '&destination=' . urlencode($_GET['destination']) : '') . (isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : '') . (isset($_GET['start_date']) ? '&start_date=' . urlencode($_GET['start_date']) : '') . (isset($_GET['end_date']) ? '&end_date=' . urlencode($_GET['end_date']) : '') : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="index.php?page=call-history&p=<?php echo $currentPage + 1; ?><?php echo isset($_GET['filter']) ? '&filter=1' . (isset($_GET['destination']) ? '&destination=' . urlencode($_GET['destination']) : '') . (isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : '') . (isset($_GET['start_date']) ? '&start_date=' . urlencode($_GET['start_date']) : '') . (isset($_GET['end_date']) ? '&end_date=' . urlencode($_GET['end_date']) : '') : ''; ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Export & Download -->
    <div class="d-flex justify-content-end">
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-download me-2"></i> Export
            </button>
            <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                <li>
                    <a class="dropdown-item" href="index.php?page=call-export&format=csv<?php echo isset($_GET['filter']) ? '&filter=1' . (isset($_GET['destination']) ? '&destination=' . urlencode($_GET['destination']) : '') . (isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : '') . (isset($_GET['start_date']) ? '&start_date=' . urlencode($_GET['start_date']) : '') . (isset($_GET['end_date']) ? '&end_date=' . urlencode($_GET['end_date']) : '') : ''; ?>">
                        CSV Format
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="index.php?page=call-export&format=pdf<?php echo isset($_GET['filter']) ? '&filter=1' . (isset($_GET['destination']) ? '&destination=' . urlencode($_GET['destination']) : '') . (isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : '') . (isset($_GET['start_date']) ? '&start_date=' . urlencode($_GET['start_date']) : '') . (isset($_GET['end_date']) ? '&end_date=' . urlencode($_GET['end_date']) : '') : ''; ?>">
                        PDF Format
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div> 
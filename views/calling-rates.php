<!-- SEO-optimized page title and description -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "WebPage",
    "name": "Calling Rates - Dial Anyone",
    "description": "Learn about our competitive calling rates and credit system for international calls. Make affordable calls worldwide with Dial Anyone."
}
</script>

<div class="container py-5">
    <h1 class="mb-4">Calling Rates & Credit System</h1>
    
    <div class="row mb-5">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h4 mb-3">Understanding Our Credit System</h2>
                    <p>At Dial Anyone, we use a simple credit system that makes it easy to make affordable international calls. Here's how it works:</p>
                    
                    <ul class="list-group list-group-flush mb-4">
                        <li class="list-group-item bg-light">
                            <strong>What are credits?</strong>
                            <p class="mb-0">Credits are our virtual currency used to pay for calls. They work like prepaid minutes, but give you more flexibility across different calling destinations.</p>
                        </li>
                        <li class="list-group-item">
                            <strong>How many minutes do credits provide?</strong>
                            <p class="mb-0">Minutes vary by the type of package you purchase. Please refer to the table below for an indicative credit charge.</p>
                        </li>
                        <li class="list-group-item bg-light">
                            <strong>Do credits expire?</strong>
                            <p class="mb-0">No, your credits never expire. Use them at your own pace without worrying about losing them.</p>
                        </li>
                    </ul>
                    <br /><b>
                    <h3 class="h5 mb-3">International Calling Rates <h7></b><i>(these are indicative and are subject to change as per our service provider, Twilio.)</i></h7></h3>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-bordered">
                            <thead class="table-primary">
                                <tr>
                                    <th>ISO</th>
                                    <th>Country</th>
                                    <th>Credits/minute</th>
                                    <th>Minutes with Ultimate Package</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Read and parse CSV file
                                $csvFile = $_SERVER['DOCUMENT_ROOT'] . '/Call Credit Pricing - OutboundVoicePricing.csv.csv';
                                if (file_exists($csvFile)) {
                                    $csv = array_map('str_getcsv', file($csvFile));
                                    array_shift($csv); // Remove header row
                                    
                                    foreach ($csv as $row) {
                                        if (count($row) >= 4 && !empty($row[0]) && !empty($row[1])) {
                                            echo '<tr>';
                                            echo '<td>' . htmlspecialchars($row[0]) . '</td>';
                                            echo '<td>' . htmlspecialchars($row[1]) . '</td>';
                                            echo '<td>' . htmlspecialchars($row[2]) . '</td>';
                                            echo '<td>' . htmlspecialchars($row[3]) . '</td>';
                                            echo '</tr>';
                                        }
                                    }
                                } else {
                                    echo '<tr><td colspan="4" class="text-center">Pricing information is currently being updated. Please check back later.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <p class="small text-muted mt-2">* Rates are subject to change. Please check back for the most up-to-date pricing.</p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 mt-4 mt-lg-0">
            <div class="card border-primary mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="h5 mb-0">Why Choose Our Credit System?</h3>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i> Transparent pricing with no hidden fees</li>
                        <li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i> Credits never expire</li>
                        <li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i> No monthly commitments or contracts</li>
                        <li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i> Secure payment processing by Stripe</li>
                        <li><i class="fas fa-check-circle text-success me-2"></i> The more you buy, the more you save</li>
                    </ul>
                </div>
            </div>
            
            <div class="card bg-light">
                <div class="card-body">
                    <h3 class="h5 mb-3">Ready to make calls?</h3>
                    <p>Sign up now and start making affordable international calls directly from your browser.</p>
                    <?php if (!isAuthenticated()): ?>
                        <a href="index.php?page=register" class="btn btn-primary btn-lg w-100">Get Started</a>
                    <?php else: ?>
                        <a href="index.php?page=credits" class="btn btn-primary btn-lg w-100">Buy Credits</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card shadow-sm mb-5">
        <div class="card-body p-4">
            <h2 class="h4 mb-4">Frequently Asked Questions</h2>
            
            <div class="accordion" id="ratesFAQ">
                <div class="accordion-item">
                    <h3 class="accordion-header" id="headingOne">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                            How do the calling rates compare to other services?
                        </button>
                    </h3>
                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#ratesFAQ">
                        <div class="accordion-body">
                            Our rates are highly competitive compared to traditional phone carriers and even many VoIP services. Please check the table above for the most up to date rates.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h3 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            Do different countries have different rates?
                        </button>
                    </h3>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#ratesFAQ">
                        <div class="accordion-body">
                            Yes, call rates vary slightly by country, but our credit system is designed to give you approximately 300-400 minutes of calling time for 1,000 credits across most destinations (please refer to the table above for the most up to date rates). Some premium destinations may use credits at a higher rate, but our system is designed to be simple and transparent. You can even call satellite numbers however they are much more pricier than any other destinations.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h3 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                            Can I call toll-free numbers with my credits?
                        </button>
                    </h3>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#ratesFAQ">
                        <div class="accordion-body">
                            Yes! You can call toll-free numbers worldwide using our service. While toll-free numbers are free for callers within their designated regions when using traditional phone lines, calling them from other countries typically incurs charges. With Dial Anyone, you can reach these numbers using your credits at our standard competitive rates.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h3 class="accordion-header" id="headingFour">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                            How is payment processed and is it secure?
                        </button>
                    </h3>
                    <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#ratesFAQ">
                        <div class="accordion-body">
                            All payments are processed securely through Stripe, a leading global payment processor. We never store your credit card information on our servers. Stripe maintains the highest level of security certification in the payment industry (PCI Service Provider Level 1), ensuring your payment information remains secure.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 